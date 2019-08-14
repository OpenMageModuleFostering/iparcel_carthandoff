<?php
/**
 * i-parcel frontend scripts block
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Html_Head_Iparcel extends Mage_Core_Block_Template
{
    /**
     * Checking if frontend scripts are enabled
     *
     * @return string
     */
    public function getFlag()
    {
        return Mage::getStoreConfigFlag('iparcel/scripts/scripts');
    }
}
