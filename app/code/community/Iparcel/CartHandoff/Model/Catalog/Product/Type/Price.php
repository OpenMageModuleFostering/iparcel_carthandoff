<?php
/**
 * Product Type Price Model
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Catalog_Product_Type_Price extends Mage_Catalog_Model_Product_Type_Price
{
    public function getPrice($product, $model = null)
    {
        if (is_null($model)) {
            $model = $this;
        }

        if (Mage::getStoreConfig('iparcel/international_customer/checkitems_price')) {
            $checkItemsPrice = $this->getCheckItemsPrice($product);
            if (!is_null($checkItemsPrice)) {
                return $checkItemsPrice;
            }
        }

        // Load the parent class based on the class passed to this method.
        // This is used so that this method doesn't have to be re-implemented
        // in the Configurable/Price class
        $parentClass = get_parent_class($model);
        $parent = new $parentClass;
        return $parent->getPrice($product);
    }

    public function getCheckItemsPrice($product)
    {
        return Mage::getModel('ipcarthandoff/checkitems')->getCachedPrice($product);
    }
}