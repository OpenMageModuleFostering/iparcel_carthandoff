<?php

$installer = $this;
$installer->startSetup();

// Create a table to store tracking_number -> order relationship
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('iparcel/tracking_number')};
CREATE TABLE {$this->getTable('iparcel/tracking_number')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `tracking_number` varchar(255) NOT NULL,
  `order_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
