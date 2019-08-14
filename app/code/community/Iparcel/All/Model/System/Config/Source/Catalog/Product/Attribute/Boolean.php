<?php
/**
 * Source model class for backend config fields with boolean product atributes
 *
 * @category    Iparcel
 * @package     Iparcel_All
 * @author      Bobby Burden <bburden@i-parcel.com>
 */
class Iparcel_All_Model_System_Config_Source_Catalog_Product_Attribute_Boolean
{
    /**
     * Options list
     *
     * @return array
     */
    public function toOptionArray()
    {
        /** @var Mage_Catalog_Model_Resource_Product_Attribute_Collection $_attributeCollection */
        $_attributeCollection = Mage::getResourceModel('catalog/product_attribute_collection')
            ->addVisibleFilter()
            ->addFieldToFilter('frontend_input', 'boolean');
        $attributeCollection = array(array('value' => 0, 'label' => '<empty>'));

        foreach ($_attributeCollection as $_attribute) {
            $label = $_attribute->getFrontendLabel();
            $attributeCollection[] = array(
                'value' => $_attribute->getAttributeId(),
                'label' => (empty($label)) ? Mage::helper('catalog')->__($_attribute->getAttributeCode()) : Mage::helper('catalog')->__($label)
            );
        }
        return $attributeCollection;
    }
}
