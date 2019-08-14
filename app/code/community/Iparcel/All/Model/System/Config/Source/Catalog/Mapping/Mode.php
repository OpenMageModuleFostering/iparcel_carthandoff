<?php
/**
 * Source model for catalog_mapping/config/auto_upload config field
 *
 * @category    Iparcel
 * @package     Iparcel_Al
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Source_Catalog_Mapping_Mode
{
    const DISABLED = "0";
    const ON_UPDATE = "1";
    const CRON = "2";

    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::DISABLED, 'label' => Mage::helper('iparcel')->__('Disabled')),
            array('value' => self::ON_UPDATE, 'label' => Mage::helper('iparcel')->__('On product save')),
            array('value' => self::CRON, 'label' => Mage::helper('iparcel')->__('Cron job'))
        );
    }
}
