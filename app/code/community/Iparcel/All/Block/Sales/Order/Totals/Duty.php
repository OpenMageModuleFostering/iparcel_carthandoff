<?php
/**
 * i-parcel model for shipping duty display to frontend order views
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Sales_Order_Totals_Duty extends Iparcel_All_Block_Sales_Order_Totals_Abstract
{
    /**
     * Find and display duty for current order/credit memo
     *
     * @return Iparcel_All_Block_Sales_Order_Totals_Duty
     */
    public function initTotals()
    {
        if ((float) $this->getOrder()->getBaseIparcelDutyAmount()) {
            $source = $this->getSource();
            $value  = $source->getIparcelDutyAmount();

            $this->getParentBlock()->addTotal(new Varien_Object(array(
                'code'   => Mage::getModel('iparcel/payment_iparcel')->getDutyCode(),
                'strong' => false,
                'label'  => Mage::helper('iparcel')->getDutyLabel(),
                'value'  => $value
            )));
        }

        return $this;
    }
}
