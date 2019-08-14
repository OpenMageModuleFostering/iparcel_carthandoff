<?php
/**
 * Model for "Payment Method" step of checkout
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Payment_Ipcarthandoff extends Iparcel_All_Model_Payment_Iparcel
{
    protected $_code = 'ipcarthandoff';
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    protected $_formBlockType = 'ipcarthandoff/form_button';
    protected $_infoBlockType = 'payment/info';

    public function getCheckoutRedirectUrl()
    {
        return Mage::getUrl('ipcarthandoff/handoff/payment');
    }

    /**
     * Override parent's canUseCheckout to prevent this method on non-iparcel
     * shipping method orders
     *
     * @return bool
     */
    public function canUseCheckout()
    {
        $session = Mage::getSingleton('checkout/session');
        $quote = $session->getQuote();

        // If $quote and $shippingAddress are objects
        if (is_object($quote)) {
            $shippingAddress = $quote->getShippingAddress();
            if (is_object($shippingAddress)) {
                // Check the shipping method to see if it starts with `iparcel`
                if (preg_match('/^iparcel/', $shippingAddress->getShippingMethod())) {
                    return $this->_canUseCheckout;
                }
            }
        }

        return false;
    }

    /**
     * Processes status update from i-parcel
     *
     * Updates the status of a payment (and order) based on the request from
     * i-parcel. Returns string if an error is encountered. Otherwise, returns
     * `true`.
     *
     * @param $post POST message to process
     * @return bool|string
     */
    public function processStatusUpdate($post)
    {
        $order = Mage::helper('ipcarthandoff')->loadOrderByTrackingNumber(
            $post['trackingnumber']
        );

        if ($order == false) {
            return 'Magento order not found.';
        }

        if ($post['status'] == "SUCCESS") {
            // Create and capture invoice
            if ($this->_createInvoice($order)) {
                // Attach comment of successful payment
                $order->addStatusHistoryComment(
                    'Payment processed successfully',
                    Mage_Sales_Model_Order::STATE_COMPLETE
                );
                $order->save();
            }
        } elseif ($post['status'] == "FAILED") {
            // Attach comment from $post['failure_description'] and cancel order
            $order->addStatusHistoryComment(
                'Payment processing failed: ' . $post['failure_description'],
                Mage_Sales_Model_Order::STATE_CANCELED
            );
            $order->cancel();
            $order->save();
        }

        return true;
    }

    /**
     * Creates a paid invoice for the Magento order
     *
     * @param Mage_Sales_Model_Order $order
     */
    private function _createInvoice($order)
    {
        // Verify that the order isn't already paid
        if (!is_null($order->getTotalPaid())) {
            return false;
        }

        $orderItems = $order->getAllItems();
        $itemsQty = array();
        foreach ($orderItems as $item) {
            $itemsQty[$item->getItemId()] = $item->getQtyOrdered();
        }

        $invoice = Mage::getModel('sales/service_order', $order)
            ->prepareInvoice();

        $invoice->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE);
        $invoice->register();

        $transaction= Mage::getModel('core/resource_transaction')
            ->addObject($invoice)
            ->addObject($invoice->getOrder());
        $transaction->save();

        return true;
    }

}