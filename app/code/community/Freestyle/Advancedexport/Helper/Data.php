<?php

/************************************************************************
  © 2013,2014, 2015 Freestyle Solutions.   All rights reserved.
  FREESTYLE SOLUTIONS, DYDACOMP, FREESTYLE COMMERCE, and all related logos 
  and designs are trademarks of Freestyle Solutions (formerly known as Dydacomp)
  or its affiliates.
  All other product and company names mentioned herein are used for
  identification purposes only, and may be trademarks of
  their respective companies.
************************************************************************/

class Freestyle_Advancedexport_Helper_Data extends Mage_Core_Helper_Abstract
{

    const ADVANCED_EXPORT_FOLDER = 'freestyleexport';

    public function getIsExtEnabledForApi()
    {
        $classDesc = 'freestyle_advancedexport/settings/is_enabled';
        return Mage::getStoreConfig($classDesc);
    }

    public function getExportfolder()
    {
        return self::ADVANCED_EXPORT_FOLDER;
    }

    public function getChanelId($websiteId = 1)
    {
        $classDesc = 'freestyle_advancedexport/settings/chanel_id';
        return trim(Mage::app()->getWebsite($websiteId)->getConfig($classDesc));
    }

    public function getChanelName()
    {
        $classDesc = 'freestyle_advancedexport/settings/chanel_name';
        return Mage::getStoreConfig($classDesc);
    }

    public function getMemoryLimit()
    {
        $classDesc = 'freestyle_advancedexport/settings/memory_limit';
        return Mage::getStoreConfig($classDesc);
    }

    public function getApiUserName()
    {
        $classDesc = 'freestyle_advancedexport/api/api_username';
        return Mage::getStoreConfig($classDesc);
    }

    public function getIsFileExist($fileName)
    {
        $fileFullPath = Mage::getBaseDir() . DS . $this->getExportfolder() 
            . '/' . $fileName;
        if (file_exists($fileFullPath)) {
            return true;
        }
        return false;
    }

    public function getApiUserPassword()
    {
        $classDesc = 'freestyle_advancedexport/api/api_password';
        $value = Mage::getStoreConfig($classDesc);
        $decrypt = Mage::helper('core')->decrypt($value);
        return $decrypt;
    }

    public function getApiAuthenticationUrl()
    {
        $classDesc = 'freestyle_advancedexport/api/api_authorization_url';
        return Mage::getStoreConfig($classDesc);
    }

    public function getApiNotificationUrl()
    {
        $classDesc = 'freestyle_advancedexport/api/api_service_url';
        return Mage::getStoreConfig($classDesc);
    }

    //DE-10071
    public function getOrderCutOffDate()
    {
        $classDesc = 'freestyle_advancedexport/settings/cutoff_date';
        $dateString = Mage::getStoreConfig($classDesc);
        if (is_null($dateString) || empty($dateString)) {
            return "1980-01-01 23:59:59";
        } else {
            $configDate = trim($dateString) . ":00"; //add millliseconds.  
                                                     //all times in UTC
            return $configDate;
        }
    }

    public function putFile($entityToExport, $outxml, $batchNumber, $date)
    {
        $fileName = $this->getChanelId() . '_' . $entityToExport . '_' 
            . $date . '_' . 'batch-' . $batchNumber . '.xml';

        $uploadFolder = Mage::getBaseDir() . DS . $this->getExportfolder();
        $fullPath = $uploadFolder . DS . $fileName;

        if (!is_dir($uploadFolder)) {
            mkdir($uploadFolder, 0777);
        }
        $fileResult = file_put_contents($fullPath, $outxml);

        if ($fileResult) {
            $zip = new ZipArchive();
            $zip->open($fullPath . '.zip', ZIPARCHIVE::CREATE);
            $zip->addFile($fullPath, $fullPath);
            $zip->close();

            //Remove XML FILE AFTER ZIP CREATING
            //----------------------------------
        }
        if ($fileResult) {
            return $fileName;
        } else {
            return false;
        }
    }

    public function isEnterprise()
    {
        $enterpriseFolder = 
            Mage::getBaseDir('code') . DS . 'core' . DS . 'Enterprise';
        return is_dir($enterpriseFolder) ? "Enterprise" : "Community";
    }

