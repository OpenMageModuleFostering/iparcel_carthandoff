<?php
/**
 * i-parcel XML log model
 *
 * @category Iparcel
 * @package  Iparcel_All
 * @author   Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Log extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('iparcel/log');
    }
}
