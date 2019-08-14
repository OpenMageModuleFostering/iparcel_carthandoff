<?php
/**
 * Model to prevent "Zero Subtotal Checkout" on i-parcel orders
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Payment_Free extends Mage_Payment_Model_Method_Free
{
    /**
     * Add an additional check if this is an i-parcel order
     *
     * @return bool
     */
    public function isAvailable($quote = null)
    {
        $paymentModel = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');

        if ($quote && $paymentModel->canUseCheckout()) {
            return false;
        }

        return parent::isAvailable($quote);
    }
}