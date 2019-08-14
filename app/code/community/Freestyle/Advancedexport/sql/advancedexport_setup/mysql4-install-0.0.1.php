<?php

$installer = $this;
$installer->startSetup();

$installer->run(
    "DROP TABLE IF EXISTS {$this->getTable('advancedexport/history')};
    CREATE TABLE {$this->getTable('advancedexport/history')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `export_date` datetime NOT NULL,
		`export_date_time_start` datetime NOT NULL,
		`export_date_time_end` datetime NOT NULL,
		`created_files` text,
		`init_from` varchar(255),
		`export_entity` varchar(30),
		`errors` text,
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('advancedexport/passivemode')};
    CREATE TABLE {$this->getTable('advancedexport/passivemode')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `passivemod_enabled` int(2) NOT NULL,
		`passivemod_start` datetime NOT NULL,
		`passivemod_end` datetime NOT NULL,
		`created_files` LONGTEXT,
		`is_notification_sent` int(2),
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;

	DROP TABLE IF EXISTS {$this->getTable('advancedexport/configuration')};
    CREATE TABLE {$this->getTable('advancedexport/configuration')} (
        `id` int(11) unsigned NOT NULL auto_increment,
        `config_code` varchar(50) NOT NULL,
		`config_value` varchar(255) NOT NULL,
        PRIMARY KEY  (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;"
);

$installer->endSetup();
