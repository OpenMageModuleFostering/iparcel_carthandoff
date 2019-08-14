<?php
/**
 * Resource Collection Model for Iparcel_CartHandoff_Model_Checkitems class
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Resource_Checkitems_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Initializing Resource Collection
     */
    public function _construct()
    {
        $this->_init('ipcarthandoff/checkitems');
    }
}