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
        if ($checkoutDetailsResponse->status == 0) {
            $this->_session->addError($checkoutDetailsResponse->message);
            $this->_cancelPayment($transactionId);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
        }

        $this->_prepareSuccessPage($checkoutDetailsResponse);
        $this->loadLayout();
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

        /**
         * Make sure the quote is active, the customer matches the quote,
         * and the payment method is 'ipcarthandoff'
         */
        if (!$this->_quote->getIsActive()
            || $this->_quote->getPayment()->getMethod() != 'ipcarthandoff'
        ) {
            $this->_session->addError("Invalid quote for Cart Handoff review.");
            $this->_cancelPayment($transactionId);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        }

        $checkoutDetailsResponse = $apiHelper->getCheckoutDetails(
            $transactionId,
            $this->_session
        );

        // Handle error status
        if ($checkoutDetailsResponse->status == 0) {
            $this->_session->addError($checkoutDetailsResponse->message);
            $this->_cancelPayment($transactionId);
            $this->getResponse()->setRedirect(Mage::getUrl('checkout/cart'));
            return;
        }

        // Show the order review page
        $this->_prepareSuccessPage($checkoutDetailsResponse);
        $this->loadLayout();
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
     * @param array $checkoutDetailsResponse
     */
    private function _prepareSuccessPage($checkoutDetailsResponse)
    {
        $order = $checkoutDetailsResponse->order;
        $order = Mage::getModel('sales/order')->loadByIncrementId($order->getIncrementId());
        $this->_session->setQuoteId($order->getQuoteId());
        $this->_session->setLastSuccessQuoteId($order->getQuoteId());
        $this->_session->getQuote()->setIsActive(false)->save();
        $this->_session->setOrder($order);
        $this->_session->setLastOrderId($order->getId());
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
}