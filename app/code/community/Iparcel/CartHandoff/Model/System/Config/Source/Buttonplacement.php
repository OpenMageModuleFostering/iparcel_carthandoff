<?php
/**
 * Source model for cart_placement option
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_System_Config_Source_Buttonplacement
{
    const TOP = "0";
    const BOTTOM = "1";
    const BOTH = "2";

    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => self::TOP, 'label' => Mage::helper('ipcarthandoff')->__('Top')),
            array('value' => self::BOTTOM, 'label' => Mage::helper('ipcarthandoff')->__('Bottom')),
            array('value' => self::BOTH, 'label' => Mage::helper('ipcarthandoff')->__('Both')),
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
            self::TOP => 'top',
            self::BOTTOM => 'bottom',
            self::BOTH => 'both'
        );
    }

}