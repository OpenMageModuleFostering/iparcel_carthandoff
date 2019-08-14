<?php
/**
 * Catalog_Product observer class
 *
 * @category    Iparcel
 * @package     Iparcel_GlobaleCommerce
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_Catalog_Product_Observer
{
    /**
     * Checking if catalog upload on product save is enabled
     *
     * @return bool
     */
    protected function _isEnabled()
    {
        return Mage::getStoreConfig('catalog_mapping/config/auto_upload') == Iparcel_All_Model_System_Config_Source_Catalog_Mapping_Mode::ON_UPDATE;
    }

    /**
     * catalog_product_save_after event handler
     */
    public function product_save($observer)
    {
        if ($this->_isEnabled()) {
            $product = $observer->getProduct();
            $productCollection = new Varien_Data_Collection();
            $productCollection->addItem($product);
            Mage::helper('iparcel/api')->submitCatalog($productCollection);
        }
    }

    /**
     * catalog_product_attribute_update_before event handler
     */
    public function product_massUpdate($observer)
    {
        if ($this->_isEnabled()) {
            $productIds = $observer->getProductIds();
            $attributesData = $observer->getAttributesData();
            $productCollection = new Varien_Data_Collection();
            foreach ($productIds as $id) {
                $product = Mage::getModel('catalog/product')->load($id);
                foreach ($attributesData as $code => $value) {
                    $product->setData($code, $value);
                }
                $productCollection->addItem($product);
            }
            Mage::helper('iparcel/api')->submitCatalog($productCollection);
        }
    }

    /**
     * catalog_product_delete_before event handler
     */
    public function product_delete($observer)
    {
        if ($this->_isEnabled()) {
            $product = $observer->getProduct();
            $product->setIsDeleted(true);
            $productCollection = new Varien_Data_Collection();
            $productCollection->addItem($product);
            Mage::helper('iparcel/api')->submitCatalog($productCollection);
        }
    }
}
