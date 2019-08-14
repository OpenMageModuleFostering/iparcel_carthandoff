<?php
/**
 * Backend model for weekday config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Data_Date_Weekday extends Mage_Core_Model_Config_Data
{
    /**
     * Validate before saving
     */
    public function save()
    {
        $_weekday = $this->getValue();
        if (is_numeric($_weekday) && $_weekday<=7 && $_weekday>0) {
            return parent::save();
        } else {
            Mage::throwException(Mage::helper('iparcel')->__('Wrong day of week'));
        }
    }
}
