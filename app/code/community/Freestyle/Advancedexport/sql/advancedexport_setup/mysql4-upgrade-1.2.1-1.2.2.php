<?php

// app/code/community/Freestyle/Advancedexport/sql/
// >advancedexport_setup/mysql4-upgrade-1.2.1-1.2.2.php

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
        "ALTER TABLE {$this->getTable('advancedexport/queue')} MODIFY COLUMN"
        . " `entity_id` VARCHAR(255) NOT NULL COLLATE utf8_general_ci;"
        . "ALTER TABLE {$this->getTable('advancedexport/queue')}"
        . " ADD COLUMN `error_msg` VARCHAR(255) NOT NULL COLLATE "
        . "utf8_general_ci,"
        . " ADD COLUMN `entity_value` VARCHAR(255) NOT NULL COLLATE "
        . "utf8_general_ci;"
    );
} else {
    $tableName = $installer->getTable('advancedexport/queue');

    $table = $installer->getConnection();
    if ($installer->getConnection()->isTableExists($tableName)) {
        $table->modifyColumn(
            $tableName, 
            'entity_id', 
            'VARCHAR(255) NOT NULL COLLATE utf8_general_ci'
        );
        $table->addColumn(
            $tableName, 
            'error_msg', 
            'VARCHAR(255) NOT NULL COLLATE utf8_general_ci'
        );
        $table->addColumn(
            $tableName, 
            'entity_value', 
            'VARCHAR(255) NOT NULL COLLATE utf8_general_ci'
        );
    }
}

$installer->endSetup();