    public function getXmlVersionHeader()
    {
        return '<?xml version="1.0"?>';
    }

    public function getMainXmlTagWithParams()
    {
        $xmlString = 'data xmlns:xsi='
                . '"http://www.w3.org/2001/XMLSchema-instance"'
                . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema"';
        return $xmlString;
    }

    public function getMainXmlTagEnd()
    {
        return 'data';
    }

    public function addToOutCollector($entityToExport, $outXml, $fileFullPath)
    {
        unset($entityToExport);
        if (!is_file($fileFullPath)) {
            //$this->openXmlHeaderTag($entityToExport, $fileFullPath);
            $this->openXmlHeaderTag($fileFullPath);
            $fileResult = 
                file_put_contents($fileFullPath, $outXml, FILE_APPEND);
        } else {
            $fileResult = 
                file_put_contents($fileFullPath, $outXml, FILE_APPEND);
        }
    }

    //public function openXmlHeaderTag($entityToExport, $fileFullPath) {
    public function openXmlHeaderTag($fileFullPath)
    {
        $entityToExport = $this->getMainXmlTagWithParams();
        file_put_contents($fileFullPath, $this->getXmlVersionHeader());
        file_put_contents($fileFullPath, "<$entityToExport>", FILE_APPEND);
    }

    //public function closeXmlHeaderTag($entityToExport, $fileFullPath) {
    public function closeXmlHeaderTag($fileFullPath)
    {
        $entityToExport = $this->getMainXmlTagEnd();
        file_put_contents($fileFullPath, "</$entityToExport>", FILE_APPEND);
    }

    public function getParamsPriority($dateS, $dateE, $ids)
    {
        $tempDateEnd = $dateE . ' ' . '23:59:59';
        //$tempDateEnd = $dateE;
        if (strlen(trim($ids))) {
            return array('param' => 'ids', 'values' => $ids);
        }
        if ($dateS || $dateE) {
            return array(
                'param' => 'date', 
                'startDate' => $dateS, 
                'endDate' => $tempDateEnd
            );
        }
        
        return array('param' => 'all');
    }

    public function getEntityModel($entityToExport)
    {
        switch ($entityToExport) {

            case 'customer':
                $model = Mage::getModel('advancedexport/exportmodels_customer');
                break;
            case 'category':
                $model = Mage::getModel('advancedexport/exportmodels_category');
                break;
            case 'order':
                $model = Mage::getModel('advancedexport/exportmodels_order');
                break;
            case 'product':
                $model = Mage::getModel('advancedexport/exportmodels_product');
                break;
            case 'customergroup':
                $classDesc = 'advancedexport/exportmodels_customergroup';
                $model = Mage::getModel($classDesc);
                break;

            default:
                $model = false;
                break;
        }

        return $model;
    }

    public function processPassiveMode($action)
    {
        $errors = array();
        $status = 'success';
        $ids = 0;
        switch ($action) {

            case 'set_to_passive' : {

                    $passiveEnabled = 
                        Mage::getModel('advancedexport/passivemode')
                            ->getCollection()
                            ->addFieldToFilter(
                                'passivemod_enabled', array('eq' => '1')
                            );
                    if ($passiveEnabled->count()) {
                        $outStrErr = '';
                        foreach ($passiveEnabled as $one) {
                            $outStrErr .= $one->getId() . '; ';
                        }
                        $errors[] = 'Not all of the previously enabled passive'
                                . ' modes have been disabled. Identifiers: ' 
                                . $outStrErr;
                        Mage::log(
                            '[WARNING] - Not all of the previously '
                            . 'enabled passive modes have been disabled. '
                            . 'Identifiers: ' . $outStrErr, 
                            1, 
                            'freestyle.log'
                        );
                        break;
                    }

                    $dateTimeStart = new DateTime();
                    $classDesc ='advancedexport/passivemode';
                    $passiveModel = Mage::getModel($classDesc);
                    $data = array();
                    $data['passivemod_enabled'] = 1;
                    $data['passivemod_start'] = 
                            $dateTimeStart->format('Y-m-d H:i:s');
                    $data['passivemod_end'] = null;
                    $data['created_files'] = serialize(array());
                    $data['is_notification_sent'] = 0;

                    $passiveModel->setData($data);
                    $passiveModel->save();

                    break;
                }

            case 'disable_passive' : {

                    $passiveEnabled = 
                        Mage::getModel('advancedexport/passivemode')
                        ->getCollection()
                        ->addFieldToFilter(
                            'passivemod_enabled', array('eq' => '1')
                        );
                    $dateTimeEnd = new DateTime();

                    if ($passiveEnabled->count()) {
                        foreach ($passiveEnabled as $one) {
                            $current = 
                                Mage::getModel('advancedexport/passivemode')
                                ->load($one->getId());
                            $data = $current->getData();

                            $data['passivemod_enabled'] = 0;
                            $data['passivemod_end'] = 
                                $dateTimeEnd->format('Y-m-d H:i:s');
                            $ids = $data['id'];
                            $current->setData($data);
                            $current->save();
                        }
                    }
                    break;
                }
            default: {
                    break;
                }
        }

        $result['errors'] = $errors;
        $result['status'] = $status;
        $result['id'] = $ids;

        return $result;
    }

