<?php
/**
 * i-parcel jQuery block
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Html_Head_Jquery extends Mage_Core_Block_Template
{
    /**
     * Checking if i-parcel's jQuery is enabled
     *
     * @return string
     */
    public function getFlag()
    {
        return Mage::getStoreConfigFlag('iparcel/scripts/jquery');
    }
}
