<?php
/**
 * Overrides Onepage to make sure CartHandoff is used for applicable orders
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Checkout_Type_Onepage extends Mage_Checkout_Model_Type_Onepage
{
    /**
     * If this is a CartHandoff order, make sure we set our payment method
     *
     * @param array $data
     * @return array
     */
    public function savePayment($data)
    {
        $quote = $this->getQuote();
        $paymentModel = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');

        if ($paymentModel->canUseCheckout()) {
            $data['method'] = $paymentModel->getCode();
            Mage::register('storecredit_apply_carthandoff', true);
        }

        return parent::savePayment($data);
    }
}