<?php
/**
 * i-parcel frontend ajax controller
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_AjaxController extends Mage_Core_Controller_Front_Action
{
    /**
     * Configurable products action for post script
     */
    public function configurableAction()
    {
        $sku = $this->getRequest()->getParam('sku');
        $super_attribute = $this->getRequest()->getParam('super_attribute');
        $customOptions = $this->getRequest()->getParam('options');
        // var $product Mage_Catalog_Model_Product
        $product = Mage::getModel('catalog/product')->loadByAttribute('sku', $sku);

        // Return an empty response if no product matches the given SKU
        if ($product == false) {
            return;
        }

        /**
         * If we are dealing with a simple product, we just need to worry about
         * custom options.
         */
        if ($product->getTypeId() == "configurable") {
            // var $child Mage_Catalog_Model_Product
            $child = Mage::getModel('catalog/product_type_configurable')->getProductByAttributes($super_attribute, $product);

            // var $typeInstance Mage_Catalog_Model_Product_Type_Abstract
            $typeInstance = $product->getTypeInstance(true);
            if (!$typeInstance instanceof Mage_Catalog_Model_Product_Type_Configurable) {
                return;
            }

            $attributes = $product->getTypeInstance(true)->getConfigurableAttributes($product);
            // var $attributes array
            $options = array();
            foreach ($attributes as $attribute) {
                $id = $attribute->getAttributeId();
                foreach ($attribute->getPrices() as $value) {
                    if ($value['value_index'] == $super_attribute[$id]) {
                        $options[$attribute->getProductAttribute()->getAttributeCode()] = $value['label'];
                        break;
                    }
                }
            }

            if (is_null($child) == false) {
                $sku = $child->getSku();
            }

        } else {
            /**
             * Attempt to load the simple product with the custom options set
             * to match the user's input. If a product is found with this
             * configuration, use the SKU formed by adding the option IDs to the
             * product's SKU.
             */
            $child = Mage::getModel('catalog/product')->load($product->getId());
            $optionIds = array();
            $options = array();
            foreach ($child->getOptions() as $option) {
                $id = $option->getId();
                if (array_key_exists($id, $customOptions)) {
                    if ($customOptions[$id] !== "") {
                        $options[$option->getTitle()] = $customOptions[$id];
                        $optionIds[] = $id;
                    }
                }
            }

            // If valid options are filled, at their ID to the SKU
            if (count($optionIds)) {
                $sku = $child->getSKU() . '__' . join('_', $optionIds);
            }
        }

        if ($child) {
            $this->getResponse()
                ->clearHeaders()
                ->setHeader('Content-Type', 'application/json')
                ->setBody(
                    json_encode(array(
                            'sku' => $sku,
                            'attributes' => $options,
                            'stock' => Mage::getModel('cataloginventory/stock_item')->loadByProduct($child)->getQty()
                        )
                    )
                );
        }
    }
}
