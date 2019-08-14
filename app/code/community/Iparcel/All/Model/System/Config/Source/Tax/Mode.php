<?php
/**
 * Source model for iparcel/tax/mode config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Source_Tax_Mode
{
    const DISABLED = "0";
    const CUMULATIVELY = "1";
    const SEPARATELY = "2";

    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::DISABLED, 'label' => Mage::helper('iparcel')->__('Disabled')),
            array('value' => self::CUMULATIVELY, 'label' => Mage::helper('iparcel')->__('Enabled - Tax and Duty cumulatively')),
            array('value' => self::SEPARATELY, 'label' => Mage::helper('iparcel')->__('Enabled - Tax and Duty separately'))
        );
    }
}
