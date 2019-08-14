<?php
/**
 * Source model for CheckItems cache lifetime option
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_System_Config_Source_Checkitems_Cache
{
    const DAY = "0";
    const WEEK = "1";
    const MONTH = "2";

    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::DAY, 'label' => Mage::helper('ipcarthandoff')->__('One day')),
            array('value' => self::WEEK, 'label' => Mage::helper('ipcarthandoff')->__('One week')),
            array('value' => self::MONTH, 'label' => Mage::helper('ipcarthandoff')->__('One month')),
        );
    }

    /**
     * Returns array of option values
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            self::DAY => 'day',
            self::WEEK => 'week',
            self::MONTH => 'month'
        );
    }

}