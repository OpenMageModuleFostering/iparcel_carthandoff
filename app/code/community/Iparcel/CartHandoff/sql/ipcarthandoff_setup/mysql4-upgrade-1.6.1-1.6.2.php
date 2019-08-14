<?php

$installer = $this;
$installer->startSetup();

// Create table to store TX ID history
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('ipcarthandoff/txid')};
CREATE TABLE {$this->getTable('ipcarthandoff/txid')} (
  `id` int(11) unsigned NOT NULL auto_increment,
  `txid` varchar(255) NOT NULL,
  `processing` boolean NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
