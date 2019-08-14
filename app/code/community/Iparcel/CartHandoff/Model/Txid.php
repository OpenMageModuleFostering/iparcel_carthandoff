<?php
/**
 * Model for storing history of TX IDs used with CartHandoff
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Txid extends Mage_Core_Model_Abstract
{
    /**
     * Initialize resource model
     */
    protected function _construct()
    {
        $this->_init('ipcarthandoff/txid');
    }

    public function loadByTxid($txid) {
        $txid = $this->getCollection()
            ->addFieldToFilter('txid', $txid)
            ->getFirstItem();

        if ($txid->getId()) {
            return $txid;
        }

        return false;
    }
}
