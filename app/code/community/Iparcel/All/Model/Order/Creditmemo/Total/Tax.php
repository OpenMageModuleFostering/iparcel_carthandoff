<?php
/**
 * i-parcel model for displaying tax total on credit memos
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Order_Creditmemo_Total_Tax extends Mage_Sales_Model_Order_Creditmemo_Total_Abstract
{

    public function collect(Mage_Sales_Model_Order_Creditmemo $creditmemo)
    {
        $order = $creditmemo->getOrder();
        $taxAmount = $order->getIparcelTaxAmount();
        $baseTaxAmount = $order->getBaseIparcelTaxAmount();

        $creditmemo->setGrandTotal($creditmemo->getGrandTotal() + $taxAmount);
        $creditmemo->setBaseGrandTotal($creditmemo->getBaseGrandTotal() + $baseTaxAmount);

        $creditmemo->setIparcelTaxAmount($taxAmount);
        $creditmemo->setBaseIparcelTaxAmount($baseTaxAmount);
    }
}
