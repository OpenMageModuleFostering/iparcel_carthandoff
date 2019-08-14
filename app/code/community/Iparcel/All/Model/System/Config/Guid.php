<?php
/** Backend model for i-parcel GUID config field
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Guid extends Mage_Core_Model_Config_Data
{
    /**
     * Validates and saves GUID
     */
    public function save()
    {
        $_guid = $this->getValue();
        if (preg_match('/^[0-9A-Z]{8}-([0-9A-Z]{4}-){3}[0-9A-Z]{12}$/i', $_guid)) {
            return parent::save();
        } else {
            Mage::throwException(Mage::helper('iparcel')->__('Wrong GUID'));
        }
    }
}
