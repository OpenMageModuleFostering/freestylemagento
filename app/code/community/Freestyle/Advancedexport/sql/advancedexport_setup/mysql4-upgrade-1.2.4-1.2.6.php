<?php

// app/code/community/Freestyle/AdvancedExport/sql/
// >advancedexport_setup/mysql4-upgrade-1.2.3-1.2.4.php

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

} else {
    //conversion process.. copy configuration
}



$installer->endSetup();
