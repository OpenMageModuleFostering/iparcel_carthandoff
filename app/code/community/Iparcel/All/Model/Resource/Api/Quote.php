<?php
/**
 * Resource Model for Iparcel_All_Model_Api_Quote class
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Resource_Api_Quote extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initializing Resource
     */
    protected function _construct()
    {
        $this->_init('iparcel/api_quote', 'id');
    }
}
