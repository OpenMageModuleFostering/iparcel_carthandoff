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
        $paymentModel = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');

        if ($paymentModel->canUseCheckout()) {
            $data['method'] = $paymentModel->getCode();
            Mage::register('storecredit_apply_carthandoff', true);
        }

        return parent::savePayment($data);
    }

    /**
     * Remove `amazon_order_reference_id` from the user's session
     *
     * This is necessary so that rates can be returned for non-Amazon Payments
     * checkout methods, but hidden when Amazon sets a reference ID. Amazon will
     * set a reference ID during the address selection step of the Amazon
     * checkout flow.
     *
     * @return this
     */
    public function initCheckout()
    {
        $session = $this->getCheckout();
        $session->unsetData('amazon_order_reference_id');

        return parent::initCheckout();
    }
}