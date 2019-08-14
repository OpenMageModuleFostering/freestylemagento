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

class Freestyle_Advancedexport_Model_Queue extends Mage_Core_Model_Abstract
{

    protected $_sequence = 1;
    protected $_jsonOut = array();
    public $_transmissionHasError = false;

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancedexport/queue');
    }

    public function getLastExportData()
    {
        $collection = $this->getCollection();
        $collection->getSelect()->order('create_time DESC');
        $last = $collection->getFirstItem();
        return $last;
    }

    /**
     * Send queue records based on website id
     *
     * @param int $batchSize - number of records to send
     * @param int $websiteId - website Id to send
     * @return int - number of records sent; can be less than batchsize
     * */
    public function sendWebsiteCollection($batchSize = 50, $websiteId = 1)
    {
        $batchSize = ($batchSize == NULL) ? 50 : $batchSize;
        $websiteId = ($websiteId == NULL) ? 1 : $websiteId;

        $returnCount = 0;
        $okToContinue = false;
        try {
            $collection = $this->getCollection()
                    ->addFieldToFilter('status', 'pending')
                    ->addFieldToFilter('scope_value', $websiteId)
                    ->setOrder('create_time', 'ASC')
                    ->setPageSize($batchSize)
                    ->setCurPage(1);
            $okToContinue = true;
        } catch (Exception $ex) {
            $errorMessage = "[EXCEPTION] - Unable to gather data collection for"
                    . " [Website Id = {$websiteId}]; Exception: " 
                    . $ex->getMessage();
            Mage::log(
                $errorMessage . ' ' . $ex->getFile() . '::' 
                . (string) $ex->getLine(), 
                1, 
                'freestyle.log'
            );
            $returnCount = 0;
        }

        if ($okToContinue) {
            try {
                $this->prepareEntities($collection);
                $updateToSuccess = false;
                $notifier = Mage::helper('advancedexport/notificationSender');
                if (!empty($this->_jsonOut)) {
                    $updateToSuccess = $notifier
                            ->sendQueue($this->_jsonOut, $websiteId);
                }

                foreach ($collection as $row) {
                    $row->setStatus($updateToSuccess ? 'sent' : 'error');
                    $row->setErrorMsg(
                        $updateToSuccess ? null : $notifier->sendErrorMessage
                    );
                    $row->setUpdateTime($this->createTimestamp());
                }
                $collection->save();
                //$returnCount = $collection->count();
                $returnCount = $collection->getSize();
            } catch (Exception $exx) {
                $errorMessage = "[EXCEPTION] - Unable to gather data collection"
                        . " for [Website Id = {$websiteId}]; Exception: " . 
                        $exx->getMessage();
                Mage::log(
                    $errorMessage . ' ' . $exx->getFile() . '::' . 
                    (string) $exx->getLine(), 
                    1, 
                    'freestyle.log'
                );
                foreach ($collection as $row) {
                    $row->setStatus('error');
                    $row->setErrorMsg($errorMessage);
                    $row->setUpdateTime($this->createTimestamp());
                }
                $collection->save();
                $returnCount = 0;
            }
        }
        //DE-11662 - reset the array
        $this->_jsonOut = array();
        return $returnCount;
    }

    /**
     * Sends queue records based on ids or array of ids
     *
     * @param int $batchSize - number of records to send
     * @param mixed $idToSend - an id or an array of ids from the queue table
     * @return int - number of records processed
     */
    public function sendMixCollection($batchSize = 50, $idToSend = 0)
    {
        $batchSize = ($batchSize == NULL) ? 50 : $batchSize;
        
        if ($idToSend == 0) {
            return false;
        }
        $this->_transmissionHasError = false;  //reset
        $returnCount = 0;
        $okToContinue = false;
        try {
            if (is_array($idToSend)) {
                $integerIds = array_map('intval', $idToSend);
                $collection = $this->getCollection()
                        ->addFieldToFilter('id', array('in' => $integerIds))
                        ->setOrder('create_time', 'ASC')
                        ->setPageSize($batchSize)
                        ->setCurPage(1);
            } else {
                $collection = $this->getCollection()
                        ->addFieldToFilter('id', $idToSend)
                        ->setOrder('create_time', 'ASC')
                        ->setPageSize($batchSize)
                        ->setCurPage(1);
            }
            $okToContinue = true;
        } catch (Exception $ex) {
            $errorMessage = "[EXCEPTION] - Unable to gather data collection "
                    . "for [Website Id = {$websiteId}]; Exception: " 
                    . $ex->getMessage();
            Mage::log(
                $errorMessage . ' ' . $ex->getFile() . '::' . 
                (string) $ex->getLine(), 
                1, 
                'freestyle.log'
            );
            $returnCount = 0;
        }

        if ($okToContinue) {
            try {
                $this->prepareEntities($collection, true);

                $updateToSuccess = false;
                $notifier = Mage::helper('advancedexport/notificationSender');
                if (!empty($this->_jsonOut)) {
                    $updateToSuccess = $notifier->sendMixQueue($this->_jsonOut);
                }

                foreach ($collection as $row) {
                    $row->setStatus($updateToSuccess ? 'sent' : 'error');
                    $row->setErrorMsg(
                        $updateToSuccess ? NULL : $notifier->sendErrorMessage
                    );
                    $row->setUpdateTime($this->createTimestamp());
                }
                $this->_transmissionHasError = 
                        !empty($notifier->sendErrorMessage);                
                $collection->save();
                //$returnCount = $collection->count();
                $returnCount = $collection->getSize();
            } catch (Exception $exx) {
                $errorMessage = "[EXCEPTION] - Unable to gather data collection"
                        . " for [Website Id = {$websiteId}]; Exception: " 
                        . $exx->getMessage();
                Mage::log(
                    $errorMessage . ' ' . $exx->getFile() . '::' . 
                    (string) $exx->getLine(), 
                    1, 
                    'freestyle.log'
                );
                foreach ($collection as $row) {
                    $row->setStatus('error');
                    $row->setErrorMsg($errorMessage);
                    $row->setUpdateTime($this->createTimestamp());
                }
                $collection->save();
                $returnCount = 0;
            }
        }

        //DE-11662 - reset the array
        $this->_jsonOut = array();
        return $returnCount;
    }

    /**
     * Sends queue records based on ids or array of ids
     *
     * @param int $batchSize - number of records to send
     * @param mixed $idToSend - an id or an array of ids from the queue table
     * @param int $websiteId - website Id to send
     * @return int - number of records processed
     */
    public function getEntitiesToExport(
            $batchSize = 50, 
            $idToSend = null, 
            $websiteId = 1
        )
    {
        if ($idToSend) {
            return $this->sendMixCollection($batchSize, $idToSend);
        } else {
            return $this->sendWebsiteCollection($batchSize, $websiteId);
        }
    }

    /**
     * Add records to the queue
     *
     * @param string $entityToExport - 
     *      {'order', 'product', 'customer', 'category'}
     * @param int $entityId - entity id for the entity to export
     * @param string $action - {'added', 'deleted'}
     * @param string $entityValue - 
     *      display value for entity {increment id, sku, first+last name}
     * @param string $scope - 'website';
     * @param int scope_value - website id;
     * @return this (Freestyle_Advancedexport_Model_Queue)
     */
    public function addToQueue(
            $entityToExport, 
            $entityId, 
            $action, 
            $entityValue = '', 
            $scope = '', 
            $scopeId = 1
        )
    {
        $collectionCheck = $this->checkDistinct(
            $entityToExport, 
            $entityId, 
            $action, 
            $scope, 
            $scopeId
        );
        if ($collectionCheck->count() == 0) {
            $websites = array();
            $helper = Mage::Helper('advancedexport/website');

            if ($scope === 'default') {
                //create an array of multiple stores
                //get a list of stores that are supported
                $syncedSites = $helper->getWebsites(true);
                foreach ($syncedSites as $website) {
                    $config['scope'] = 'website'; 
                    $config['scope_value'] = $website['WebsiteId']; 
                    array_push($websites, $config);
                }
            } else {
                if ($helper->isWebSiteStoreViewSupported($scopeId)) {
                    $single['scope'] = 'website';
                    $single['scope_value'] = Mage::app()
                            ->getWebsite($scopeId)
                            ->getId(); 
                    array_push($websites, $single);
                }
            }

            foreach ($websites as $web) {
                $messageData['entity_type'] = $entityToExport;
                $messageData['entity_id'] = $entityId;
                $messageData['action'] = $action;
                $messageData['create_time'] = $this->createTimestamp();
                $messageData['status'] = 'pending';
                $messageData['entity_value'] = utf8_encode($entityValue);

                $messageData['scope'] = $web['scope'];
                $messageData['scope_value'] = $web['scope_value'];

                $this->setData($messageData);
                $this->save();
            }
            return $this;
        } else {
            //get the record and return it..
            $newQueueModel = Mage::getModel('advancedexport/queue');
            foreach ($collectionCheck as $row) {
                $newQueueModel->load($row->getId());
                break;
            }
            return $newQueueModel;
        }
    }

    /**
     * Removes records from queue table
     *
     * @param int $daysToKeep - number of days to keep as history
     */
    public function purgeQueue($daysToKeep = 30)
    {
        $dateModel = Mage::getModel('core/date');
        $dateEndTime = $dateModel->timestamp(
            time() - ($daysToKeep * 24 * 60 * 60)
        );
        $dateEnd = $dateModel->date('Y-m-d', $dateEndTime) . " 00:00:00";

        $result = $this->getCollection()
                ->addFieldToFilter(
                    'create_time', 
                    array('date' => true, 'to' => $dateEnd)
                )
                ->addFieldToFilter('status', 'sent');
        $result->getSelect()
                ->reset(Zend_Db_Select::COLUMNS)
                ->columns('id');
        foreach ($result as $row) {
            $row->setStatus('deleted');
            $row->setUpdateTime($this->createTimestamp());
        }

        $result->delete();
    }

    /**
     * Sends one order to Freestyle immediately
     *
     * @param mixed $orderIncrementId - string usually
     * @return bool $updateToSuccess
     */
    public function resendOrder($orderIncrementId)
    {
        $advHelper = Mage::Helper('advancedexport');
        $webHelper = Mage::Helper('advancedexport/website');

        //$rowsToUpdate = array();

        //initialize XML stuffs
        $xmlVersionHeader = $advHelper->getXmlVersionHeader();
        $xmlVersion = $advHelper->getMainXmlTagWithParams();
        $xmlEndTag = $advHelper->getMainXmlTagEnd();

        $entityToExport = 'order';
        $action = 'added';

        $orderModel = Mage::getModel('sales/order');
        $orderModel->loadByIncrementId($orderIncrementId);

        //get the website for the store this order was placed under
        $storeId = $orderModel->getStoreId();
        $websiteId = $webHelper->getWebsiteByStoreId($storeId);
        if (!$webHelper->isWebSiteStoreViewSupported($websiteId)) {
            //website is not synced to Freestyle
            $resultMsg = "Unable to send Order $orderIncrementId to Freestyle."
                    . "  Website is not synced with Freestyle.";
            Mage::getSingleton('adminhtml/session')->addError($resultMsg);
            $this->_jsonOut = array(); //reset
            Mage::app()->getResponse()->setBody($resultMsg);
            return false;
        }

        $entityId = $orderModel->getId();

        if (!$entityId) {
            $resultMsg = "Unable to send Order $orderIncrementId to Freestyle."
                    . "  Please ensure you have entered a valid Order Number";
            Mage::getSingleton('adminhtml/session')->addError($resultMsg);
            return false;
        }

        $entityDisplayValue = $orderIncrementId;
        $entityXml = '';

        //generic code
        $model = $advHelper->getEntityModel($entityToExport);

        //if order, check setting, if send dependencies, gather order items
        //if order, check setting, if send dependencies, gather customer
        $sendExtra = Mage::Helper('advancedexport/queue')
                ->getSendOrderDependencies();        
        if ($entityToExport == 'order' && $sendExtra) {
            $this->appendOrderDependencies($entityId);
        }

        if (strtoupper($action) != 'DELETED') {
            $entityData = $model->info($orderIncrementId);
            $data = array($entityToExport => $entityData);
            $entityXml = Mage::getModel('advancedexport/exportmodels_abstract')
                    ->arrayToXml($entityToExport, $data);
            $entityXml = $xmlVersionHeader 
                    . "<$xmlVersion>" 
                    . $entityXml 
                    . "</$xmlEndTag>";
        } else {
            $entityXml = 'DELETED';
        }

        //key value matches FS
        $rowOut['EntityId'] = $entityId;
        $rowOut['EntityType'] = $entityToExport;
        $rowOut['EntityEventType'] = $action;
        $rowOut['EntityXml'] = $entityXml;
        $rowOut['EntityValue'] = utf8_encode($entityDisplayValue);
        $rowOut['MagentoCreateTime'] = $orderModel->created_at;
        $rowOut['Sequence'] = $this->_sequence;
        $this->_sequence++;
        array_push($this->_jsonOut, $rowOut);

        $updateToSuccess = false;

        if (!empty($this->_jsonOut)) {
            $notifier = Mage::helper('advancedexport/notificationSender');
            $storeId = $orderModel->getStoreId();
            $websiteId = $webHelper->getWebsiteByStoreId($storeId);
            $updateToSuccess = $notifier->sendQueue(
                $this->_jsonOut, $websiteId
            );
        }

        $resultMsg = '';
        if ($updateToSuccess) {
            $resultMsg = "Success! Order $orderIncrementId sent to Freestyle";
            Mage::getSingleton('adminhtml/session')->addSuccess($resultMsg);
        } else {
            $resultMsg = "Unable to send Order $orderIncrementId to Freestyle."
                    . "  Please ensure you have entered a valid Order Number";
            Mage::getSingleton('adminhtml/session')->addError($resultMsg);
        }

        $this->_jsonOut = array(); //reset
        Mage::app()->getResponse()->setBody($resultMsg);
        return $updateToSuccess;
    }

    /**
     * Gathers collection of records from queue table
     *
     * @param int $startValue - beginning Id value
     * @param int $endValue - ending Id value
     * @return array $collection->data
     */
    public function getEntityQueue($startValue = 1, $endValue = 1)
    {
        $collection = $this->getCollection();
        $collection->getSelect()->where(
            '`id` >= ' . $startValue . ' && `id` <= ' . $endValue
        )->order('create_time ASC');
        $select = $collection->getData();
        return $select;
    }

    /**
     * Generates XML based on collection passed
     *
     * @param object $collection - collection of queue records
     * @param bool $isMix - true if the data needs to be prepared with sales
     *                      channel id needs to be embedded in the payload
     */
    protected function prepareEntities($collection, $isMix = false)
    {
        $advHelper = Mage::Helper('advancedexport');
        //$rowsToUpdate = array();

        //initialize XML stuffs
        $xmlVersionHeader = $advHelper->getXmlVersionHeader();
        $xmlVersion = $advHelper->getMainXmlTagWithParams();
        $xmlEndTag = $advHelper->getMainXmlTagEnd();

        foreach ($collection as $row) {
            $entityToExport = $row->getEntityType();
            $entityId = $row->getEntityId();
            $action = $row->getAction();
            $entityDisplayValue = $row->getEntityValue();
            $entityXml = '';

            //generic code
            $model = $advHelper->getEntityModel($entityToExport);
            $channelId = null;
            if ($isMix) {
                $channelId = $advHelper->getChanelId($row->getScopeValue());
            }
            //if order, check setting, if send dependencies, gather order items
            //if order, check setting, if send dependencies, gather customer
            if ($entityToExport == 'order') {
                //swap entityid to contain increment id
                $sendExtra = Mage::Helper('advancedexport/queue')
                        ->getSendOrderDependencies();
                if ($sendExtra) {
                    $this->appendOrderDependencies($entityId, $channelId);
                    //array_merge($jsonOut, $rowDepOut);
                }
                $entityId = $row->getEntityValue();
            }

            if (strtoupper($action) != 'DELETED') {
                //if($entityToExport==='product')
                //    $entityData = $model->info($entityId,$row->getStoreId());
                //else
                $entityData = $model->info($entityId); //currently, store level
                                                       // info is not supported.
                $data = array($entityToExport => $entityData);
                $fsAbstract = 
                        Mage::getModel('advancedexport/exportmodels_abstract');
                $entityXml = $fsAbstract->arrayToXml($entityToExport, $data);
                $entityXml = $xmlVersionHeader 
                        . "<$xmlVersion>" 
                        . $entityXml 
                        . "</$xmlEndTag>";
            } else {
                if (method_exists($model, "getEntityIdFieldName")) {
                    $entityData = $model->info($entityId);
                    $entityIdFieldName = $model->getEntityIdFieldName();
                    $entityData[$entityIdFieldName] = $entityId;
                    $data = array($entityToExport => $entityData);
                    $fieldsToExport = array($entityIdFieldName);
                    $entityXml = 
                        Mage::getModel('advancedexport/exportmodels_abstract')
                        ->arrayToXml($entityToExport, $data, $fieldsToExport);
                    $entityXml = $xmlVersionHeader 
                            . "<$xmlVersion>" 
                            . $entityXml 
                            . "</$xmlEndTag>";
                } else {
                    $entityXml = $xmlVersionHeader 
                            . "<$xmlVersion>" 
                            . "DELETED" 
                            . "</$xmlEndTag>";
                }
            }

            if ($entityToExport == 'order') {
                //swap it back
                $entityId = $row->getEntityId();
            }

            //key value matches FS
            $rowOut['EntityId'] = $entityId;
            $rowOut['EntityType'] = $entityToExport;
            $rowOut['EntityEventType'] = $action;
            $rowOut['EntityXml'] = $entityXml;
            $rowOut['EntityValue'] = $this->encodeToUtf8($entityDisplayValue);
            $rowOut['MagentoCreateTime'] = $row->getCreateTime();
            $rowOut['Sequence'] = $this->_sequence;
            if ($isMix) {
                $rowOut['SalesChannelId'] = $channelId;
            }
            $this->_sequence++;
            array_push($this->_jsonOut, $rowOut);

            //update the row to 'processing'
            $row->setStatus('processing');
        }
        $collection->save();
    }

    /**
     * Checks if there is an unprocessed record in the queue already
     *
     * @param string $entityToExport - 
     *      {'order', 'product', 'customer', 'category'}
     * @param int $entityId - entity id for the entity to export
     * @param string $action - {'added', 'deleted'}
     * @param string $entityValue - display value for entity 
     *      {increment id, sku, first+last name}
     * @param string $scope - 'website';
     * @param int scope_value - website id;
     * @return object $collection
     */
    protected function checkDistinct(
            $entityToExport, 
            $entityId, 
            $action, 
            $scope = 'website', 
            $scopeValue = 1
        )
    {
        $batchSize = Mage::Helper('advancedexport/queue')->getQueueBatchSize();
        $collection = $this->getCollection()
                ->addFieldToFilter('entity_type', $entityToExport)
                ->addFieldToFilter('entity_id', $entityId)
                ->addFieldToFilter('action', $action)
                ->addFieldToFilter('status', 'pending')
                ->addFieldToFilter('scope', $scope)
                ->addFieldToFilter('scope_value', $scopeValue)
                ->setOrder('create_time', 'ASC')
                ->setPageSize($batchSize)
                ->setCurPage(1);
        return $collection;
    }

    /**
     * Gathers items and customers for an order and pre-pends 
     * them in the payload
     *
     * @param int $orderId - order id to be processed
     * @param string channelId - sales channel id of the website for this order
     */
    //TODO:  inspect order model and skip dependencies if order is cancelled
    protected function appendOrderDependencies($orderId, $channelId = null)
    {
        //$queueArray = array();
        $customerInfo = array();
        //$orderItemsArray = array();

        $advHelper = Mage::Helper('advancedexport');

        //initialize XML stuffs
        $xmlVersionHeader = $advHelper->getXmlVersionHeader();
        $xmlVersion = $advHelper->getMainXmlTagWithParams();
        $xmlEndTag = $advHelper->getMainXmlTagEnd();

        $entityXml = '';

        //load the order
        $orderModel = Mage::getModel('sales/order');
        $orderModel->load($orderId);

        $magentoCreateTime = $orderModel->created_at;

        //get the customer
        $customerIdtoExport = $orderModel->customer_id;
        $model = $advHelper->getEntityModel('customer');
        $entityData = $model->info($customerIdtoExport);
        $data = array('customer' => $entityData);
        $entityXml = Mage::getModel('advancedexport/exportmodels_abstract')
                ->arrayToXml('customer', $data);
        $entityXml = $xmlVersionHeader 
                . "<$xmlVersion>" 
                . $entityXml 
                . "</$xmlEndTag>";
        $entityDisplayValue = trim($entityData['firstname']) 
                . " " . trim($entityData['lastname']);

        $customerInfo['EntityId'] = $customerIdtoExport;
        $customerInfo['EntityType'] = 'customer';
        $customerInfo['EntityEventType'] = 'added';
        $customerInfo['EntityXml'] = $entityXml;
        $customerInfo['EntityValue'] = $this->encodeToUtf8($entityDisplayValue);
        $customerInfo['MagentoCreateTime'] = $magentoCreateTime; 
        $customerInfo['Sequence'] = $this->_sequence;

        if ($channelId) {
            $customerInfo['SalesChannelId'] = $channelId;
        }

        $this->_sequence++;
        array_push($this->_jsonOut, $customerInfo);

        unset($model);
        unset($data);
        unset($entityXml);
        unset($entityData);
        unset($entityDisplayValue);
        unset($customerInfo);

        //walk thru the items
        $items = $orderModel->getAllItems();
        $model = $advHelper->getEntityModel('product');
        //foreach ($items as $itemId => $item) {
        foreach ($items as $item) {
            $itemInfo = array();
            $productIdtoExport = $item->getProductId();
            $entityData = $model->info($productIdtoExport);
            $data = array('product' => $entityData);
            $entityXml = Mage::getModel('advancedexport/exportmodels_abstract')
                    ->arrayToXml('product', $data);
            $entityXml = $xmlVersionHeader 
                    . "<$xmlVersion>" 
                    . $entityXml 
                    . "</$xmlEndTag>";
            $entityValue = $item->getSku();

            $itemInfo['EntityId'] = $productIdtoExport;
            $itemInfo['EntityType'] = 'product';
            $itemInfo['EntityEventType'] = 'added';
            $itemInfo['EntityXml'] = $entityXml;
            $itemInfo['EntityValue'] = $this->encodeToUtf8($entityValue);
            $itemInfo['MagentoCreateTime'] = $magentoCreateTime; 
            $itemInfo['Sequence'] = $this->_sequence;

            if ($channelId) {
                $itemInfo['SalesChannelId'] = $channelId;
            }

            $this->_sequence++;
            array_push($this->_jsonOut, $itemInfo);
            unset($data);
            unset($entityXml);
            unset($entityValue);
            unset($entityData);
            unset($itemInfo);
        }
        return true;
    }

    /**
     * Generates TimeStamp.  Added for compatibility with EE1.10
     *
     * @return DateTime
     */
    protected function createTimestamp()
    {
        /*
          $currentDate = Varien_Date::now();
          $currentTimestamp = Varien_Date::toTimestamp($currentDate);
          return $currentTimestamp;
         *
         */
        $usePrimitiveDate = false;
        $mageVersion = Mage::getVersion();
        $fsHelper = Mage::Helper('advancedexport');
        if ($fsHelper->isEnterprise() == 'Enterprise') {
            $usePrimitiveDate = version_compare($mageVersion, '1.12.0.0') <= 0;
        }

        $currentTimeStamp = Mage::getSingleton('core/date')->gmtTimestamp();

        //added fix for EE 1.10 returning null time stamps
        if ($currentTimeStamp == null || $usePrimitiveDate) {
            $currentDateTime = new DateTime('now', new DateTimeZone('UTC'));
            $currentTimeStamp = $currentDateTime->format('Y-m-d H:i:s');
            unset($currentDateTime);
        }
        return $currentTimeStamp;
    }

    /**
     * Converts string to UTF8 for XML Compatibility
     *
     * @param string $string
     * @return string
     */
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
}
