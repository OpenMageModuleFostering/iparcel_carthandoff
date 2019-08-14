<?php
/**
 * Resource Model for Iparcel_CartHandoff_Model_Checkitems class
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_Carthandoff_Model_Resource_Checkitems extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initializing Resource
     */
    protected function _construct()
    {
        $this->_init('ipcarthandoff/checkitems', 'id');
    }
}
