<?php
/**
 * Product for i-parcel shipping extension
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Block_Catalog_Product extends Mage_Catalog_Block_Product_View
{
    /**
     * Retrieve qty for product
     *
     * @param Mage_Catalog_Model_Product $product
     * @return float
     */
    public function getStock()
    {
        return Mage::getModel('cataloginventory/stock_item')->loadByProduct($this->getProduct())->getQty();
    }
}
