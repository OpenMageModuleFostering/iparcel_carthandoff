<?php
/**
 * Class to reorder Magento's total collectors
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Quote_Address_Total_Collector extends Mage_Sales_Model_Quote_Address_Total_Collector
{
    public function getCollectors()
    {
        if (Mage::getStoreConfig('iparcel/tax/mode') == Iparcel_All_Model_System_Config_Source_Tax_Mode::DISABLED) {
            return parent::getCollectors();
        }

        $collectors = parent::getCollectors();

        $totals = array(
            Mage::getModel('iparcel/payment_iparcel')->getTaxCode(),
            Mage::getModel('iparcel/payment_iparcel')->getDutyCode(),
            'grand_total',
            'reward',
            'giftcardaccount',
            'customerbalance'
        );

        foreach ($totals as $total) {
            $collectors = $this->_moveIndexToEnd($total, $collectors);
        }

        return $collectors;
    }

    /**
     * Move index of array to the end of the array
     *
     * @param mixed $index
     * @param array $array
     * @return array
     */
    protected function _moveIndexToEnd($index, $array)
    {
        if (isset($array[$index])) {
            $temp = $array[$index];
            unset($array[$index]);
            $array[$index] = $temp;
        }

        return $array;
    }
}
