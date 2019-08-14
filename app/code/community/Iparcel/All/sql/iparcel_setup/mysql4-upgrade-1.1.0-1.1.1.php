<?php

$installer = new Mage_Sales_Model_Resource_Setup('core_setup');

// Setup attributes for handling tax and duty for i-parcel shipped orders
$entities = array(
    'quote_address',
    'order',
    'invoice',
    'creditmemo'
);
$options = array(
    'type'     => Varien_Db_Ddl_Table::TYPE_DECIMAL,
    'visible'  => true,
    'required' => false
);

foreach ($entities as $entity) {
    $installer->addAttribute($entity, 'base_iparcel_duty_amount', $options);
    $installer->addAttribute($entity, 'iparcel_duty_amount', $options);
    $installer->addAttribute($entity, 'base_iparcel_tax_amount', $options);
    $installer->addAttribute($entity, 'iparcel_tax_amount', $options);
}

$installer->endSetup();
