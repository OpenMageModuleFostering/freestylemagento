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

class Freestyle_Advancedexport_Model_Exportmodels_Abstract
{

    const XML_VERSION = '1.0';
    const ICONV_CHARSET = 'UTF-8';

    public $entity;
    public $stepErrors;

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }

    public function arrayToXml($entityMain, $dataArray, $fieldsToExport = null)
    {
        if (!empty($dataArray[$entityMain])) {
            $studentInfo = array($dataArray);
            $entity = 'data';

            $xmlInfo = new
                Freestyle_Advancedexport_Model_Exportmodels_SimpleXMLExtended(
                    "<?xml version=\""
                    . self::XML_VERSION
                    . "\"?><$entity></$entity>"
                );
            $this->_arrayToXml($studentInfo, $xmlInfo, $fieldsToExport);
            //return $xml_info->$entityMain->asXML();
            $xmlReturnString = $xmlInfo->$entityMain->asXML();
            //$xmlReturnStringUtf8 = $this->cleanString($xmlReturnString);
            $xmlReturnStringUtf = $xmlReturnString;
            return $xmlReturnStringUtf;
        } else {
            return '';
        }
    }

    protected function cleanString($string)
    {
        Mage::log(
            'libiconv ==' . (string) ('"libiconv"' != ICONV_IMPL),
            1,
            'freestyle.log'
        );
        return '"libiconv"' != ICONV_IMPL ? $string : iconv(
            mb_detect_encoding($string),
            self::ICONV_CHARSET . '//TRANSLIT', $string
        );
    }

    public function encodeToUtf8($string)
    {
        return mb_convert_encoding(
            $string,
            "UTF-8",
            mb_detect_encoding(
                $string,
                "UTF-8, ISO-8859-1, ISO-8859-15",
                true
            )
        );
    }

    protected function _arrayToXml($info, &$xmlInfo, $fieldsToExport = null)
    {
        foreach ($info as $key => $value) {
            $dataCheck = false;
            $dataCheck = is_array($fieldsToExport)
                    && !empty($fieldsToExport)
                    && !is_array($value);
            if ($dataCheck) {
                if (!in_array($key, $fieldsToExport)) {
                    continue;
                }
            }
            
            if (is_array($value)) {
                if (!is_numeric($key)) {
                    if (!empty($key)) {
                        $subnode = $xmlInfo->addChild("$key");
                        $this->_arrayToXml($value, $subnode, $fieldsToExport);
                    } else {
                        $subnode = $xmlInfo->addChild("blank_attribute");
                        $this->_arrayToXml($value, $subnode, $fieldsToExport);
                    }
                } else {
                    $this->_arrayToXml($value, $xmlInfo, $fieldsToExport);
                }
            } else {
                $keyToAdd = $key;
/*
  if (($this->entity == 'product') || ($this->entity == 'category')) {
  $valueToAdd = htmlspecialchars($value);
  $xml_info->addChild($keyToAdd)->addCData($valueToAdd);

  } else {
  $valueToAdd = htmlspecialchars($value);
  $xml_info->addChild($keyToAdd,$valueToAdd);
  }
 */

/* Add CDATA Tag for all entities */
//$valueToAdd = htmlspecialchars($value);
//$xml_info->addChild($keyToAdd)->addCData($valueToAdd);
                $xmlInfo->addChild($keyToAdd)->addCData($value);

                unset($keyToAdd);
                //unset($valueToAdd);
            }
        }
    }

    protected function getStoresArray($websiteId = 1)
    {
        $storesCollection = Mage::getModel('core/store')
                ->getCollection()
                ->addFieldToFilter('group_id', $websiteId);
        foreach ($storesCollection as $store) {
            //stuff into array
            $stores[] = $store->getId();
        }
        return $stores;
    }
    
    protected function getCustomerCollection($priority, $stores)
    {
        $result = array();
        switch ($priority['param']) {
            case 'all': {
                    $result = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addFieldToFilter(
                                'store_id', array(
                                'in' => array('in' => $stores)
                                )
                            );
                    break;
                }
            case 'ids': {
                    $idsArr = explode(",", $priority['values']);
                    $result = Mage::getModel('customer/customer')
                            ->getCollection()
                            ->addFieldToFilter(
                                'store_id', array(
                                'in' => array('in' => $stores)
                                )
                            )
                            ->addAttributeToFilter(
                                'entity_id', array('in' => $idsArr)
                            );
                    break;
                }
            case 'date': {
                    $dateStart = $priority['startDate'];
                    $dateEnd = $priority['endDate'];
                    if ($dateStart && $dateEnd) {
                        $result = Mage::getModel('customer/customer')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' => array('in' => $stores)
                                    )
                                )
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart,
                                        'to' => $dateEnd
                                        )
                                );
                    } elseif ($dateStart) {
                        $result = Mage::getModel('customer/customer')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' =>
                                    array('in' => $stores)
                                    )
                                )
                                ->addAttributeToFilter(
                                    'created_at', array(
                                    'date' => true,
                                    'from' => $dateStart
                                    )
                                );
                    } else {
                        $result = Mage::getModel('customer/customer')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' =>
                                    array('in' => $stores)
                                    )
                                )                                
                                ->addAttributeToFilter(
                                    'created_at', array(
                                    'date' => true,
                                    'to' => $dateEnd
                                    )
                                );
                    }
                    break;
                }
            case 'default': {
                    break;
                }
        }
        if (!empty($result)) {
            //reset the columns
            $result->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns('entity_id');            
        }
        return $result;
    }

    protected function getOrderCollection($priority, $stores)
    {
        $result = array();
        switch ($priority['param']) {
            case 'all': {
                    $result = Mage::getModel('sales/order')
                            ->getCollection()
                            ->addFieldToFilter(
                                'store_id', array(
                                'in' => array('in' => $stores)
                                )
                            );
                    break;
                }
            case 'ids': {
                    $idsArr = explode(",", $priority['values']);
                    $result = Mage::getModel('sales/order')
                            ->getCollection()
                            ->addFieldToFilter(
                                'store_id', array(
                                'in' => array('in' => $stores)
                                )
                            )
                            ->addAttributeToFilter(
                                'increment_id', array('in' => $idsArr)
                            );
                    break;
                }
            case 'date': {
                    $dateStart = $priority['startDate'];
                    $dateEnd = $priority['endDate'];
                    if ($dateStart && $dateEnd) {
                        $result = Mage::getModel('sales/order')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' => array('in' => $stores)
                                    )
                                )
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart,
                                        'to' => $dateEnd
                                    )
                                );
                    } elseif ($dateStart) {
                        $result = Mage::getModel('sales/order')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' => array('in' => $stores)
                                    )
                                )
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart
                                    )
                                );
                    } else {
                        $result = Mage::getModel('sales/order')
                                ->getCollection()
                                ->addFieldToFilter(
                                    'store_id', array(
                                    'in' => array('in' => $stores)
                                    )
                                )
                                ->addAttributeToFilter(
                                    'created_at', array(
                                    'date' => true,
                                    'to' => $dateEnd
                                    )
                                );
                    }                   
                    break;
                }
            case 'default': {
                    break;
                }
        }
        if (!empty($result)) {
            //reset the columns
            $result->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns(
                        array('entity_id', 'increment_id')
                    );            
        }
        return $result;
    }
    protected function getProductCollection($priority, $websiteId)
    {
        $result = array();
        switch ($priority['param']) {
            case 'all': {
                    $result = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addWebsiteFilter($websiteId);
                    break;
                }
            case 'ids': {
                    $idsArr = explode(",", $priority['values']);
                    $result = Mage::getModel('catalog/product')
                            ->getCollection()
                            ->addWebsiteFilter($websiteId)
                            ->addAttributeToFilter(
                                'entity_id', array('in' => $idsArr)
                            );
                    break;
                }
            case 'date': {
                    $dateStart = $priority['startDate'];
                    $dateEnd = $priority['endDate'];
                    if ($dateStart && $dateEnd) {
                        $result = Mage::getModel('catalog/product')
                                ->getCollection()
                                ->addWebsiteFilter($websiteId)
                                ->addAttributeToFilter(
                                    'created_at', array(
                                    'date' => true,
                                    'from' => $dateStart,
                                    'to' => $dateEnd
                                    )
                                );
                    } elseif ($dateStart) {
                        $result = Mage::getModel('catalog/product')
                                ->getCollection()
                                ->addWebsiteFilter($websiteId)
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart
                                        )
                                );
                    } else {
                        $result = Mage::getModel('catalog/product')
                                ->getCollection()
                                ->addWebsiteFilter($websiteId)
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'to' => $dateEnd
                                    )
                                );
                    }
                    break;
                }
            case 'default': {
                    break;
                }
        }
        if (!empty($result)) {
            //reset the columns
            $result->getSelect()
                    ->reset(Zend_Db_Select::COLUMNS)
                    ->columns('entity_id');            
        }
        return $result;
    }
    
    protected function getCategoryCollection($priority)
    {
        $result = null;
        switch ($priority['param']) {
            case 'all': {
                    $result = Mage::getModel('catalog/category')
                            ->getCollection();
                    break;
                }
            case 'ids': {
                    $idsArr = explode(",", $priority['values']);
                    $result = Mage::getModel('catalog/category')
                            ->getCollection()
                            ->addAttributeToFilter(
                                'entity_id', array('in' => $idsArr)
                            );
                    break;
                }
            case 'date': {
                    $dateStart = $priority['startDate'];
                    $dateEnd = $priority['endDate'];
                    if ($dateStart && $dateEnd) {
                        $result = Mage::getModel('catalog/category')
                                ->getCollection()
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart,
                                        'to' => $dateEnd
                                    )
                                );
                    } elseif ($dateStart) {
                        $result = Mage::getModel('catalog/category')
                                ->getCollection()
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'from' => $dateStart
                                    )
                                );
                    } else {
                        $result = Mage::getModel('catalog/category')
                                ->getCollection()
                                ->addAttributeToFilter(
                                    'created_at', array(
                                        'date' => true,
                                        'to' => $dateEnd
                                    )
                                );
                    }
                    break;
                }
            case 'default': {
                    break;
                }
        }
        return $result;
    }
    
    protected function getCustomerGroupCollection($priority)
    {
        $result = null;
        switch ($priority['param']) {
            case 'all':
            case 'date': {
                    /* customer_group doesn't have create_at field */
                    $result = Mage::getModel('customer/group')
                            ->getCollection();
                    break;
                }
            case 'ids': {
                    $idsArr = explode(",", $priority['values']);
                    $result = Mage::getModel('customer/group')
                            ->getCollection()
                            ->addFieldToFilter(
                                'customer_group_id', array('in' => $idsArr)
                            );
                    break;
                }
            case 'default': {
                    break;
                }
        }
        return $result;
    }
    
    public function getEntityIdsCollection(
        $entityToExport,
        $priority,
        $websiteId = 1
        ) 
    {
        $result = array();
        $stores = $this->getStoresArray($websiteId);
        
        switch ($entityToExport) {
            case 'customer': {
                    $result = $this->getCustomerCollection($priority, $stores);
                    break;
                }
            case 'order': {
                    $result = $this->getOrderCollection($priority, $stores);
                    break;
                }
            case 'product': {
                    $result = $this
                            ->getProductCollection($priority, $websiteId);
                    break;
                }
            case 'category': {
                    $result = null;
                    $result = $this->getCategoryCollection($priority, $stores);
                    $result->getSelect()
                            ->reset(Zend_Db_Select::COLUMNS)
                            ->columns('entity_id');
                    break;
                }
            case 'customergroup': {
                    $result = null;
                    $result = $this->getCustomerGroupCollection($priority);
                    $result->getSelect()
                            ->reset(Zend_Db_Select::COLUMNS)
                            ->columns('customer_group_id');
                    break;
                }
            default: {
                    break;
                }
        }
        return $result;
    }

    public function getExportData($entityToExport, $batchsize, $dataCollection)
    {
        $helper = $this->getHelper();
        $this->entity = $entityToExport;

        $entityModel = $helper->getEntityModel($entityToExport);

        $data = array();
        $bathCounter = 1;
        $recordCounter = 0;

        foreach ($dataCollection as $oneEntity) {
            if ($entityToExport != 'order') {
                $entityData = $entityModel->info($oneEntity['entity_id']);
            } else {
                $entityData = $entityModel->info($oneEntity['increment_id']);
            }

            $data[$bathCounter][$oneEntity['entity_id']] =
                    array($entityToExport => $entityData);
            $recordCounter++;
            if ($recordCounter == $batchsize) {
                $recordCounter = 0;
                $bathCounter++;
            }
        }

        $date = date("m-d-Y_H-i-s");
        $fileResult = array();
        foreach ($data as $batchNumber => $oneBatch) {
            $outXml = Mage::getModel('advancedexport/exportmodels_abstract')
                    ->arrayToXml($entityToExport, $oneBatch);
            $fileName =
                $helper->putFile($entityToExport, $outXml, $batchNumber, $date);
            if ($fileName) {
                $fileResult[] = $fileName;
            } else {
                $this->errors[] = 'File failed to create. Batch Number is '
                        . $batchNumber . '. Batches count: ' . count($data);
                Mage::log(
                    'File failed to create. Batch Number is ' . $batchNumber
                    . '. Batches count: ' . count($data), 1, 'freestyle.log'
                );
            }
        }

        return $fileResult;
    }

    public function getExportDataMemoryControll(
            $entityToExport,
            $batchsize,
            $dataCollection,
            $idsFile,
            $dateTimeInit,
            $lastExportedStepInfo,
            $batchesFile,
            $websiteId = 1
        ) 
    {
        $maxMemory = (float) ini_get("memory_limit");
        unset($idsFile);
        $this->entity = $entityToExport;
        $helper = $this->getHelper();
        $entityModel = $helper->getEntityModel($entityToExport);

        $data = array();
        $memoryFinish = false;

        $dataReturnStepValue = array();
        
        $startRecordNumber = 0;
        if ($lastExportedStepInfo['last_record_value']) {
            $startRecordNumber =
                (int) $lastExportedStepInfo['last_record_value'] + 1;
        }

        $currentBatchNumber = (int) ($startRecordNumber / (int) $batchsize);
        $currentBatchNumber++;

        $configPath = Mage::getBaseDir() . DS .
                Mage::Helper('advancedexport')->getExportfolder();
        $zipFile = '[' . $helper->getChanelName() . ']_'
                 . '[' . $helper->getChanelId($websiteId) . ']_'
                 . '[' . $entityToExport . ']_'
                 . '[' . $dateTimeInit . ']';
        
        $tempfile = $zipFile . '_'
                  . '[batch-' . $currentBatchNumber . '].xml';
        
        $fileFullPath = $configPath . DS . $tempfile;
        $zipFileFullPath = $configPath . DS . $zipFile;

        $batchesFile[$tempfile] = $fileFullPath;

        for ($i = $startRecordNumber; $i < count($dataCollection); $i++) {
            $this->closeXmlHeader($i, $batchsize, $fileFullPath);
            $currentBatchNumber = (int) ($i / (int) $batchsize);
            $currentBatchNumber++;

            $tempfile = '[' . $helper->getChanelName() . ']_'
                      . '[' . $helper->getChanelId($websiteId) . ']_'
                      . '[' . $entityToExport . ']_'
                      . '[' . $dateTimeInit . ']_'
                      . '[batch-' . $currentBatchNumber . '].xml';
            $fileFullPath = $configPath . DS . $tempfile;

            $batchesFile[$tempfile] = $fileFullPath;

            $oneEntity = $dataCollection[$i];
            
            switch ($entityToExport){
                case 'order' : 
                    $entityData = $entityModel
                        ->info($oneEntity['increment_id']);
                    $dataReturnStepValue['index'] = 'increment_id';
                    $dataReturnStepValue['value'] = $oneEntity['increment_id'];
                    $dataReturnStepValue['record_number'] = $i;
                    break;
                case 'customergroup' : 
                    $entityData = $entityModel
                            ->info($oneEntity['customer_group_id']);
                    $dataReturnStepValue['index'] = 'customer_group_id';
                    $dataReturnStepValue['value'] = 
                            $oneEntity['customer_group_id'];
                    $dataReturnStepValue['record_number'] = $i;
                    break;
                default : 
                    $entityData = $entityModel->info($oneEntity['entity_id']);
                    $dataReturnStepValue['index'] = 'entity_id';
                    $dataReturnStepValue['value'] = $oneEntity['entity_id'];
                    $dataReturnStepValue['record_number'] = $i;
            }

            $data = array($entityToExport => $entityData);

            try {
                $outXml = $this->arrayToXml($entityToExport, $data);
                $helper->addToOutCollector(
                    $entityToExport,
                    $outXml,
                    $fileFullPath
                );
            } catch (Exception $e) {
                $this->stepErrors[] = 'Can not Save Entity To Xml. Entity Id: '
                        . $oneEntity['entity_id'];
                Mage::log(
                    'Can not [SAVE] Entity To Xml. Entity Id: '
                    . $oneEntity['entity_id'], 1, 'freestyle.log'
                );
            }

            //Controll Memory
            $currentMemoryUsage = (float) memory_get_usage() / 1024 / 1024;
            if ($maxMemory < ($currentMemoryUsage + 10)) {
                $finishStepInfo = array(
                    'error' => 'no_errors',
                    'action' => 'next_step',
                    'entermmidiateFileName' => $tempfile,
                    'last_exported_info' => $dataReturnStepValue,
                    'export_finished' => 0,
                    'memorylimit' => $maxMemory,
                    'memory_usage' => $currentMemoryUsage,
                    'error' => $this->stepErrors,
                    'batches_files' => $batchesFile,
                );

                $memoryFinish = true;
                break;
            }
        }

        if ($memoryFinish) {
            return $finishStepInfo;
        } else {
            if (count($dataCollection)) {
                //$helper->closeXmlHeaderTag($entityToExport, $fileFullPath);
                $helper->closeXmlHeaderTag($fileFullPath);
            } else {
                //$helper->openXmlHeaderTag($entityToExport, $fileFullPath);
                $helper->openXmlHeaderTag($fileFullPath);
                //$helper->closeXmlHeaderTag($entityToExport, $fileFullPath);
                $helper->closeXmlHeaderTag($fileFullPath);
            }
        }

        $this->createZip($zipFileFullPath, $batchesFile);
        
        $fileResult['export_finished'] = 1;
        $fileResult['action'] = 'end';
        $fileResult['batches_files'] = $batchesFile;
        $fileResult['zip_file'] = array($zipFile . '.zip');
        $fileResult['error'] = $this->stepErrors;

        return $fileResult;
    }
    
    protected function closeXmlHeader($pointer, $batchsize, $fileFullPath)
    {
        $helper = $this->getHelper();
        //File Name and File Path Format
        if ((($pointer % (int) $batchsize) == 0) && ($pointer != 0)) {
            //$helper->closeXmlHeaderTag($entityToExport, $fileFullPath);
            $helper->closeXmlHeaderTag($fileFullPath);
        }        
    }
    
    protected function createZip($zipFileFullPath, $batchesFile)
    {
        $zip = new ZipArchive();
        try {
            $zip->open($zipFileFullPath . '.zip', ZIPARCHIVE::CREATE);
        } catch (Exception $e) {
            $this->stepErrors[] = 'Can not create Zip Archive';
            Mage::log('Can not [CREATE] Zip Archive', 1, 'freestyle.log');
        }
        foreach ($batchesFile as $fileName => $fullPath) {
            try {
                $zip->addFile($fullPath, $fileName);
            } catch (Exception $e) {
                $this->stepErrors[] = 'Can not add file '
                    . $fileName
                    . ' to Zip Archive';
                Mage::log(
                    'Can not [ADD] file '
                    . $fileName
                    . ' to Zip Archive', 1, 'freestyle.log'
                );
            }
        }
        $zip->close();        
    }
}
