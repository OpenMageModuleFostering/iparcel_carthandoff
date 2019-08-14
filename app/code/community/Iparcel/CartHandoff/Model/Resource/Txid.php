<?php
/**
 * Resource Model for Iparcel_Carthandoff_Model_Txid
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_Carthandoff_Model_Resource_Txid
    extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Initializing Resource
     */
    protected function _construct()
    {
        $this->_init('ipcarthandoff/txid', 'id');
    }
}
