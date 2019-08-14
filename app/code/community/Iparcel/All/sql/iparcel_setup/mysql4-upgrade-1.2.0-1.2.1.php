<?php

$installer = $this;
$installer->startSetup();

$attributeArray = array('width', 'height', 'price', 'weight', 'length');
foreach($attributeArray as $attributeCode) {
    $attributeModel = Mage::getModel('eav/entity_attribute')->loadByCode('catalog_product', $attributeCode);
    if($attributeModel->getData()) {
        Mage::getModel('core/config')->saveConfig('catalog_mapping/attributes/' . $attributeCode, $attributeModel->getAttributeId());
    }
}

$installer->endSetup();
