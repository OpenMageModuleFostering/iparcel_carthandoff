<?php
/**
 * i-parcel model for displaying tax total on invoices
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Order_Invoice_Total_Tax extends Mage_Sales_Model_Order_Invoice_Total_Abstract
{
    public function collect(Mage_Sales_Model_Order_Invoice $invoice)
    {
        $order = $invoice->getOrder();
        $taxAmount = $order->getIparcelTaxAmount();
        $baseTaxAmount = $order->getBaseIparcelTaxAmount();

        $invoice->setGrandTotal($invoice->getGrandTotal() + $taxAmount);
        $invoice->setBaseGrandTotal($invoice->getBaseGrandTotal() + $baseTaxAmount);

        $invoice->setIparcelTaxAmount($taxAmount);
        $invoice->setBaseIparcelTaxAmount($baseTaxAmount);
    }
}
