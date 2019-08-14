<?php

// app/code/community/Freestyle/AdvancedExport/sql/
// >advancedexport_setup/mysql4-upgrade-1.2.6-1.2.9.php

/* @var $installer Mage_Core_Model_Resource_Setup */

$installer = $this;
$installer->startSetup();

// get the version of magento!
$mageVersion = Mage::getVersion();
//$mageEdition = strtoupper(Mage::getEdition());
$enterpriseFolder = Mage::getBaseDir('code').DS.'core'.DS.'Enterprise';
$mageEdition = is_dir($enterpriseFolder) ? "ENTERPRISE" : "COMMUNITY";

if ($mageEdition == 'ENTERPRISE') {
    $runPureSQL = version_compare($mageVersion, '1.12.0.0') <= 0;
} else {
    //we are assuming if you are not enterprise, you are community
    $runPureSQL = version_compare($mageVersion, '1.7.0.0') <= 0;
}

if ($runPureSQL) {
    $installer->run(
        "ALTER TABLE {$this->getTable('advancedexport/queue')} "
        . " ADD COLUMN `scope` VARCHAR(255) NOT NULL COLLATE "
        . "utf8_general_ci COMMENT 'Scope', "
        . " ADD COLUMN `scope_value` VARCHAR(255) NOT NULL COLLATE "
        . "utf8_general_ci COMMENT 'Scope Value';"
    );
    $installer->run(
        "CREATE INDEX IDX_FREESTYLE_ADVANCEDEXPORT_QUEUE_SCOPE_VALUE"
        . " ON {$this->getTable('advancedexport/queue')} (scope_value);"
    );
    $installer->run(
        "UPDATE {$this->getTable('advancedexport/queue')} SET scope='website',"
        . " scope_value='1' WHERE scope_value IS NULL"
    );
} else {
    $tableName = $installer->getTable('advancedexport/queue');

    $table = $installer->getConnection();
    if ($installer->getConnection()->isTableExists($tableName)) {
        $table->addColumn(
            $tableName, 
            'scope', 
            "VARCHAR(255) NOT NULL COLLATE utf8_general_ci DEFAULT 'website'"
        );
        $table->addColumn(
            $tableName, 
            'scope_value', 
            "VARCHAR(255) NOT NULL COLLATE utf8_general_ci DEFAULT '1'"
        );
        $table->addIndex(
            $tableName, 
            $installer->getIdxName(
                $tableName, 
                array('scope_value')
            ), 
            'scope_value'
        );
    }
}
    //convert the current config from default to website
    $installer->run(
        "UPDATE {$this->getTable('core_config_data')} SET `scope` = 'websites',"
        . " `scope_id` = 1 WHERE `path`="
        . "'freestyle_advancedexport/settings/chanel_id' "
        . "AND `scope`='default' AND `scope_id`=0;"
    );

$installer->endSetup();
