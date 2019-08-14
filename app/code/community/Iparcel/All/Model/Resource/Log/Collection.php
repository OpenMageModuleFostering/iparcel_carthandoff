<?php
/**
 * Collection model for log resource
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Resource_Log_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    protected function _construct()
    {
        $this->_init('iparcel/log');
    }

    /**
     * Deletes all items in the collection
     *
     * @return self
     */
    public function deleteAllItems()
    {
        foreach ($this->getItems() as $item) {
            $item->delete();
        }

        return $this;
    }
}
