<?php
/**
 * i-parcel model for displaying duty total on credit memos
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Order_Creditmemo_Total_Duty extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $dutyAmount = $order->getIparcelDutyAmount();
        $baseDutyAmount = $order->getBaseIparcelDutyAmount();

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $dutyAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseDutyAmount);

        $creditmemo->setIparcelDutyAmount($dutyAmount);
        $creditmemo->setBaseIparcelDutyAmount($baseDutyAmount);
    }
}
