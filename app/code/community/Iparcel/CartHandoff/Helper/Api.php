<?php
/**
 * Controller for handling Cart Handoff
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Helper_Api extends Iparcel_All_Helper_Api
{
    /** @var string URL for SetCheckout API Method */
    protected $_setCheckout = 'https://pay.i-parcel.com/v1/api/SetCheckout';

    /** @var string URL for GetCheckoutDetails API Method */
    protected $_getCheckoutDetails = 'https://pay.i-parcel.com/v1/api/GetCheckoutDetails';

    /** @var string URL for CancelPayment API Method */
    protected $_cancelPayment = 'https://pay.i-parcel.com/v1/api/Cancel';

    /** @var string URL for CheckItems API Method */
    protected $_checkItems = 'https://webservices.i-parcel.com/api/CheckItems';

    /** @var int Code used when throwing Invalid Quote exception */
    protected $_invalidQuoteCode = 10;

    /**
     * Sets up customer's checkout information in UPS i-parcel
     *
     * @param Mage_Sales_Model_Quote $quote
     * @param string $cancelUrl URL to use when cancelling an order
     * @param string $returnUrl URL to use once the order is submitted.
     * @return mixed Transaction Number, in case of error, returns false
     */
    public function setCheckout(
        Mage_Sales_Model_Quote $quote,
        $cancelUrl = false,
        $returnUrl = false
    )
    {
        $customer = $quote->getCustomer();
        $billingAddress = null;
        $shippingAddress = null;
        $helper = Mage::helper('ipcarthandoff');

        // Pull the billing and shipping address from the quote
        $quoteShippingAddress = $quote->getShippingAddress();
        $quoteBillingAddress = $quote->getBillingAddress();

        // If one or both of these are not set, use the defaults from the
        // customer.
        if ($quoteBillingAddress->getEmail() == null) {
            // Pull the default billing address from the customer
            $customerDefaultBilling = $helper->getDefaultAddress(
                $customer,
                false
            );
            if (is_null($customerDefaultBilling)) {
                $billingAddress = null;
                $phoneNumber = null;
            } else {
                $billingAddress = $this->_buildAddress(
                    $customerDefaultBilling,
                    false,
                    $customer->getEmail()
                );
                $phoneNumber = $customerDefaultBilling->getTelephone();
            }
        } else {
            // Set the address information from the quote billing address
            $billingAddress = $this->_buildAddress($quoteBillingAddress, false);
            $phoneNumber = $quoteBillingAddress->getTelephone();
        }

        if ($quoteShippingAddress->getAddressId() == null) {
            // Pull the default shipping address from the customer
            $customerDefaultShipping = $helper->getDefaultAddress(
                $customer,
                true
            );
            if (is_null($customerDefaultShipping)) {
                $shippingAddress = null;
            } else {
                $shippingAddress = $this->_buildAddress(
                    $customerDefaultShipping,
                    true,
                    $billingAddress['email']
                );
            }
        } else {
            // Set the address information from the quote shipping address
            $shippingAddress = $this->_buildAddress(
                $quoteShippingAddress,
                true,
                $billingAddress['email']
            );
        }

        if ($cancelUrl == false) {
            $cancelUrl = Mage::getUrl('checkout/cart');
        }

        if ($returnUrl == false) {
            $returnUrl = $helper->getReturnUrl();
        }

        $shoppingUrl = Mage::getBaseUrl() . Mage::getStoreConfig(
                         'payment/ipcarthandoff/back_to_shopping'
                     );

        // Set currency from iparcelSession cookie
        $iparcelSession = json_decode(
            Mage::getModel('core/cookie')->get('ipar_iparcelSession')
        );
        $customerCurrency = null;
        if (isset($iparcelSession->currency)) {
            $customerCurrency = $iparcelSession->currency;
        }

        $request = array(
            'key' => Mage::getStoreConfig('iparcel/config/userid'),
            'currency_code' => $quote->getQuoteCurrencyCode(),
            'page_currency' => $customerCurrency,
            'custom' => $quote->getStoreId(),
            'discount_amount_cart' => 0,
            'prepaidamount' => 0,
            'reference_number' => $quote->getId(),
            'return' => $returnUrl,
            'shopping_url' => $shoppingUrl,
            'cancel_return' => $cancelUrl,
            'image_url' => Mage::getDesign()->getSkinUrl(
                Mage::getStoreConfig('design/header/logo_src')
            ),
            'AddressInfo' => array(
                'Billing' => $billingAddress,
                'Shipping' => $shippingAddress
            ),
            'ItemDetailsList' => null,
            'day_phone_a' => '',
            'day_phone_b' => $phoneNumber,
        );
        $totals = $quote->getTotals();

        // Find prepaid amount for quote
        $request['prepaidamount'] = $this->_findPrepaidAmount($quote);

        // Calculate discount
        $totalKeys = array(
            'subtotal', 'shipping', 'iparcel_tax', 'iparcel_duty', 'tax'
        );

        $total = 0;
        foreach ($totalKeys as $key) {
            if (array_key_exists($key, $totals) && is_object($totals[$key])) {
                $total += $totals[$key]->getValue();
            }
        }

        $request['discount_amount_cart'] = round(
            $total - $quote->getGrandTotal(),
            2
        );

        // Add items to request
        $quoteItems = $quote->getAllItems();
        $itemDetailsList = array();
        foreach($quoteItems as $item) {
            if ($this->_itemRequiresPricing($item)) {
                /**
                 * If no price is attached to this item, load it from
                 * the parent item
                 */
                $price = $item->getCalculationPrice();
                $qty = $item->getTotalQty();
                $parentItem = $item->getParentItem();

                if ($parentItem) {
                    $price = $parentItem->getCalculationPrice();
                    $qty = $parentItem->getTotalQty();
                }
                if (is_null($price)) {
                    $price = $item->getProduct()->getPrice();
                }

                $itemDetails = array(
                    'item_number' => $item->getSku(),
                    'quantity' => $qty,
                    'item_name' => $item->getName(),
                    'amount' => $price * $qty,
                    'discount_amount' => $item->getDiscountAmount(),
                );
                $itemDetailsList[] = $itemDetails;
            }
        }
        $request['ItemDetailsList'] = $itemDetailsList;

        try {
            $response = $this->_restJSON(
                $request,
                $this->_setCheckout,
                $this->_getTimeout()
            );
        } catch (Exception $e) {
            Mage::getSingleton('checkout/session')
                ->addError($this->_getTimeoutMessage());

            return false;
        }

        // Log request and response
        Mage::getModel('iparcel/log')
            ->setController('SetCheckout')
            ->setRequest(json_encode($request))
            ->setResponse($response)
            ->save();

        $response = json_decode($response);

        if (is_object($response) && property_exists($response, 'tx')) {
            return $response->tx;
        }

        return false;
    }

    /**
     * Retrieves checkout details from UPS i-parcel API
     *
     * @param string $transactionId Transaction ID returned by the API
     * @return object On success, contains API response; on error, a a status message
     */
    public function getCheckoutDetails($transactionId)
    {
        // Make request to API
        $request = array(
            'key' => Mage::getStoreConfig('iparcel/config/userid'),
            'tx' => $transactionId
        );

        try {
            $response = $this->_restJSON(
                $request,
                $this->_getCheckoutDetails,
                $this->_getTimeout()
            );
        } catch (Exception $e) {
            $return = array(
                'status' => 0,
                'message' => $this->_getTimeoutMessage()
            );

            return (object) $return;
        }

        // Log request and response
        Mage::getModel('iparcel/log')
            ->setController('GetCheckoutDetails')
            ->setRequest(json_encode($request))
            ->setResponse($response)
            ->save();

        $response = json_decode($response);

        // If an error is returned, build error information and return
        if (!property_exists($response, 'status') || $response->status == 'FAIL') {
            $return = array (
                'status' => 0,
                'message' => "Unable to verify order with UPS i-parcel"
            );
            return (object) $return;
        }

        return (object) $response;
    }

    /**
     * Cancels payment for the transaction ID -- no longer used
     *
     * @param string $tx Transaction ID to cancel
     * @return bool Returns true on success
     */
    public function cancelPayment($tx)
    {
        return true;
    }

    /**
     * Filters a product collection and removes products that are not eligible
     * for international customers.
     *
     * If a product is passed in instead of a collection, the price from the
     * CheckItems call is used for the product -- no other changes are made.
     *
     * @param object $collection
     * @param string $countryCode If set, use this Country Code
     *               instead of the user's cookie
     * @param boolean $returnResponse If true, return the response
     *                from the API call
     * @return object
     */
    public function checkItems(
        $collection,
        $countryCode = false,
        $returnResponse = false
    )
    {
        $singleProduct = false;

        /**
         * If $collection is a single product, create a collection of
         * that single product to act on
         */
        $remove = true;
        $isCollection = in_array(
            'Varien_Data_Collection',
            class_parents($collection)
        );
        if ($isCollection == false) {
            $singleProduct = true;
            $collection = Mage::getModel('catalog/product')
                ->getCollection()
                ->addAttributeToFilter('sku', $collection->getSku());
            $remove = false;
        }

        $storeId = Mage::app()->getStore()->getId();

        // Get iparcel cookie
        $cookie = Mage::getModel('core/cookie')->get('ipar_iparcelSession');
        $cookie = json_decode($cookie);

        // If the cookie is empty, create an object to hold the locale/currency
        if ($countryCode !== false) {
            $cookie = (object) array(
                'locale' => $countryCode,
                'currency' => 'USD'
            );
        }

        // Get iparcel session ID
        $sessID = Mage::getModel('core/cookie')->get('ipar_sess_id');

        if (!isset($cookie->locale) || $cookie->locale == 'US') {
            $return = $collection;
            if ($returnResponse) {
                $return = array(
                    'collection' => $collection,
                    'response' => null
                );
            }
            return $return;
        }

        // Pass the product collection into the CheckItems class to check the
        // previously cached results
        $checkItemsModel = Mage::getModel('ipcarthandoff/checkitems');
        $cache = $checkItemsModel->getCache(
            $collection,
            $cookie->locale,
            $storeId
        );

        $eligibleSKUs = array();
        // If the cache count matches the collection, skip sending the request
        if (count($cache) != count($collection)) {

            // Prepare the ItemDetailsList
            $itemDetailsCollection = $this->_prepareCollectionForCheckItems(
                $collection
            );

            // Split the ItemDetailsList into collections of products
            $itemDetailsCollection = array_chunk(
                $itemDetailsCollection,
                $this->_getChunkSize()
            );

            $responses = array();
            foreach ($itemDetailsCollection as $itemDetailsList) {
                $request = array(
                    'Key' => Mage::helper('iparcel')->getGuid(),
                    'ItemDetailsList' => $itemDetailsList,
                    'AddressInfo' => array(
                        'Billing' => array(),
                        'Shipping' => array(
                            'CountryCode' => $cookie->locale,
                            'PostalCode' => 'A1A1A1'
                        ),
                    ),
                    'CurrencyCode' => $cookie->currency,
                    'DDP' => true,
                    'Insurance' => false,
                    'SessionID' => $sessID,
                    'ServiceLevel' => 115
                );

                $response = $this->_restJSON($request, $this->_checkItems);

                Mage::getModel('iparcel/log')
                    ->setController('CheckItems')
                    ->setRequest(json_encode($request))
                    ->setResponse($response)
                    ->save();

                $responses[] = json_decode($response, true);
            }

            // Build the $response from the separate responses.
            $response = array_shift($responses);
            foreach ($responses as $currentResponse) {
                $response['ItemDetailsList'] = array_merge(
                    $response['ItemDetailsList'],
                    $currentResponse['ItemDetailsList']
                );
            }

            // Build the $items from the response's ItemDetailsList
            $items = $response['ItemDetailsList'];

            // Cache response from the web service
            $checkItemsModel->cacheResponse(
                $items,
                $collection,
                $cookie->locale,
                $storeId
            );

            // Load eligible SKUs into the array
            foreach ($items as $item) {
                if ($item['HTSCode'] != '' && $item['HTSCode'] != 'NONE') {
                    $eligibleSKUs[$item['SKU']] = $item['ValueCompanyCurrency'];
                }
            }
        } else {
            // Build the $eligibleSKUs array from the cache
            foreach ($cache as $sku => $data) {
                if ($data['eligible']) {
                    $eligibleSKUs[$sku] = $data['price'];
                }
            }
        }

        // Strip products from the collection that are not in the array
        $itemIds = array();
        $itemList = $collection->getItems();
        foreach ($itemList as $key => $item) {
            if (in_array($item->getSku(), array_keys($eligibleSKUs))) {
                $itemIds[] = $key;
            } else {
                $collection->removeItemByKey($key);
            }
        }

        // Add filter for items remaining, and reload
        if ($remove){
            $collection->addFieldToFilter('entity_id',
                array(
                    'in' => $itemIds
                )
            );
            $collection->load();
        }

        if ($singleProduct) {
            $return = $collection->getFirstItem();
        }

        $return = $collection;

        // If `$returnResponse` is enabled, include the response in
        // the return value
        if ($returnResponse) {
            $return = array (
                'collection' => $return,
                'response' => $response
            );
        }

        return $return;
    }

    /**
     * Formats the products from the collection for the ItemDetailsList of Checkitems
     *
     * @param object $collection
     * @return array Product array formatted for CheckItems
     */
    private function _prepareCollectionForCheckItems($collection)
    {
        $productArray = array();

        foreach ($collection as $product) {
            $item = array(
                'SKU' => $product->getSku(),
                'Quantity' => 1,
                'itemStyle' => null,
            );

            $productArray[] = $item;
        }

        return $productArray;
    }

    /**
     * Builds an address for setCheckout with the given Address Object
     *
     * @param object $address Address model (various classes)
     * @param bool $shipping If set to true, will append "shipping_" to the keys
     * @param string $email If set, this email address is used
     * @return array Formatted address array, empty if the $address is invalid
     */
    private function _buildAddress($address, $shipping = false, $email = null)
    {
        if (!is_object($address)) {
            return array();
        }

        $prefix = '';

        if ($shipping) {
            $prefix = 'shipping_';
        }

        $formattedAddress = array (
            $prefix . 'email' => is_null($email) ? $address->getEmail() : $email,
            $prefix . 'first_name' => $address->getFirstname(),
            $prefix . 'last_name' => $address->getLastname(),
            $prefix . 'address1' => $address->getStreet1(),
            $prefix . 'address2' => $address->getStreet2(),
            $prefix . 'city' => $address->getCity(),
            $prefix . 'state' => $address->getRegion(),
            $prefix . 'zip' => $address->getPostcode(),
            $prefix . 'country' => $address->getCountryId()
        );

        return $formattedAddress;
    }

    /**
     * Set the URLs for API Calls.
     *
     * Useful for setting the API endpoint URLs to controlled URLs for testing.
     *
     * @param array $urls Array of URLs to set as [var_name] => 'value'
     * @return boolean True on success
     */
    public function setUrls($urls)
    {
        try {
            foreach ($urls as $name => $url) {
                $this->{$name} = $url;
            }
        } catch (Exception $e) {
            Mage::log($e->getMessage());
            return false;
        }

        return true;
    }

    /**
     * Builds order from getCheckoutDetails response
     *
     * @param Mage_Sales_Model_Quote $quote Magento quote
     * @param object $response API Response
     * @param Mage_Sales_Model_Order Created order
     */
    public function buildOrder($quote, $response)
    {

        Mage::register('iparcel_skip_auto_create_shipment', true);
        Mage::register('iparcel_skip_auto_submit', true);

        /**
         * Make sure the quote is active, and is an ipcarthandoff order
         */
        if (!$quote->getIsActive()
            || $quote->getPayment()->getMethod() != Mage::helper('ipcarthandoff')->getPaymentMethodCode()
        ) {
            throw new Exception(
                "Invalid quote for Cart Handoff review.",
                $this->_invalidQuoteCode
            );
            return;
        }

        // Clear quote address
        $quote->removeAllAddresses()->save();

        // Set store for quote to match request.
        $storeId = $response->custom;
        if (is_numeric($storeId)) {
            $quote->setStoreId($storeId);
        }

        // Set addresses to match response
        $responseBilling = $response->AddressInfo->Billing;
        $billingStreet = $responseBilling->address1;
        if ($responseBilling->address2 != '') {
            $billingStreet .= "\n" . $responseBilling->address2;
        }
        $billingCountry = Mage::getModel('directory/country')->load(
            $responseBilling->country
        );
        $billingRegion = Mage::getModel('directory/region')->loadByName(
            $responseBilling->state,
            $billingCountry->getId()
        );
        $quote->getBillingAddress()
            ->setFirstname($responseBilling->first_name)
            ->setLastname($responseBilling->last_name)
            ->setStreet($billingStreet)
            ->setCity($responseBilling->city)
            ->setPostcode($responseBilling->zip)
            ->setCountryId($billingCountry->getId())
            ->setRegionId($billingRegion->getId())
            ->setTelephone($response->day_phone_b)
            ->setEmail($responseBilling->email)
            ->save();

        $responseShipping = $response->AddressInfo->Shipping;
        /**
         * After pulling the shipping address from the response, we pass it to
         * Iparcel_CartHandoff_Model_System_Config_Source_Shippingaddress for
         * any processing
         */
        $responseShipping = $this->_updateShippingAddress($responseShipping);

        $shippingStreet = $responseShipping->shipping_address1;
        if ($responseShipping->shipping_address2 != '') {
            $shippingStreet .= "\n" . $responseShipping->shipping_address2;
        }
        $shippingCountry = Mage::getModel('directory/country')->load(
            $responseShipping->shipping_country
        );
        $shippingRegion = Mage::getModel('directory/region')->loadByName(
            $responseShipping->shipping_state,
            $shippingCountry->getId()
        );
        $quote->getShippingAddress()
            ->setFirstname($responseShipping->shipping_first_name)
            ->setLastname($responseShipping->shipping_last_name)
            ->setStreet($shippingStreet)
            ->setCity($responseShipping->shipping_city)
            ->setPostcode($responseShipping->shipping_zip)
            ->setCountryId($shippingCountry->getId())
            ->setRegionId($shippingRegion->getId())
            ->setTelephone($response->day_phone_b)
            ->setShippingMethod('iparcel_' . $response->servicelevel)
            ->setShippingAmount($response->shipping_cost)
            ->setEmail($responseShipping->shipping_email)
            ->save();

        // Setup payment details
        $quote->getPayment()->addData(
            array(
                'method' => Mage::helper('ipcarthandoff')->getPaymentMethodCode()
            )
        );

        $quote->save();
        $quote->reserveOrderId();

        $serviceLevels = array(
            'iparcel_' . $response->servicelevel => array(
                'duty' => (float) $response->duty,
                'tax' => (float) $response->tax
            )
        );

        // Create shipping quote
        $shippingQuote = Mage::getModel('iparcel/api_quote')->loadByQuoteId($quote->getId());
        $shippingQuote->setQuoteId($quote->getId());
        $shippingQuote->setParcelId(0);
        $shippingQuote->setServiceLevels($serviceLevels);
        $shippingQuote->save();

        // Create shipping rate
        $rate = Mage::getModel('sales/quote_address_rate');
        $rate->setCode('iparcel_' . $response->servicelevel)
            ->setCarrier('iparcel')
            ->setCarrierTitle('UPS i-parcel')
            ->setMethod('iparcel_' . $response->servicelevel)
            ->setMethodTitle('iparcel')
            ->setMethodDescription('iparcel')
            ->setPrice($response->shipping_cost);

        $quote->getShippingAddress()->removeAllShippingRates();
        $quote->getShippingAddress()->addShippingRate($rate);

        $convert = Mage::getModel('sales/convert_quote');

        $order = $convert->toOrder($quote);

        $order->setPayment($convert->paymentToOrderPayment($quote->getPayment()));
        $order->setBillingAddress($convert->addressToOrderAddress($quote->getBillingAddress()));
        $order->setShippingAddress($convert->addressToOrderAddress($quote->getShippingAddress()));

        $items = $quote->getAllItems();
        foreach ($items as $item) {
            $orderItem = $convert->itemToOrderItem($item);
            $productOptions = $item->getProduct()->getTypeInstance(true)->getOrderOptions($item->getProduct());
            if ($productOptions) {
                $options = $productOptions;
            }

            $additionalOptions = $item->getOptionByCode('additional_options');
            if ($additionalOptions) {
                $options['additional_options'] = unserialize($additionalOptions->getValue());
            }

            if ($options) {
                $orderItem->setProductOptions($options);
            }

            if ($item->getParentItem()) {
                $orderItem->setParentItem($order->getItemByQuoteItemId($item->getParentItem()->getId()));
            }

            $order->addItem($orderItem);
        }

        $quote->collectTotals();

        $quote->setIsActive(0);

        Mage::getModel('sales/service_quote', $quote)->submitAll();

        // Create shipment with tracking number
        $order = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());

        if (Mage::getStoreConfig('payment/ipcarthandoff/send_new_order_emails') != 0) {
            $order->sendNewOrderEmail();
        }

        $itemsToShip = array();
        foreach ($order->getAllItems() as $item) {
            $itemsToShip[$item->getItemId()] = $item->getQtyOrdered();
        }

        $shipmentId = Mage::getModel('sales/order_shipment_api')->create(
            $order->getIncrementId(),
            $itemsToShip,
            null,
            false,
            false
        );

        // Add tracking number
        $storedServiceLevels = Mage::helper('iparcel')->getServiceLevels();
        $title = 'UPS I-Parcel';
        if (array_key_exists($response->servicelevel, $storedServiceLevels)) {
            $title = $storedServiceLevels[$response->servicelevel];
        }

        Mage::getModel('sales/order_shipment_api')->addTrack(
            $shipmentId,
            $order->getShippingCarrier()->getCarrierCode(),
            $title,
            $response->trackingnumber
        );

        $order->addStatusHistoryComment(
            'Pending payment status update from UPS i-parcel',
            Mage_Sales_Model_Order::STATE_PENDING_PAYMENT
        );

        $order->save();

        return $order;
    }

    /**
     * Alter the shipping address returned by getCheckoutDetails to match the
     * configured shipping address to use for Cart Handoff orders
     *
     * @param $address
     * @return object
     */
    private function _updateShippingAddress($address)
    {
        $shippingAddressSource = Mage::getModel(
            'ipcarthandoff/system_config_source_shippingaddress'
        );

        $shippingAddressConfiguration = Mage::getStoreConfig(
            'payment/ipcarthandoff/shipping_address'
        );

        /**
         * If the store is configured to override the user's shipping address,
         * pull that address from the source model
         */
        if ($shippingAddressConfiguration != $shippingAddressSource::CUSTOMER) {
            $selectedAddress = $shippingAddressSource->getAddress($shippingAddressConfiguration);
            $selectedAddress['shipping_email'] = $address->shipping_email;
            $address = (object) $selectedAddress;
        }

        return $address;
    }

    /**
     * Determines if item requires pricing in API request
     *
     * @param $item
     * @return bool
     */
    private function _itemRequiresPricing($item)
    {
        $type = $item->getProductType();
        if ($type == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
            || $type == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE
            || $type == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE
        ) {
            return true;
        }
        return false;
    }

    /**
     * Finds the prepaid value of a quote.
     *
     * Because there is no baked-in prepayment methods in Magento
     * (except Enterprise Gift Cards) This method has to do the work
     * of supporting third-party prepayment methods
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return float
     */
    private function _findPrepaidAmount(Mage_Sales_Model_Quote $quote)
    {
        $prepaidAmount = 0.00;

        // Support for AheadWorks_Storecredit
        if (class_exists('AW_Storecredit_Helper_Data', false)) {
            $awStorecreditHelper = Mage::helper('aw_storecredit');
            if ($awStorecreditHelper
                && $awStorecreditHelper->isModuleEnabled()
            ) {
                $awStorecreditTotals = Mage::helper('aw_storecredit/totals');
                $creditCollection = $awStorecreditTotals
                                  ->getQuoteStoreCredit($quote->getId());

                foreach ($creditCollection as $credit) {
                    $prepaidAmount += $credit->getStorecreditAmount();
                }
            }
        }

        return $prepaidAmount;
    }

    /**
     * Returns the chunk size configured for CheckItems API calls
     *
     * @return int
     */
    private function _getChunkSize()
    {
        $chunkSize = Mage::getStoreConfig(
            'iparcel/international_customer/checkitems_chunk_size'
        );

        return (int) $chunkSize;
    }

    /**
     * Returns the configured timeout setting
     *
     * @return int
     */
    private function _getTimeout()
    {
        return (int) Mage::getStoreConfig('iparcel/api/timeout');
    }

    /**
     * Returns configured timeout message
     *
     * @return string
     */
    private function _getTimeoutMessage()
    {
        return (string) Mage::getStoreConfig('iparcel/api/timeout_message');
    }
}
