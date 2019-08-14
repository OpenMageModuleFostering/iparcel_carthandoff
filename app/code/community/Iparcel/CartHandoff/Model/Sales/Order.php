<?php
/**
* Overrides Mage_Sales_Model_Order to control sending new order emails
*
* @category    Iparcel
* @package     Iparcel_CartHandoff
* @author      Bobby Burden <bburden@i-parcel.com>
*/
class Iparcel_CartHandoff_Model_Sales_Order extends Mage_Sales_Model_Order
{
    public function queueNewOrderEmail($forceMode = false)
    {
        if (Mage::getStoreConfig('payment/ipcarthandoff/send_new_order_emails') == 0
            && $this->getPayment()->getMethod() == 'iparcel'
            && $forceMode == false
        ) {
            return $this;
        }

        return parent::queueNewOrderEmail($forceMode);
    }
}