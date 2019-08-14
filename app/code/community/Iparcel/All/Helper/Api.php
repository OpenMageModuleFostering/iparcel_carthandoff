<?php
/**
 * I-parcel shared API helper
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @authoer     Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Helper_Api
{
    /** @var string URL for the Cancel endpoint */
    protected $_cancel = 'https://webservices.i-parcel.com/api/Cancel';

    /** @var string URL for the Split endpoint */
    protected $_split = 'https://webservices.i-parcel.com/api/Split';

    /** @var string URL for the SubmitCatalog endpoint */
    protected $_submitCatalog = 'https://webservices.i-parcel.com/api/SubmitCatalog';

    /** @var string URL for the SubmitParcel endpoint */
    protected $_submitParcel = 'https://webservices.i-parcel.com/api/SubmitParcel';

    /** @var string URL for the Quote endpoint */
    protected $_quote = 'https://webservices.i-parcel.com/api/Quote';

    /**
     * Send POST requests to the REST API
     *
     * @param string $post POST Data to send
     * @param string $url REST API URL to send POST data to
     * @param array $header Array of headers to attach to the request
     * @return string Response from the POST request
     */
    protected function _rest($post, $url, array $header)
    {
        $curl = curl_init($url);

        $timeout = 15;
        if ($timeout) {
            curl_setopt($curl, CURLOPT_TIMEOUT, $timeout);
        }

        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
        curl_setopt($curl, CURLOPT_POSTFIELDS, "$post");
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($curl);

        curl_close($curl);

        return $response;
    }

    /**
     * Send REST XML requests
     *
     * Wrapper for _rest() that sends a SimpleXMLElement object to the API.
     *
     * @param SimpleXMLElement $xml XML to send
     * @param string $url REST API URL to send POST data to
     * @return string Response from the POST request
     */
    protected function _restXML($xml, $url)
    {
        return $this->_rest($xml->asXml, $url, array('Content-Type: text/xml'));
    }

    /**
     * Send REST JSON requests
     *
     * Wrapper for _rest() that sends the passed data as JSON to the API.
     *
     * @param string $json Data to be JSON encoded and sent to the API
     * @param string $url REST API URL to send POST data to
     * @return string Response from the POST request
     */
    protected function _restJSON($json, $url)
    {
        return $this->_rest(
            json_encode($json),
            $url,
            array('Content-Type: text/json')
        );
    }

    /**
     * Cancels shipments via the Web Service API
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @return bool
     */
    public function cancelShipment(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $json = array();
        $json['TrackingNumber'] = Mage::helper('iparcel')->getTrackingNumber($shipment);
        $json['UserNotes'] = '';
        $json['Key'] = Mage::helper('iparcel')->getGuid();

        $response = $this->_restJSON($json, $this->_cancel);

        $log = Mage::getModel('iparcel/log');
        $log->setRequest(json_encode($json));
        $log->setResponse($response);
        $log->setController('Cancel Shipment');
        $log->save();

        $response = json_decode($response);

        if ($response->Code != 1) {
            Mage::throwException(
                'Failed when canceling shipment: ' . $response->Message
            );
        } else {
            Mage::getModel('sales/order_shipment_api')->addComment(
                $shipment->getIncrementId(),
                'Shipment canceled',
                false,
                false
            );
        }

        return true;
    }

    /**
     * Cancels all shipments of an order
     *
     * @param Mage_Sales_Model_Order $order
     * @return bool
     */
    public function cancelShipmentsByOrder(Mage_Sales_Model_Order $order)
    {
        if (!Mage::helper('iparcel')->isIparcelOrder($order)) {
            return true;
        }

        $shipmentsCollection = $order->getShipmentsCollection();

        foreach ($shipmentsCollection as $shipment) {
            $this->cancelShipment($shipment);
        }

        return true;
    }


    /**
     * Send Quote request
     *
     * Takes the passed Rate Request and transforms it into data that can
     * be passed to the API. It then submits the request as JSON to the
     * Quote API endpoint.
     *
     * @param Mage_Shipping_Model_Rate_Request $request Magento Shipping Rate Request
     * @return stdClass Response from the Quote API
     */
    public function quote(Mage_Shipping_Model_Rate_Request $request)
    {
        // log init
        $log = Mage::getModel('iparcel/log');
        /* var $log Iparcel_All_Model_Api_Log */
        $log->setController('Quote');
        $quote = Mage::getModel('checkout/cart')->getQuote();
        /* var $quote Mage_Sales_Model_Quote */
        $shippingAddress = $quote->getShippingAddress();
        /* var $shippingAddress Mage_Sales_Model_Quote_Address */
        $billingAddress = $quote->getBillingAddress();
        /* var $billingAddress Mage_Sales_Model_Quote_Address */
        $json = array();
        $addressInfo = array();
        $billingStreet = $billingAddress->getStreet();
        $billing = array();
        $billing['City'] = $billingAddress->getCity();
        $billing['CountryCode'] = $billingAddress->getCountryId();
        $billing['Email'] = $quote->getCustomerEmail();
        $billing['FirstName'] = $billingAddress->getFirstname();
        $billing['LastName'] = $billingAddress->getLastname();
        $billing['Phone'] = $billingAddress->getTelephone();
        $billing['PostCode'] = $billingAddress->getPostcode();
        $billing['Region'] = $billingAddress->getRegion();
        for ($i=0; $i<count($billingStreet); $i++) {
            $billing['Street'.($i+1)] = $billingStreet[$i];
        }
        $addressInfo['Billing'] = $billing;
        $shippingStreet = explode("\n", $request->getDestStreet());
        $shipping = array();
        $shipping['City'] = $request->getDestCity();
        $shipping['CountryCode'] = $request->getDestCountryId();
        $shipping['Email'] = $quote->getCustomerEmail();
        $shipping['FirstName'] = $shippingAddress->getFirstname();
        $shipping['LastName'] = $shippingAddress->getLastname();
        $shipping['Phone'] = $shippingAddress->getTelephone();
        $shipping['PostCode'] = $request->getDestPostcode();
        $shipping['Region'] = $request->getDestRegionCode();
        foreach($shippingStreet as $key => $value) {
            $shipping['Street' . ($key + 1)] = $value;
        }
        $addressInfo['Shipping'] = $shipping;
        $addressInfo['ControlNumber'] = $quote->getCpf();
        $json['AddressInfo'] = $addressInfo;
        $json['CurrencyCode'] = $request->getPackageCurrency()->getCurrencyCode();
        $json['DDP'] = true;
        $itemsList = array();
        foreach ($request->getAllItems() as $item) {
            /* var $item Mage_Sales_Model_Quote_Item */
            $itemProduct = Mage::getModel('catalog/product')->load($item->getProductId());
            /* var $itemProduct Mage_Catalog_Model_Product */
            //get item price
            $itemPrice = (float)$item->getFinalPrice() ?: (float)$item->getPrice();
            // if not price and item has parent (is configurable)
            if (!$itemPrice && ($parent=$item->getParentItem())) {
                // get parent price
                $itemPrice = (float)$parent->getFinalPrice() ?: (float)$parent->getPrice();
            }
            // if still not price
            if (!$itemPrice) {
                // get product price
                $itemPrice = (float)$item->getProduct()->getPrice();
            }
            // if product isn't virtual and is configurable or downloadable
            if ($item["is_virtual"] == false && !in_array($itemProduct->getTypeId(), array('configurable','downloadable'))) {
                // add line item node
                $lineItem = array();
                $lineItem['SKU'] = $item->getSku();
                $lineItem['CustWeightLbs'] = (float)$item->getWeight();
                $lineItem['ValueUSD'] = $itemPrice;
                $lineItem['CustLengthInches'] = (float)$item->getLength();
                $lineItem['CustWidthInches'] = (float)$item->getWidth();
                $lineItem['CustHeightInches'] = (float)$item->getHeight();
                $lineItem['Quantity'] = $item->getTotalQty();
                $lineItem['ValueShopperCurrency'] = $itemPrice;
                $lineItem['ShopperCurrency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                $itemsList[] = $lineItem;
            }
        }
        $json['ItemDetailsList'] = $itemsList;
        // Get discounts
        $totals = $quote->getTotals();
        $discount = 0;
        if (isset($totals['discount']) && $totals['discount']->getValue()) {
            $discount = -1 * $totals['discount']->getValue();
        }
        $json['OtherDiscount'] = $discount;
        $json['OtherDiscountCurrency'] = $quote->getQuoteCurrencyCode();
        $json['ParcelID'] = 0;
        $json['SessionID'] = '';
        $json['key'] = Mage::helper('iparcel')->getGuid();
        $log->setRequest(json_encode($json));
        $response = $this->_restJSON($json, $this->_quote);
        $log->setResponse($response);
        $log->save();
        return json_decode($response);
    }

    /**
     * Send SubmitCatalog request
     *
     * Takes the passed Product Collection and transforms it into data that can
     * be passed to the API. It then submits the request as JSON to the
     * SubmitCatalog API endpoint.
     *
     * @param Varien_Data_Collection $productCollection A Magento Product collection
     * @return int The amount of products uploaded to the API
     */
    public function submitCatalog(Varien_Data_Collection $productCollection)
    {
        // init log
        /** @var Iparcel_All_Model_Log $log */
        $log = Mage::getModel('iparcel/log');
        $log->setController('Submit Catalog');

        $items = $this->prepareProductsForSubmitCatalog($productCollection);

        $log->setRequest(json_encode($items));

        $numberToUpload = count($items['SKUs']);

        if ($numberToUpload > 0) {
            $response = $this->_restJSON($items, $this->_submitCatalog);

            $log->setResponse($response);
            $log->save();

            if (!preg_match('/.*Success.*/', $response)) {
                $numberToUpload = -1;
            }
        }

        return $numberToUpload;
    }

    /**
     * Send Submit Parcel request
     *
     * Takes the passed Shipment and transforms it into data that can be passed
     * to the API. It then submits the request as JSON to the SubmitParcel API
     * endpoint.
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment Magento shipment to be acknowledged as parcel
     * @return stdClass Response from the SubmitParcel API
     */
    public function submitParcel(Mage_Sales_Model_Order_Shipment $shipment)
    {
        $order = $shipment->getOrder();
        // init log
        $log = Mage::getModel('iparcel/log');
        /* var $log Iparcel_All_Model_Api_Log */
        $log->setController('Submit Parcel');
        $shippingAddress = $order->getShippingAddress();
        /* var $shippingAddress Mage_Sales_Model_Quote_Address */
        $billingAddress = $order->getBillingAddress();
        /* var $billingAddress Mage_Sales_Model_Quote_Address */
        $json = array();
        $addressInfo = array();
        $billingStreet = $billingAddress->getStreet();
        $billing = array();
        $billing['City'] = $billingAddress->getCity();
        $billing['CountryCode'] = $billingAddress->getCountryId();
        $billing['Email'] = $order->getCustomerEmail();
        $billing['FirstName'] = $billingAddress->getFirstname();
        $billing['LastName'] = $billingAddress->getLastname();
        $billing['Phone'] = $billingAddress->getTelephone();
        $billing['PostCode'] = $billingAddress->getPostcode();
        $billing['Region'] = $billingAddress->getRegion();
        $billing['Street1'] = $billingStreet[0];
        if (array_key_exists(1, $billingStreet)) {
            $billing['Street2'] = $billingStreet[1];
        }
        $addressInfo['Billing'] = $billing;
        $shippingStreet = $shippingAddress->getStreet();
        $shipping = array();
        $shipping['City'] = $shippingAddress->getCity();
        $shipping['CountryCode'] = $shippingAddress->getCountryId();
        $shipping['Email'] = $order->getCustomerEmail();
        $shipping['FirstName'] = $shippingAddress->getFirstname();
        $shipping['LastName'] = $shippingAddress->getLastname();
        $shipping['Phone'] = $shippingAddress->getTelephone();
        $shipping['PostCode'] = $shippingAddress->getPostcode();
        $shipping['Region'] = $shippingAddress->getRegion();
        $shipping['Street1'] = $shippingStreet[0];
        $shipping['Street2'] = $shippingStreet[1];
        $addressInfo['Shipping'] = $shipping;
        $addressInfo['ControlNumber'] = $order->getCpf();
        $json['AddressInfo'] = $addressInfo;
        $json['CurrencyCode'] = $order->getOrderCurrencyCode();
        $json['DDP'] = true;
        $shipmentItems = $shipment->getAllItems();
        $itemsList = array();
        foreach ($shipmentItems as $item) {
            /** @var $item Mage_Sales_Model_Order_Shipment_Item */
            // Check for a configurable product -- the simple should be loaded
            /** @var $itemProduct Mage_Catalog_Model_Product */
            $orderItem = $item->getOrderItem();
            if ($orderItem->getProductType() == "configurable") {
                $itemProduct = $orderItem->getChildrenItems();
                $itemProduct = $itemProduct[0];
            } else {
                $itemProduct = Mage::getModel('catalog/product')->load($item->getOrderItem()->getProductId());
            }
            //get item price
            $itemPrice = (float)$item->getFinalPrice() ?: (float)$item->getPrice();
            // if not price and item has parent (is configurable)
            if (!$itemPrice && ($parent=$item->getParentItem())) {
                // get parent price
                $itemPrice = (float)$parent->getFinalPrice() ?: (float)$parent->getPrice();
            }
            // if still not price
            if (!$itemPrice) {
                // get product price
                $itemPrice = (float)$item->getProduct()->getPrice();
            }
            // if product isn't virtual and is configurable or downloadable
            if ($item["is_virtual"] == false && !in_array($itemProduct->getTypeId(), array('configurable','downloadable'))) {
                // add line item node
                $lineItem = array();
                $lineItem['SKU'] = $item->getSku();
                $lineItem['CustWeightLbs'] = (float)$item->getWeight();
                $lineItem['ValueUSD'] = $itemPrice;
                $lineItem['CustLengthInches'] = (float)$item->getLength();
                $lineItem['CustWidthInches'] = (float)$item->getWidth();
                $lineItem['CustHeightInches'] = (float)$item->getHeight();
                $lineItem['Quantity'] = (float)$item->getQty();
                $lineItem['ValueShopperCurrency'] = $itemPrice;
                $lineItem['ShopperCurrency'] = Mage::app()->getStore()->getCurrentCurrencyCode();
                $itemsList[] = $lineItem;
            }
        }
        $json['ItemDetailsList'] = $itemsList;
        // if order_reference is set add it to request
        if (Mage::getStoreConfig('carriers/iparcel/order_reference')) {
            $json['OrderReference'] = $order->getIncrementId();
        }
        // Get discounts
        $orderDiscountAmount = $order->getData('discount_amount');
        $discount = 0;
        if ($orderDiscountAmount != 0) {
            $discount = -1 * $orderDiscountAmount;
        }
        // Get ServiceLevelID
        $method = $order->getShippingMethod();
        /* var $method string */
        $method = explode('_', $method);
        /* var $method array */
        array_shift($method);
        $serviceLevelId = implode('_', $method);
        /* var $serviceLevelId string */
        $json['OtherDiscount'] = $discount;
        $json['OtherDiscountCurrency'] = $order->getOrderCurrencyCode();
        $json['ServiceLevel'] = $serviceLevelId;
        $json['SessionID'] = '';
        $json['key'] = Mage::helper('iparcel')->getGuid();
        $log->setRequest(json_encode($json));
        $response = $this->_restJSON($json, $this->_submitParcel);
        $log->setResponse($response);
        $log->save();
        return json_decode($response);
    }

    /**
     * Split a shipment via web service API
     *
     * @param Mage_Sales_Model_Order_Shipment $shipment
     * @param array $itemCollection Items to split into separate shipment
     * @return Object
     */
    public function splitShipment(Mage_Sales_Model_Order_Shipment $shipment, $itemCollection)
    {
        if (count($itemCollection) < 1) {
            Mage::throwException(
                'Failed when splitting shipment: Item count must be greater than 0'
            );
        }

        $json = array();
        $json['TrackingNumber'] = Mage::helper('iparcel')->getTrackingNumber($shipment);
        $json['SkuList'] = array();
        $json['Key'] = Mage::helper('iparcel')->getGuid();

        $items = array();
        foreach ($itemCollection as $item) {
            $items['Sku'] = $item['sku'];
            $items['Quantity'] = $item['qty'];
            $json['SkuList'][] = $items;
        }

        $response = $this->_restJSON($json, $this->_split);

        $log = Mage::getModel('iparcel/log');
        $log->setController('Split Shipment');
        $log->setRequest(json_encode($json));
        $log->setResponse($response);
        $log->save();

        $response = json_decode($response);

        if (is_array($response) && count($response) == 2) {
            // Delete old shipment, create two new ones.

            $order = $shipment->getOrder();
            $orderItems = $order->getAllItems();

            // Split the order items into two arrays, one for each new shipment
            $firstItems = array();
            $secondItems = array();
            foreach($orderItems as $orderItem) {
                foreach($itemCollection as $item) {
                    if ($orderItem->getSku() == $item['sku']) {
                        $firstItems[$orderItem->getId()] = $item['qty'];
                        $secondItems[$orderItem->getId()] = $orderItem->getQtyOrdered() - $item['qty'];
                    } else {
                        $secondItems[$orderItem->getId()] = $orderItem->getQtyOrdered();
                    }
                }
            }

            // Delete the original shipment
            $shipment->delete();
            $items = $order->getAllVisibleItems();
            foreach($items as $item){
                $item->setQtyShipped(0);
                $item->save();
            }

            Mage::register('iparcel_skip_auto_submit', true);
            // Create a new shipment for each set of items
            $firstShipment = Mage::getModel('sales/order_shipment_api')->create(
                $order->getIncrementId(),
                $firstItems,
                null,
                false,
                false
            );
            Mage::getModel('sales/order_shipment_api')->addTrack(
                $firstShipment,
                $order->getShippingCarrier()->getCarrierCode(),
                'I-Parcel',
                $response[0]
            );

            $secondShipment = Mage::getModel('sales/order_shipment_api')->create(
                $order->getIncrementId(),
                $secondItems,
                null,
                false,
                false
            );
            Mage::getModel('sales/order_shipment_api')->addTrack(
                $secondShipment,
                $order->getShippingCarrier()->getCarrierCode(),
                'I-Parcel',
                $response[1]
            );
            Mage::unregister('iparcel_skip_auto_submit');
        } else {
            Mage::throwException(
                'Failed when splitting shipment.'
            );
        }

        return true;
    }

    /**
     * Prepare product collection for SubmitCatalog calls
     *
     * This function takes a Magento product collection and extracts the
     * necessary information to send it to the SubmitCatalog API endpoint.
     *
     * @param Varien_Data_Collection $products Products to prepare
     * @return array Prepared array of products and product information
     */
    public function prepareProductsForSubmitCatalog(Varien_Data_Collection $products)
    {
        /** @var Mage_Eav_Model_Entity_Attribute $hsCode */
        $hsCode = Mage::getModel('eav/entity_attribute')->load(Mage::getStoreConfig('catalog_mapping/attributes/hscodeus'));
        /** @var Mage_Eav_Model_Entity_Attribute $shipAlone */
        $shipAlone = Mage::getModel('eav/entity_attribute')->load(Mage::getStoreConfig('catalog_mapping/attributes/shipalone'));

        $items = array();
        $items['key'] = Mage::helper('iparcel')->getGuid();

        $skus = $items['SKUs'] = array();

        foreach ($products as $product) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = Mage::getModel('catalog/product')->load($product->getId());

            $sku = $product->getSku() ?: '';
            $name = $product->getName() ?: '';
            if (empty($sku) || empty($name)) {
                continue;
            }

            $item = array();

            $item['SKU'] = $sku;
            $item['ProductName'] = $name;

            for ($i = 1; $i <= 6; $i++) {
                $_attribute = Mage::getModel('eav/entity_attribute')
                    ->load(Mage::getStoreConfig(sprintf('catalog_mapping/attributes/attribute%d', $i)));

                // if attribute exists
                $productAttribute = null;
                if ($_attribute !== null) {
                    // and has attribute_code
                    if ($code = $_attribute->getAttributeCode()) {
                        // then productAttribute value is product's attribute_text (if exists) or product's data (if not)
                        $productAttribute = strip_tags($product->getAttributeText($code) ?: $product->getData($code));
                    }
                }
                $item["Attribute$i"] = (string)substr($productAttribute, 0, 255);
            }

            $price = null;
            // if it's simple product and config is to get parent's price
            if ($product->getTypeId() == 'simple' && Mage::getStoreConfig('catalog_mapping/attributes/price') == Iparcel_All_Model_System_Config_Source_Catalog_Mapping_Configurable_Price::CONFIGURABLE) {
                // get parentIds
                $parentIds = Mage::getModel('catalog/product_type_grouped')->getParentIdsByChild($product->getId()) ?: Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
                // get price
                $price = $parentIds ? Mage::getModel('catalog/product')->load($parentIds[0])->getPrice() : $product->getPrice();
            }
            // if there's no price
            if (!$price) {
                //get current product's price
                $price = $product->getPrice();
            }

            $item['CountryOfOrigin'] = (string)$product->getCountryOfManufacture();
            $item['CurrentPrice'] = (float)$price;
            $item['Delete'] = $product->getIsDeleted() ? true : false;
            $item['HSCodeCA'] = '';

            if ($code = $hsCode->getAttributeCode()) {
                $item['HSCodeUS'] = trim($product->getAttributeText($code)) ?: $product->getData($code);
            } else {
                $item['HSCodeUS'] = '';
            }

            $item['Height'] = (float)$product->getHeight();
            $item['Length'] = (float)$product->getLength();
            $item['ProductURL'] = $product->getUrlPath();
            $item['SKN'] = '';
            if ($code = $shipAlone->getAttributeCode()) {
                $item['ShipAlone'] = $product->getAttributeText($code) == 'Yes' ? true : false;
            }
            $item['Width'] = (float)$product->getWidth();
            $item['Weight'] = (float)$product->getWeight();

            // Detect and handle a Simple Product with Custom Options
            if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_SIMPLE && $product->getHasOptions()) {
                // loop through each of the sorted products with custom options
                // and build out custom option and option type based skus
                foreach ($this->_findSimpleProductVariants($product) as $customOptionProduct) {
                    $customOptionSku = "";
                    $customOptionPriceMarkup = 0;
                    $customOptionName = "";
                    foreach ($customOptionProduct as $o) {
                        $customOptionSku .= "_" . $o["sku"];
                        $customOptionPriceMarkup += $o["price"];
                        $customOptionName .= " - " . $o["title"];
                    }
                    if ($customOptionSku != '') {
                        $item['SKU'] = $sku . "_" . $customOptionSku;
                    } else {
                        $item['SKU'] = $sku;
                    }
                    $item['CurrentPrice'] = (float)( $price + $customOptionPriceMarkup );
                    //append the product name with the custom option
                    $item['ProductName'] = $name . $customOptionName;
                    $items['SKUs'][] = $item;
                }
            } else {
                $items['SKUs'][] = $item;
            }

        }

        return $items;
    }

    /**
     * Performs the work of handling Simple Products with Custom Options
     *
     * @param Mage_Catalog_Model_Product $product Product with Options
     * @return array Options array with all product variations
     */
    private function _findSimpleProductVariants($product)
    {
        // get product options collection object
        $options = Mage::getModel('catalog/product_option')
            ->getCollection()
            ->addProductToFilter($product->getId())
            ->addPriceToResult(0)
            ->addValuesToResult();

        // array indeces for products and product options
        $optCount = 0;
        $optTypeCount = 0;

        // arrays for sorted products, sorting key/value lookups
        $sorted = array();
        $productOptions = array();
        $productSorting = array();

        foreach ($options as $option) {
            // array for products custom options
            $customOptions = array();
            $optSortOrder = $option["sort_order"];
            $optSortKey = 0;
            /**
             * If this option has no values (text field, text area, etc.) just
             * build a sku for the option ID, otherwise, build out each value as
             * well.
             */
            if (count($option->getValues()) == 0) {
                $productOption = $product->getOptions();
                $productOption = $productOption[$option->getId()];
                $customOption = array();
                $customOption["sku"] = $option->getId();
                $customOption["price"] = $option->getPrice();
                $customOption["title"] = $productOption->getTitle();;
                $customOption["sort_order"] = $option->getSortOrder();
                if (get_class($option) == 'MageWorx_CustomOptions_Model_Catalog_Product_Option') {
                    $customOption['required'] = $option->getIsRequire(true);
                } else {
                    $customOption['required'] = $option->getIsRequire();
                }
                $customOptions[$optTypeCount] = $customOption;
                $optTypeCount++;
            } else {
                foreach ($option->getValues() as $values) {
                    $customOption = array();
                    // create the sku portion with custom option id and option type id.. 1_2 -> Size_Large
                    $customOption["sku"] = $values["option_id"] . "-" . $values["option_type_id"];
                    $customOption["price"] = $values["price"];
                    $customOption["title"] = $values["title"];
                    $customOption["sort_order"] = $values["sort_order"];
                    /**
                     * Add `required` bit. Used later to build SKU variations.
                     * If this store uses Mageworx_CustomOptions, act as if we are
                     * on a product page
                     */
                    if (get_class($option) == 'MageWorx_CustomOptions_Model_Catalog_Product_Option') {
                        $customOption['required'] = $option->getIsRequire(true);
                    } else {
                        $customOption['required'] = $option->getIsRequire();
                    }
                    // add custom option type to collection
                    $customOptions[$optTypeCount] = $customOption;
                    $optTypeCount++;
                }
            }
            // maintain index to sort order relationship
            $productSorting[$optCount] = $optSortOrder;
            // add custom option type collection to options collections
            $customOptionProducts[$optCount] = $customOptions;
            $optCount++;
        }
        // sort by array sort_order value while maintaining array index
        asort($productSorting);
        $sortCount = 0;

        // iterate sorted indeces and build sorted array of products
        foreach ($productSorting as $k => $v) {
            $sorted[$sortCount] = $customOptionProducts[$k];
            $sortCount++;
        }

        return $this->_findVariations($sorted);
    }

    /**
     * Given an array of options, returns all of the valid option variations
     *
     * @param array $options Array of option arrays (sku, price, title, sort_order, required)
     * @return array Array of arrays, representing the possible option variations
     */
    private function _findVariations($options)
    {
        // filter out empty values
        $options = array_filter($options);
        $result = array(array());
        $optionalProductOptions = array();
        $requiredOptions = true;

        // Remove the optional product options
        foreach ($options as $key => $option) {
            foreach ($option as $product) {
                if ($product['required'] == false) {
                    $optionalProductOptions[$key][$product['sku']] = $product;
                    unset($options[$key]);
                }
            }
        }

        // Add all variations of the required options to the $result array
        foreach ($options as $key => $values) {
            $append = array();
            foreach ($result as $product) {
                foreach ($values as $item) {
                    $product[$key] = $item;
                    $append[] = $product;
                }
            }
            $result = $append;
        }

        // Check for any required options
        if (count($result[0]) != 0 || count($result) == 1) {
            $requiredOptions = false;
        }

        // Add a new product to the $result array for each variation of optional
        // product options + the existing required options.
        foreach ($result as $productConfiguration) {
            foreach ($optionalProductOptions as $option) {
                foreach ($option as $product) {
                    $newVariation = $productConfiguration;
                    $newVariation[] = $product;
                    $result[] = $newVariation;
                }
            }
        }

        // If there were no required options, add all variations of optional
        // product options
        if ($requiredOptions == false && count($optionalProductOptions) > 1) {
            $allOptionals = array();
            foreach ($optionalProductOptions as $key => $option) {
                $allOptionals[] = array_shift($option);
            }
            $result[] = $allOptionals;
        }

        return $result;
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
}
