<?php
/**
 * Backend model for time hour config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Data_Time_Hour extends Mage_Core_Model_Config_Data
{
    /**
     * Validate before saving
     */
    public function save()
    {
        $_hour = $this->getValue();
        if (is_numeric($_hour) && $_hour<24 && $_hour>=0) {
            return parent::save();
        } else {
            Mage::throwException(Mage::helper('iparcel')->__('Wrong hour'));
        }
    }
}
