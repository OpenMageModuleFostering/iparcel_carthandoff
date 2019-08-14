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
    $dutyCode = Mage::getModel('iparcel/payment_iparcel')->getDutyCode();
    $taxCode = Mage::getModel('iparcel/payment_iparcel')->getTaxCode();

    $installer->addAttribute($entity, 'base_' . $dutyCode . '_amount', $options);
    $installer->addAttribute($entity, $dutyCode . '_amount', $options);
    $installer->addAttribute($entity, 'base_' . $taxCode . '_amount', $options);
    $installer->addAttribute($entity, $taxCode . '_amount', $options);
}

$installer->endSetup();
