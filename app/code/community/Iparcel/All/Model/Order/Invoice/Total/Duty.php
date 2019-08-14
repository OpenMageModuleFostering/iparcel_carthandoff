<?php
/**
 * i-parcel model for displaying duty total on invoices
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Order_Invoice_Total_Duty extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $dutyAmount = $order->getIparcelDutyAmount();
        $baseDutyAmount = $order->getBaseIparcelDutyAmount();

        $invoice->setGrandTotal($invoice->getGrandTotal() + $dutyAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseDutyAmount);

        $invoice->setIparcelDutyAmount($dutyAmount);
        $invoice->setBaseIparcelDutyAmount($baseDutyAmount);
    }
}
