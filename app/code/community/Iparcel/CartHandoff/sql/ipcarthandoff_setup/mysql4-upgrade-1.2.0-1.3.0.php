<?php

$installer = $this;
$installer->startSetup();

// Create table to store check items cache
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('ipcarthandoff/checkitems')};
CREATE TABLE {$this->getTable('ipcarthandoff/checkitems')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `created_at` datetime default NULL,
  `updated_at` datetime default NULL,
  `sku` varchar(255) NOT NULL,
  `country` varchar(3) NOT NULL,
  `store_id` int(11) unsigned NOT NULL,
  `price` DECIMAL(12,4) unsigned DEFAULT NULL,
  `eligible` boolean NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
