<?php
/**
 * Backend model for time minute config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Data_Time_Minute extends Mage_Core_Model_Config_Data
{
    /**
     * Validate before saving
     */
    public function save()
    {
        $_minute = $this->getValue();
        if (is_numeric($_minute) && $_minute<60 && $_minute>=0) {
            return parent::save();
        } else {
            Mage::throwException(Mage::helper('iparcel')->__('Wrong minute'));
        }
    }
}
