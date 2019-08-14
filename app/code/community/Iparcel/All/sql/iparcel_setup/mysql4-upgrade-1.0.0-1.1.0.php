<?php

$installer = $this;
$installer->startSetup();

// Create a table to store tax & duty calculations
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('iparcel/api_quote')};
CREATE TABLE {$this->getTable('iparcel/api_quote')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `quote_id` varchar(255) NOT NULL,
  `parcel_id` varchar(255) NOT NULL,
  `service_levels` TEXT NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

// Add service levels
$data = array(
    array(
        'service_id' => 112,
        'title' => 'UPS i-Parcel Express'
    ),
    array(
        'service_id' => 115,
        'title' => 'UPS i-Parcel Select'
    ),
    array(
        'service_id' => 119,
        'title' => 'UPS i-Parcel Saver'
    ),
    array(
        'service_id' => 211,
        'title' => 'UPS Standard'
    ),
    array(
        'service_id' => 208,
        'title' => 'UPS Expedited'
    ),
    array(
        'service_id' => 265,
        'title' => 'UPS Saver'
    ),
    array(
        'service_id' => 207,
        'title' => 'UPS Express'
    ),
    array(
        'service_id' => 254,
        'title' => 'UPS Express Plus'
    )
);

Mage::getModel('core/config_data')
    ->setScope('default')
    ->setScopeId(0)
    ->setPath('carriers/iparcel/name')
    ->setValue(serialize($data))
    ->save();

$installer->endSetup();