    public function checkFoldersPremissions()
    {
        $exportFolder = Mage::Helper('advancedexport')->getExportfolder();

        try {
            if (!is_dir(Mage::getBaseDir() . DS . $exportFolder)) {
                $result = mkdir(Mage::getBaseDir() . DS . $exportFolder, 0777);
                if (!$result) {
                    return false;
                }
            }
            
            $dirPath = Mage::getBaseDir() . DS . 
                    $exportFolder . DS . 'tempFiles';
            if (!is_dir($dirPath)) {
                $result = mkdir($dirPath, 0777);
                if (!$result) {
                    return false;
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return true;
    }

    public function getCurrentTime()
    {

        //return $date = date("m-d-Y_H-i-s") . '_' . $this->getMilliseconds();
        return date("m-d-Y_H-i-s") . '_' . $this->getMilliseconds();
    }

    public function getTimeByStamp($datetime)
    {
        $formattedDateTime = date("m-d-Y_H-i-s", strtotime($datetime));

        //return $date = $formattedDateTime . '_' . $this->getMilliseconds();
        return $formattedDateTime . '_' . $this->getMilliseconds();
    }

    public function getMilliseconds()
    {
        try {
            $tme = explode(' ', microtime(false));
            $millisecondsTm = explode('.', $tme[0]);
            $milliseconds = substr($millisecondsTm[1], 0, 3);
        } catch (Exception $e) {
            Mage::log($e->getMessage(), 1, 'freestyle.log');
            $milliseconds = 100;
        }

        return $milliseconds;
    }

    //custom functions
    public function getExtensionVersion()
    {
        return (string) Mage::getConfig()
                ->loadModulesConfiguration('config.xml')
                ->getNode()->modules->Freestyle_Advancedexport->version;
    }

    public function getBuildDate()
    {
        $classDesc = 'freestyle_advancedexport/settings/build_date';
        return Mage::getStoreConfig($classDesc);
    }

    public function getEnablePassiveGui()
    {
        $classDesc = 'freestyle_advancedexport/settings/enable_passive_gui';
        return Mage::getStoreConfig($classDesc);
    }

    //DE-10150 - refactor.. copied from observer.php
    public function generateAndSaveExportFile(
        $entityToExport, 
        $entityId, 
        $action, 
        $order = null, 
        $scopeValue = 1
    )
    {
        if (!$this->getIsExtEnabledForApi()) {
            return false;
        }

        $this->checkFoldersPremissions();

        $model = $this->getEntityModel($entityToExport);

        if ($order === null) {
            if ($entityToExport == 'product') {
                $entityData = $model->info($entityId, $scopeValue);
            } else {
                $entityData = $model->info($entityId);
            }
        } else {
            $entityData = $order;
        }

        if (!$entityData) {
            Mage::log(
                "[WARNING] - Cannot [EXPORT] $entityToExport = $entityId", 
                1, 
                'freestyle.log'
            );
            return false;
        }

        $data = array($entityToExport => $entityData);
        $outXml = Mage::getModel('advancedexport/exportmodels_abstract')
                ->arrayToXml($entityToExport, $data);

        //$dateTimeInit = $helper->getCurrentTime();
        $dateTimeInit = $this->getCurrentTime();
        $files = 
            $this->getFilesNames($entityToExport, $dateTimeInit, $scopeValue);

        $files['entity'] = $entityToExport;
        $files['action'] = $action;

        $this->addToOutCollector($entityToExport, $outXml, $files['filePath']);
        //$this->closeXmlHeaderTag($entityToExport, $files['filePath']);
        $this->closeXmlHeaderTag($files['filePath']);

        $zipTest = !Mage::getModel('advancedexport/passivemode')
                ->getIsPassiveEnabled() 
                || $entityToExport == 'order';
        if ($zipTest) {
            $zip = new ZipArchive();
            try {
                $zip->open($files['zipFilePath'] . '.zip', ZIPARCHIVE::CREATE);
                $zip->addFile($files['filePath'], $files['fileName']);
                $zip->close();
            } catch (Exception $e) {
                $this->stepErrors[] = 'Can not create Zip Archive';
                Mage::log(
                    '[EXCEPTION] - Can not [CREATE] Zip Archive ' . 
                    $e->getMessage() . ' ' . $e->getFile() . '::' . 
                    $e->getLine(), 
                    1, 
                    'freestyle.log'
                );
            }

            return $files['zipFileName'] . '.zip';
        } else {
            Mage::getModel('advancedexport/passivemode')
                ->addFileDataToCollector($files);
        }
    }

    public function getFilesNames(
        $entityToExport, 
        $dateTimeInit, 
        $websiteId = 1
        )
    {
        $currentBatchNumber = '1';

        $tempfile = '[' . $this->getChanelName() . ']_[' . 
            $this->getChanelId($websiteId) . ']_[' . $entityToExport . ']_[' . 
            $dateTimeInit . ']_[batch-' . $currentBatchNumber . '].xml';
        $fileFullPath = Mage::getBaseDir() . DS . 
            Mage::Helper('advancedexport')->getExportfolder() . DS . $tempfile;

        $zipFile = '[' . $this->getChanelName() . ']_[' . 
            $this->getChanelId($websiteId) . ']_[' . $entityToExport . ']_[' . 
            $dateTimeInit . ']';
        $zipFileFullPath = Mage::getBaseDir() . DS . 
            Mage::Helper('advancedexport')->getExportfolder() . DS . $zipFile;

        return array(
            'fileName' => $tempfile, 
            'filePath' => $fileFullPath, 
            'zipFileName' => $zipFile, 
            'zipFilePath' => $zipFileFullPath
        );
    }

    public function readLogFile($bypass = false)
    {
        if ((int) $this->getEnablePassiveGui() == 1 && $bypass == false) {
            return "&nbsp";  //no need to read the log file if this is disabled
        }
        
        $baseDir = Mage::getBaseDir();
        $varDir = $baseDir . DS . 'var' . DS . 'log';
        //$logPath = $varDir . DS . 'freestyle.log';
        $logdata = '';
        try {
            $file = new Varien_Io_File();
            $file->open(array('path' => $varDir));
            $file->streamOpen('freestyle.log', 'r');

            while (false !== ($data = $file->streamRead())) {
                $logdata = $logdata . $data;
            }
        } catch (Exception $e) {
            Mage::log(
                "[EXCEPTION] - Failed to read log data: " . $e->getMessage()
                . $e->getFile() . '::'
                . $e->getLine(), 1, 'exception.log'
            );
        }

        if (empty($logdata)) {
            $logdata = "No data to display.  "
                    . "Please enable logging if you haven't already done so.";
        }

        return $logdata;
    }

    public function getAjaxSendUrl()
    {
        return Mage::helper('adminhtml')
            ->getUrl('adminhtml/advancedexport/sendjustone/');
    }

    public function getProductSendImages()
    {
        $classDesc = 'freestyle_advancedexport/settings/product_send_images';
        return Mage::getStoreConfig($classDesc);
    }

    public function apiAuthenticate($username, $password)
    {
        $apiModel = Mage::getModel('api/user');
        return $apiModel->authenticate($username, $password);
    }    
}
