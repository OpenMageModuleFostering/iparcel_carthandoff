<?php
/**
 * Perform CheckItems call on layered navigation collection
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Catalog_Layer extends Mage_Catalog_Model_Layer
{
    public function getProductCollection()
    {
        if (isset($this->_productCollections[$this->getCurrentCategory()->getId()])) {
            $collection = $this->_productCollections[$this->getCurrentCategory()->getId()];
        } else {
            $collection = $this->getCurrentCategory()->getProductCollection();
            $this->prepareProductCollection($collection);

            // If "checkItems" is enabled, call the CartHandoff API model to
            // check the items in the product collection
            if (Mage::getStoreConfig('iparcel/international_customer/checkitems')) {
                $handoffApi = Mage::helper('ipcarthandoff/api');
                if (is_object($handoffApi)) {
                    $handoffApi->checkItems($collection);
                }
            }

            $this->_productCollections[$this->getCurrentCategory()->getId()] = $collection;
        }

        return $collection;
    }
}
