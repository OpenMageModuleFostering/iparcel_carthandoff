<?php
/**
 * Provides support for AheadWorks StoreCredit extension
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
if (@class_exists('AW_Storecredit_Model_Observer_Storecredit')) {
    class Iparcel_CartHandoff_Model_Observer_Storecredit_Parent extends AW_Storecredit_Model_Observer_Storecredit {}
} else {
    class Iparcel_CartHandoff_Model_Observer_Storecredit_Parent {}
}

class Iparcel_CartHandoff_Model_Observer_Storecredit extends Iparcel_CartHandoff_Model_Observer_Storecredit_Parent
{
    public function paymentDataImport(Varien_Event_Observer $observer)
    {
        parent::paymentDataImport($observer);

        $input = $observer->getEvent()->getInput();
        $payment = $observer->getEvent()->getPayment();

        if (Mage::registry('storecredit_apply_carthandoff') == true) {
            $paymentModel = Mage::getModel('ipcarthandoff/payment_ipcarthandoff');
            $input->setMethod($paymentModel->getCode());
            $payment->setMethod($paymentModel->getCode());
        }
    }
}