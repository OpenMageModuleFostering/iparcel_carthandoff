<?php

$installer = $this;
$installer->startSetup();

// Setup log table
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('iparcel/log')};
CREATE TABLE {$this->getTable('iparcel/log')} (
`id` int(11) unsigned NOT NULL auto_increment,
`created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
`controller` varchar(255) NOT NULL,
`request` TEXT NOT NULL,
`response` TEXT NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
");

$installer->endSetup();
