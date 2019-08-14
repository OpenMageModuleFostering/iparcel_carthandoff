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
            $this->_quote->getStoreId()) && $customer->getId() == null
        ) {
            // Prevent guests from checking out
            $this->_session->setBeforeAuthUrl(
                Mage::getUrl('*/*/*', array('_current' => true))
            );
            $this->getResponse()->setRedirect(Mage::getUrl('customer/account/login'));
            return;
        }

        $apiHelper = Mage::helper('ipcarthandoff/api');
        $transactionNumber = $apiHelper->setCheckout($this->_quote);

        if ($transactionNumber == false) {
            $this->_session->addError("There was a problem communicating to UPS i-parcel");
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
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
        $this->_clearMultiShip($this->_quote);

        $apiHelper = Mage::helper('ipcarthandoff/api');
        $cancelUrl = Mage::getUrl('ipcarthandoff/handoff/cancel');
        $returnUrl = mage::getUrl('ipcarthandoff/handoff/paymentReturn');
        $transactionNumber = $apiHelper->setCheckout($this->_quote, $cancelUrl, $returnUrl);

        if ($transactionNumber == false) {
            $this->_session->addError("There was a problem communicating to UPS i-parcel");
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        }

        // With a transaction ID established, redirect the user to checkout
        $this->getResponse()->setRedirect(
            Mage::helper('ipcarthandoff')->getCheckoutUrl($transactionNumber)
        );

        return;
    }

    /**
     * Handles the user's return from the UPS i-parcel checkout
     */
    public function returnAction()
    {
        $transactionId = $this->getRequest()->getParam('tx');
        $apiHelper = Mage::helper('ipcarthandoff/api');

        $checkoutDetailsResponse = $apiHelper->getCheckoutDetails(
            $transactionId,
            $this->_session
        );

        // Handle error status
        if ($checkoutDetailsResponse->status == 'FAILED'
            || property_exists($checkoutDetailsResponse, 'status') == false
        ) {
            $this->_session->addError($checkoutDetailsResponse->message);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        } else {
            $order = $this->_buildOrder($checkoutDetailsResponse, $this->_session);
        }

        $this->_prepareSuccessPage($order);
        $this->loadLayout();
        Mage::dispatchEvent('checkout_onepage_controller_success_action',
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
        $transactionId = $this->getRequest()->getParam('tx');
        $apiHelper = Mage::helper('ipcarthandoff/api');

        $checkoutDetailsResponse = $apiHelper->getCheckoutDetails(
            $transactionId,
            $this->_session
        );

        // Handle error status
        if ($checkoutDetailsResponse->status == 'FAILED'
            || property_exists($checkoutDetailsResponse, 'status') == false
        ) {
            $this->_session->addError($checkoutDetailsResponse->message);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        } else {
            $order = $this->_buildOrder($checkoutDetailsResponse, $this->_session);
        }


        // Show the order review page
        $this->_prepareSuccessPage($order);
        $this->loadLayout();
        Mage::dispatchEvent('checkout_onepage_controller_success_action',
                            array(
                                'order_ids' => $this->_orderIds
                            )
        );
        $this->renderLayout();
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
     */
    private function _buildOrder($response, $session)
    {
       // Pull the quote ID from the response. Apply it to the current session
        $quoteId = $response->reference_number;
        $quote = Mage::getModel('sales/quote')->load($quoteId);
        $session = $session->replaceQuote($quote);

        try {
            // Make sure the order doesn't exist before attempting
            // to build a new order
            $order = Mage::helper('ipcarthandoff')
                   ->loadOrderByTrackingNumber($response->trackingnumber);

            if ($order == false) {
                $order = Mage::helper('ipcarthandoff/api')->buildOrder(
                    $session->getQuote(), $response
                );
            }
        } catch (Exception $e) {
            Mage::logException($e);
            // This catches any excpetions thrown during order creation.

            $order = $this->_loadAndVerify($response, $quote);

            if ($order == false) {
                /**
                 * If the Magento order cannot be created, or was created incorrectly,
                 * attempt to load the order and cancel it if it exists.
                 */
                $order = Mage::getModel('sales/order');
                $order->loadByAttribute('quote_id', $quote->getId());

                if ($order->getId() != null) {
                    $order->cancel();
                }

                // Should only hit this point if there is no way to recover from
                // errors during order creation
                return $e->getMessage();
            }
        }

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
}