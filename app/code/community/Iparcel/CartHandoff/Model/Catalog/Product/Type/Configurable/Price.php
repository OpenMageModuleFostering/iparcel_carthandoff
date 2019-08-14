<?php
/**
 * Product Type Price Model for Configurable Products
 *
 * @category    Iparcel
 * @package     Iparcel_CartHandoff
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_CartHandoff_Model_Catalog_Product_Type_Configurable_Price extends Mage_Catalog_Model_Product_Type_Configurable_Price
{
    private $_priceModel = null;

    private function _getPriceModel()
    {
        if (is_null($this->_priceModel)) {
            $this->_priceModel = Mage::getModel('ipcarthandoff/catalog_product_type_price');
        }

        return $this->_priceModel;
    }

    public function getPrice($product)
    {
        return $this->_getPriceModel()->getPrice($product, $this);
    }
}