<?php

/**
 * Controller for handling Cart Handoff
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_HandoffController extends Mage_Core_Controller_Front_Action
{
    protected $_session = null;
    protected $_quote = null;
    protected $iparcelLogfile = 'iparcel_carthandoff.log';
    protected $noValidItems = 'NO-VALID-ITEMS';

    /**
     * Grab user's checkout session and quote
     */
    public function preDispatch()
    {
        parent::preDispatch();

        $this->_session = Mage::getSingleton('checkout/session');
        $this->_quote = $this->_session->getQuote();
    }

    /**
     * Start Cart Handoff action, pass user to UPS i-parcel
     */
    public function beginAction()
    {
        // Ensure that the quote is active
        if ($this->_quote->getData('is_active') == false) {
            $this->_session->addError('Please add items to your cart before continuing.');
            $this->_redirect('checkout/cart');
            return;
        }

        $this->_clearMultiShip($this->_quote);
        $customer = $this->_quote->getCustomer();

        if (!Mage::helper('checkout')->isAllowedGuestCheckout(
                $this->_quote,
                $this->_quote->getStoreId()
            ) && $customer->getId() == null
        ) {
            // Prevent guests from checking out
            $this->_session->setBeforeAuthUrl(
                Mage::getUrl('*/*/*', array('_current' => true))
            );
            $this->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
            return;
        }

        $apiHelper = Mage::helper('ipcarthandoff/api');
        $response = $apiHelper->setCheckout($this->_quote);

        $transactionNumber = $response->tx;
        if ($transactionNumber == false) {
            $this->_session->addError("There was a problem communicating to UPS i-parcel");
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        }

        // If the TX returns a No Eligble Items error, report that and return
        // to the cart page.
        if ($transactionNumber == $this->noValidItems) {
            $this->_session->addError("None of the items in your cart are eligible for international shipping.");
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        }

        // Check for InvalidItems in the response
        $invalidItems = $response->InvalidItems;
        if (count($invalidItems)) {
            $invalidSkus = array();
            foreach ($invalidItems as $item) {
                $invalidSkus[$item->item_number] = $item->item_name;
            }

            // If checkout with inelligible items is disabled, add errors to
            // the cart and redirect back to the cart page.
            if (!Mage::getStoreConfig('payment/ipcarthandoff/allow_partial_cart')) {
                foreach ($invalidSkus as $sku => $name) {
                    $this->_session->addError(
                        "'$name' is not available for international shipping"
                    );
                }
                $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                return;
            }
        }

        // Ensure that the CartHandoff payment method is configured for this quote
        $cartHandoffPMC = Mage::helper('ipcarthandoff')->getPaymentMethodCode();
        if ($this->_quote->getPayment()->getMethod() != $cartHandoffPMC) {
            $cartHandoffPM = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');
            $this->_quote->getPayment()->setMethod($cartHandoffPM->getCode());
            $this->_quote->save();
        }

        // With a transaction ID established, redirect the user to checkout
        $this->getResponse()->setRedirect(
            Mage::helper('ipcarthandoff')->getCheckoutUrl($transactionNumber)
        );

        return;
    }

    /**
     * Cancel action used when called as a payment method
     */
    public function cancelAction()
    {
        if ($this->_session->getLastRealOrderId()) {
            $order = Mage::getModel('sales/order')->loadByIncrementId($this->_session->getLastRealOrderId());
            if ($order->getId()) {
                $order->cancel()->save();

                // Restore user's Magento quote
                $quote = Mage::getModel('sales/quote')->load($order->getQuoteId());
                if ($quote->getId()) {
                    $quote->setIsActive(1)
                        ->setReservedOrderId(null)
                        ->save();
                    $this->_session
                        ->replaceQuote($quote)
                        ->unsLastRealOrderId();

                    return true;
                }
            }
        }

        $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        return;
    }


    /**
     * Payment action -- used when the user selects the Cart Handoff payment
     * method in checkout
     */
    public function paymentAction()
    {
        return $this->beginAction();
    }

    /**
     * Handles the user's return from the UPS i-parcel checkout
     */
    public function returnAction()
    {
        $transactionId = $this->getRequest()->getParam('tx');
        $this->logToFile('returnAction(): Processing tx: ' . $transactionId);
        $apiHelper = Mage::helper('ipcarthandoff/api');

        $txid = Mage::getModel('ipcarthandoff/txid')
            ->loadByTxid($transactionId);

        if (is_object($txid)) {
            if ($txid->getProcessing()) {
                $this->logToFile('returnAction(): Waiting on ' . $transactionId . ' to finish processing.');
                // We're still working on this order
                $this->_waitForTxidProcessing($txid);
            }
        } else {
            $txid = Mage::getModel('ipcarthandoff/txid')
                ->setTxid($transactionId);
        }

        $txid->setProcessing(true);
        $txid->save();

        $this->logToFile('returnAction(): Attempting to laod order with Tracking Number ' . $transactionId);
        // Make sure the order doesn't exist before calling GetCheckoutDetails
        $order = Mage::helper('ipcarthandoff')
            ->loadOrderByTrackingNumber($transactionId);

        if ($order == false) {
            $this->logToFile('returnAction(): Calling getCheckoutDetails for ' . $transactionId);
            $checkoutDetailsResponse = $apiHelper->getCheckoutDetails(
                $transactionId,
                $this->_session
            );

            // Handle error status
            if ($checkoutDetailsResponse->status == 'FAILED'
                || property_exists($checkoutDetailsResponse, 'status') == false
            ) {
                $this->logToFile('returnAction(): Error from GetCheckoutDetails on ' . $transactionId . ' -- ' . $checkoutDetailsResponse->message);
                $this->_session->addError($checkoutDetailsResponse->message);
                $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
                $txid->setProcessing(false);
                $txid->save();
                return;
            } else {
                $this->logToFile('returnAction(): Calling _buildOrder for ' . $transactionId);
                $order = $this->_buildOrder(
                    $checkoutDetailsResponse,
                    $this->_session,
                    $txid
                );
            }
        }

        $txid->setProcessing(false);
        $txid->save();

        $this->_prepareSuccessPage($order);
        $this->loadLayout();
        $this->logToFile(
            'returnAction(): Building order review page for Order ID '
            . join(', ', $this->_orderIds)
            . ' and tx: '
            . $transactionId
        );
        Mage::dispatchEvent(
            'checkout_onepage_controller_success_action',
            array(
                'order_ids' => $this->_orderIds
            )
        );
        $this->renderLayout();

        return;
    }

    /**
     * Handles the error checking and setup before processing the return from
     * handoff when used as a payment method
     */
    public function paymentReturnAction()
    {
        return $this->returnAction();
    }

    /**
     * Clears addresses if multiship is enabled
     *
     * @param Mage_Sales_Model_Quote $quote
     * @return bool
     */
    private function _clearMultiShip(Mage_Sales_Model_Quote $quote)
    {
        if ($quote->getIsMultiShipping()) {
            $quote->setIsMultiShipping(false);
            $quote->removeAllAddresses();
        }

        return true;
    }

    /**
     * Prepares the session to display the success page
     *
     * @param object $order
     */
    private function _prepareSuccessPage($order)
    {
        $this->_session->setQuoteId($order->getQuoteId());
        $this->_session->setLastQuoteId($order->getQuoteId());
        $this->_session->setLastSuccessQuoteId($order->getQuoteId());
        $this->_session->getQuote()->setIsActive(false)->save();
        $this->_session->setOrder($order);
        $this->_session->setLastOrderId($order->getId());

        $this->_orderIds = array($order->getId());
    }

    /**
     * Cancels payment with i-parcel. Attaches status message to session.
     *
     * @param string $tx Transaction ID
     * @return bool
     */
    private function _cancelPayment($tx)
    {
        $apiHelper = Mage::helper('ipcarthandoff/api');
        $return = $apiHelper->cancelPayment($tx);

        if ($return) {
            $this->_session->addSuccess("Payment canceled with UPS i-parcel.");
        } else {
            $this->_session->addError("Cannot cancel payment with UPS i-parcel.");
        }

        return true;
    }

    /**
     * Builds order based on response from GetCheckoutDetails API
     *
     * @param object $response Response from the API
     * @param object $session User's session
     * @return mixed Order is returned on success. Error string on failure
     * @internal param object $txid Txid object to keep current status
     */
    private function _buildOrder($response, $session)
    {
        // Pull the quote ID from the response. Apply it to the current session
        $quoteId = $response->reference_number;
        $txId = $response->trackingnumber;
        $this->logToFile(
            '_buildOrder(): Loading quote ID '
            . $quoteId
            . ' for TXID: '
            . $txId
        );
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $session = $session->replaceQuote($quote);

        try {
            $order = Mage::helper('ipcarthandoff/api')->buildOrder(
                $session->getQuote(), $response
            );
        } catch (Exception $e) {
            // This catches any exceptions thrown during order creation.
            Mage::logException($e);

            $this->logToFile(
                '_buildOrder(): Failed to build order for tx ' . $txId
            );

            $this->logToFile(
                '_buildOrder(): Attempting to recover tx ' . $txId
            );
            $order = $this->_loadAndVerify($response, $quote);

            if ($order == false) {
                $this->logToFile(
                    '_buildOrder(): Unable to recover tx ' . $txId
                );

                /**
                 * If the Magento order cannot be created, or was created incorrectly,
                 * attempt to load the order and cancel it if it exists.
                 */
                $order = Mage::getModel('sales/order');
                $order->loadByAttribute('quote_id', $quote->getId());

                if ($order->getId() != null) {
                    $this->logToFile(
                        '_buildOrder(): Canceling Magento Order ID ' . $order->getId()
                    );
                    $order->cancel();
                }

                $this->logToFile(
                    '_buildOrder(): Error message when building order for tx '
                    . $txId
                    . ' : '
                    . $e->getMessage()
                );

                // Should only hit this point if there is no way to recover from
                // errors during order creation
                return $e->getMessage();
            }
        }

        $this->logToFile(
            '_buildOrder(): Successfully built order for tx ' . $txId
        );

        return $order;
    }

    /**
     * Load and Verify an order from GetCheckoutDetails response
     *
     * @param object $response Response from the API
     * @param object $quote Magento quote
     * @return mixed Order object or false
     */
    private function _loadAndVerify($response, $quote)
    {
        // Load the order based on the quote ID
        $quoteId = $quote->getId();
        $order = Mage::getModel('sales/order');

        // Attempt to load the order based on the quote ID
        try {
            $order->loadByAttribute('quote_id', $quoteId);
        } catch (Exception $e) {
            Mage::logException($e);
            return false;
        }

        // Find the grand total of the order in the response
        $responseGrandTotal = 0;
        foreach ($response->ItemDetailsList as $item) {
            $responseGrandTotal += $item->amount;
        }

        $responseGrandTotal = $responseGrandTotal
            + $response->shipping_cost
            + $response->duty
            + $response->tax;
        $responseGrandTotal -= $response->discount_amount_cart;

        // Verify that the order totals match the information in the response.
        $grandTotalsMatch = $order->getBaseGrandTotal() == $responseGrandTotal;
        $shippingTotalsMatch = $order->getBaseShippingAmount() == $response->shipping_cost;
        $taxTotalsMatch = $order->getBaseTaxAmount() == $response->tax;

        // Return the order if totals match
        if ($grandTotalsMatch
            && $shippingTotalsMatch
            && $taxTotalsMatch
        ) {
            return $order;
        }

        return false;
    }

    /**
     * Waits for $txid processing to finish
     *
     * @param $txid
     * @return bool
     */
    public function _waitForTxidProcessing($txid)
    {
        $waitTime = 10;
        if ($txid->getProcessing() == false) {
            return true;
        }

        $id = $txid->getId();
        while ($waitTime > 0) {
            $txid = Mage::getModel('ipcarthandoff/txid')->load($id);

            if ($txid->getProcessing() == false) {
                return true;
            }

            sleep(1);
            $waitTime--;
        }

        $this->_session->addError("Cannot process order.");
        $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));

        return;
    }

    /**
     * Log messages to the IPARCEL_LOGFILE
     *
     * @param string $message
     * @return bool
     */
    public function logToFile($message)
    {
        Mage::log($message, null, $this->iparcelLogfile, true);
        return true;
    }
}
