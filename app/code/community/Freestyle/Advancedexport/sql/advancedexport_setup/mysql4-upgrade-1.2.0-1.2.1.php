<?php

// app/code/community/Freestyle/AdvancedExport/sql/
// >advancedexport_setup/mysql4-1.2.0-1.2.1.php

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

//moved IF NOT EXISTS next to CREATE TABLE for CE 1.6.1.0 Compatibility
if ($runPureSQL) {
    $installer->run(
        "CREATE TABLE IF NOT EXISTS {$this->getTable('advancedexport/queue')} (
        `id` int(10) unsigned NOT NULL AUTO_INCREMENT COMMENT 'ID',
        `entity_id` varchar(255) NOT NULL,
        `entity_type` varchar(255) NOT NULL DEFAULT '' COMMENT 
        'Magento Entity Type',
        `action` varchar(255) NOT NULL DEFAULT '' COMMENT 'Action Type',
        `create_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 
        'Created At',
        `update_time` datetime DEFAULT '0000-00-00 00:00:00' COMMENT 
        'Modified At',
        `status` varchar(255) NOT NULL DEFAULT '' COMMENT 'Status Description',
        PRIMARY KEY (`id`),
        KEY `IDX_FREESTYLE_ADVANCEDEXPORT_QUEUE_ENTITY_TYPE` (`entity_type`),
        KEY `IDX_FREESTYLE_ADVANCEDEXPORT_QUEUE_STATUS` (`status`),
        KEY `IDX_FREESTYLE_ADVANCEDEXPORT_QUEUE_ENTITY_ID` (`entity_id`)
        ) ENGINE=InnoDB AUTO_INCREMENT=114 DEFAULT CHARSET=utf8 COMMENT=
        'Freestyle Message Queue'"
    );
} else {
    $tableName = $installer->getTable('advancedexport/queue');
    //create the message queue table
    if (!$installer->getConnection()->isTableExists($tableName)) {
        $table = $installer->getConnection()->newTable($tableName)
                ->addColumn(
                    'id', 
                    Varien_Db_Ddl_Table::TYPE_INTEGER, 
                    null, 
                    array(
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true,
                        'identity' => true,
                    ), 
                    'ID'
                )
                ->addColumn(
                    'entity_id', 
                    Varien_Db_Ddl_Table::TYPE_INTEGER, 
                    null, 
                    array(
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => false,
                        'identity' => false,
                    ), 
                    'Magento Entity ID'
                )
                ->addColumn(
                    'entity_type', 
                    Varien_Db_Ddl_Table::TYPE_TEXT, 
                    '255', 
                    array(
                        'nullable' => false,
                        'default' => '',
                    ), 
                    'Magento Entity Type'
                )
                ->addColumn(
                    'action', 
                    Varien_Db_Ddl_Table::TYPE_TEXT, 
                    '255', 
                    array(
                        'nullable' => false,
                        'default' => '',
                    ), 
                    'Action Type'
                )
                ->addColumn(
                    'create_time', 
                    Varien_Db_Ddl_Table::TYPE_DATETIME, 
                    null, 
                    array(
                        'nullable' => true,
                        'default' => $installer
                            ->getConnection()
                            ->getSuggestedZeroDate(),
                    ), 
                    'Created At'
                )
                ->addColumn(
                    'update_time', 
                    Varien_Db_Ddl_Table::TYPE_DATETIME, 
                    null, 
                    array(
                        'nullable' => true,
                        'default' => $installer
                                ->getConnection()
                                ->getSuggestedZeroDate(),
                    ), 
                    'Modified At'
                )
                ->addColumn(
                    'status', 
                    Varien_Db_Ddl_Table::TYPE_TEXT, 
                    '255', 
                    array(
                        'nullable' => false,
                        'default' => '',
                        ), 
                    'Status Description'
                )
                ->addIndex(
                    $installer->getIdxName(
                        $tableName, 
                        array('entity_type')
                    ), 
                    array('entity_type'), 
                    array(
                        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
                    )
                )
                ->addIndex(
                    $installer->getIdxName(
                        $tableName, 
                        array('status')
                    ), 
                    array('status'), 
                    array(
                        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
                    )
                )
                ->addIndex(
                    $installer->getIdxName(
                        $tableName, 
                        array('entity_id')
                    ), 
                    array('entity_id'), 
                    array(
                        'type' => Varien_Db_Adapter_Interface::INDEX_TYPE_INDEX
                    )
                )
                ->setComment('Freestyle Message Queue');

        $installer->getConnection()->createTable($table);
    }
}
$installer->endSetup();
