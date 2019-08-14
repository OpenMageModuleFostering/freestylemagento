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

class Freestyle_Advancedexport_Model_Observer
{

    public $stepErrors;

    public function getIsExtEnabled()
    {
        return Mage::Helper('advancedexport')->getIsExtEnabledForApi();
    }

    public function getFilesNames($entityToExport, $dateTimeInit)
    {
        $helper = Mage::Helper('advancedexport');

        $currentBatchNumber = '1';

        $tempfile = '[' . $helper->getChanelName() . ']_'
                  . '[' . $helper->getChanelId() . ']_'
                  . '[' . $entityToExport . ']_'
                  . '[' . $dateTimeInit . ']_'
                  . '[batch-' . $currentBatchNumber . '].xml';
        $fileFullPath = Mage::getBaseDir() . DS . 
                Mage::Helper('advancedexport')->getExportfolder() . DS . 
                $tempfile;

        $zipFile = '[' . $helper->getChanelName() . ']_'
                 . '[' . $helper->getChanelId() . ']_'
                 . '[' . $entityToExport . ']_'
                 . '[' . $dateTimeInit . ']';
        $zipFileFullPath = Mage::getBaseDir() . DS . 
                Mage::Helper('advancedexport')->getExportfolder() . DS . 
                $zipFile;

        return array(
            'fileName' => $tempfile, 
            'filePath' => $fileFullPath, 
            'zipFileName' => $zipFile, 
            'zipFilePath' => $zipFileFullPath
        );
    }

    public function productSaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $product = $observer->getProduct();
        Mage::log(
            "[INFO] - [productSaveAfter] File generation  "
            . "for SKU [$product->sku].", 
            1, 
            'freestyle.log'
        );
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [productSaveAfter] File generation skipped "
                . "for SKU [$product->sku]. API Call...", 
                1, 
                'freestyle.log'
            );
            return true;
        }
        $entityToExport = 'product';
        //$action = 'updated';
        $action = 'added';


        $entityId = $product->getEntityId();
        $sku = $product->getSku();

        //DE-8384 - Multi-Store Support
        $storeId = Mage::app()->getRequest()->getParam('store');
        if (is_null($storeId)) {
            //it was saved in the default scope..
            //$store_scopes= $product->getStoreIds();
            $storeScopes = $product->getWebsiteIds();
        } else {
            $storeScopes = array($this->getWebsiteIdfromStoreId($storeId));
        }

        foreach ($storeScopes as $scopeval) {
            $this->pushData(
                $entityToExport, 
                $entityId, 
                $action, 
                null, 
                $sku, 
                'website', 
                $scopeval
            );
        }
    }

    public function productDeleteBefore($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $product = $observer->getProduct();
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [productDeleteBefore] File generation skipped "
                . "for SKU [$product->sku]. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'product';
        $action = 'deleted';

        //DE-8384 - Multi-Store Support
        $storeId = Mage::app()->getRequest()->getParam('store');
        //$website_id = $this->getWebsiteIdfromStoreId($store_id); /* unused */

        $entityId = $product->getEntityId();
        $sku = $product->getSku();
        $this->pushData(
            $entityToExport, 
            $entityId, 
            $action, 
            null, 
            $sku, 
            'store', 
            $storeId
        );  //convert this later
    }

    public function productSaveAfterStandartImport($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [productSaveAfterStandartImport] File generation "
                . "skipped. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'product';
        $action = $observer->getEventtype();

        $productsIds = $observer->getChangedids();
        
        foreach ($productsIds as $entityId) {
            $realId = $entityId;
            $productModel = Mage::getModel('catalog/product')
                    ->loadByAttribute('sku', $entityId);
            if ($action == 'updated') {
                $realId = $productModel->getId();
            }

            //DE-8384 - Multi-Store Support
            $storeScopes = $productModel->getWebsiteIds();
            foreach ($storeScopes as $scopeval) {
                $this->pushData(
                    $entityToExport, 
                    $realId, 
                    $action, 
                    null, 
                    $entityId, 
                    'website', 
                    $scopeval
                );
            }
        }
    }

    public function categorySaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [categorySaveAfter] File generation skipped. "
                . "API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'category';
        $action = 'added';
                
        $category = $observer->getCategory();
        $entityId = $category->getEntityId();

        $this->pushData($entityToExport, $entityId, $action);
    }

    public function categoryDeleteBefore($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [categoryDeleteBefore] File generation skipped."
                . " API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'category';
        $action = 'deleted';

        $category = $observer->getCategory();
        $entityId = $category->getEntityId();

        $this->pushData($entityToExport, $entityId, $action);
    }

    public function customerSaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $customer = $observer->getCustomer();
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [customerSaveAfter] File generation skipped "
                . "for Customer ID [$customer->entity_id]. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        Mage::log(
            "[INFO] - [customerSaveAfter] File generation for "
            . "Customer ID [$customer->entity_id].", 
            1, 
            'freestyle.log'
        );

        $entityToExport = 'customer';
        $action = 'added';

        $entityId = $customer->getEntityId();

        //DE-8384 - Multi-Store Support
        //$store_id = Mage::app()->getRequest()->getParam('store');
        //$website_id = $this->getWebsiteIdfromStoreId($store_id);
        $websiteId = $customer->getWebsiteId();

        $this->pushData(
            $entityToExport, 
            $entityId, 
            $action, 
            null, 
            $customer->getName(), 
            'website', 
            $websiteId
        );
    }

    public function customerDeleteBefore($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $customer = $observer->getCustomer();
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [customerDeleteBefore] File generation skipped "
                . "for Customer ID [$customer->entity_id]. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'customer';
        $action = 'deleted';

        $entityId = $customer->getEntityId();

        //DE-8384 - Multi-Store Support
        $storeId = Mage::app()->getRequest()->getParam('store');
        $websiteId = $this->getWebsiteIdfromStoreId($storeId);

        $this->pushData(
            $entityToExport, 
            $entityId, 
            $action, 
            null, 
            $customer->getName(), 
            'website', 
            $websiteId
        );
    }

    public function customerAddressSaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [customerAddressSaveAfter] File generation skipped. "
                . "API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'customer';
        $action = 'added';

        $address = $observer->getCustomerAddress();
        $customer = $address->getCustomer();
        $entityId = $customer->getEntityId();

        //DE-8384 - Multi-Store Support
        $websiteId = $customer->getWebsiteId();

        $this->pushData(
            $entityToExport, 
            $entityId, 
            $action, 
            null, 
            $customer->getName(), 
            'website', 
            $websiteId
        );
    }

    public function customerSaveAfterStandartImport($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [customerSaveAfterStandartImport] File "
                . "generation skipped. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'customer';
        $action = $observer->getEvent();

        $customersIds = $observer->getChangedids();

        $customerModel = Mage::getModel('customer/customer');
        //echo '<pre>'; print_r($customersIds);
        foreach ($customersIds as $info) {
            $entityId = $customerModel
                    ->setWebsiteId($info['website'])
                    ->loadByEmail($info['email'])->getId();
            if ($entityId) {
                $websiteId = $customerModel->getWebsiteId();
                $this->pushData(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    null, 
                    $customerModel->getName(), 
                    'website', 
                    $websiteId
                );
            }
        }
    }

    public function getOrderCutOff()
    {
        return strtotime(Mage::Helper('advancedexport')->getOrderCutOffDate());
    }

    public function orderSaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $order = $observer->getOrder();
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [orderSaveAfter] File generation "
                . "skipped for Order [$order->increment_id]. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }

        //DE-10071 - compare cutoff date and current date as time values
        $cutOff = $this->getOrderCutOff();

        if (!is_null($cutOff) && !is_bool($cutOff)) {
            $currentOrderTime = strtotime($order->created_at);
            if ($currentOrderTime <= $cutOff) {
                Mage::log(
                    "[INFO] - [orderSaveAfter] File generation "
                    . "skipped for Order [$order->increment_id]. Prior to " 
                    . date('Y-m-d', $cutOff) . " cutoff date", 
                    1, 
                    'freestyle.log'
                );
                return true;
            }
        }

        $entityToExport = 'order';
        $action = 'added';

        //attempt to write the real order id instead
        if (Mage::helper('advancedexport/queue')->getEnableQueue()) {
            $entityId = $order->getId();
        } else {
            $entityId = $order->getIncrementId();
        }


        $history = $order->getAllStatusHistory();

        //TODO:  grab which store this order is for!!
        if (!empty($history)) {
            $orderStatus = $order->getStatus();
            $storeId = $order->getStoreId();
            $websiteId = $this->getWebsiteIdfromStoreId($storeId);

            if (strtoupper($orderStatus) == "COMPLETED") {
                $this->pushData(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    $order, 
                    $order->getIncrementId(), 
                    'website', 
                    $websiteId
                );
            } else {
                $this->pushData(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    null, 
                    $order->getIncrementId(), 
                    'website', 
                    $websiteId
                );
            }
        }
    }

    public function mediaSaveAfter($observer)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        $product = $observer->getProduct();
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[INFO] - [productDeleteBefore] File generation "
                . "skipped for SKU [$product->sku]. API Call...", 
                1, 
                'freestyle.log'
            );
            //return false;
            return true;
        }
        $entityToExport = 'product';
        $action = 'image';


        $entityId = $product->getEntityId();
        $sku = $product->getSku();
        $this->pushData($entityToExport, $entityId, $action, null, $sku);
    }

    public function getIsApiRunning()
    {
        $helper = Mage::helper('advancedexport/queue');
        $ignoreApiCalls = $helper->getIgnoreApi() == 1;
        if ($helper->getEnableQueue() == 0) {
            //queue is disabled... always ignore api calls
            $ignoreApiCalls = true;
        }

        $isApiRunning = false;

        if ($ignoreApiCalls) {
            //do not ignore api calls
            $isApiRunning = 
                Mage::getSingleton('api/server')->getAdapter() != null;
        }

        return $isApiRunning;
    }

    protected function pushData(
            $entityToExport, 
            $entityId, 
            $action, 
            $order = null, 
            $entityValue = '', 
            $scope = 'website', 
            $scopeId = 1
        )
    {
        //$store_website_id = Mage::app()->getStore()->getWebsiteId();
        //$store_website_id = Mage::app()->getWebsite()->getId();
//        //To Get the current store
//        $store = Mage::app()->getStore();
//        //To get Store Id
//        $store_id = Mage::app()->getStore()->getStoreId();
//        //To get Store Code
//        $store_code = Mage::app()->getStore()->getCode();
//        //To get Website Id
//        $store_website_id = Mage::app()->getStore()->getWebsiteId();
//        //To get Store Name
//        $store_name = Mage::app()->getStore()->getName();
//        //To get Store Status
//        $store_status = Mage::app()->getStore()->getIsActive();
//        //To get Store Home Url
//        $store_home_url = Mage::app()->getStore()->getHomeUrl();
//        //To get current running website id
//        $website_id = Mage::app()->getWebsite()->getId();
        $helper = Mage::Helper('advancedexport/queue');
        $isStoreSupported = Mage::Helper('advancedexport/website')
                ->isWebSiteStoreViewSupported($scopeId);
        if ($isStoreSupported) {
            if ($helper->getEnableQueue()) {
                $queueModel = Mage::getModel('advancedexport/queue');
                $scope = ($scopeId === null) ? 'default' : $scope;
                $scopeId = ($scopeId === null) ? 0 : $scopeId;

                //$scope_id = Mage::app()->getRequest()->getParam('store');
                $queueModel = $queueModel->addToQueue(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    $entityValue, 
                    $scope, 
                    $scopeId
                );
                //echo $queueModel->getId();
                if (!$helper->getSendAsync()) {
                    //send this one record immediately...
                    $queueModel->sendMixCollection(1, $queueModel->getId());
                }
            } else {
                if ($scope === 'store' && $entityToExport != 'product') {
                    $scope = 'website';  //overwrite
                    $scopeId = $this->getWebsiteIdfromStoreId($scopeId);
                }
                $this->sendNow(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    $order, 
                    $scopeId
                );
            }
        }
    }

    public function getWebsiteIdfromStoreId($storeId = 1)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }

    protected function sendNow(
            $entityToExport, 
            $entityId, 
            $action, 
            $order = NULL, 
            $scopeValue = null
        ) 
    {
        //$zipFile = $this
        //->generateAndSaveExportFile($entityToExport, $entityId, $action);
        $zipFile = Mage::helper('advancedexport')
                ->generateAndSaveExportFile(
                    $entityToExport, 
                    $entityId, 
                    $action, 
                    $order, 
                    $scopeValue
                );
        $enabledPassive = Mage::getModel('advancedexport/passivemode')
                ->getIsPassiveEnabled();
        if (!$enabledPassive) {
            Mage::helper('advancedexport/notificationSender')
                ->sendNotification(
                    $entityToExport, 
                    $entityId, 
                    $zipFile, 
                    $action, 
                    $scopeValue
                );
        }
    }

    public function customergroupSaveAfter($observer) 
    {
        $customerGroup = $observer->getEvent()->getObject();
        $this->pushCustomerGroupData($customerGroup, 'customergroupSaveAfter');
    }

    public function customergroupDeleteBefore($observer) 
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[customergroupDeleteBefore] File generation skipped. "
                . "API Call..." , 
                1, 
                'freestyle.log'
            );
            return true;
        }
        
        $entityToExport = 'customergroup';
        $action = 'deleted';
                
        $customerGroup = $observer->getEvent()->getObject();
        $customerGroupId = $customerGroup->getCustomerGroupId();
        $customerGroupCode = $customerGroup->getCustomerGroupCode();
        
        $this->pushData(
            $entityToExport, 
            $customerGroupId, 
            $action, 
            NULL, 
            $customerGroupCode
        );
    }
    
    public function systemConfigChangedSectionCustomer($observer) 
    {
        $this->handleDefaultCustomerGroupUpdate();
    }
    
    private function handleDefaultCustomerGroupUpdate()
    {
        $pushDataToQueue = false;
        $newDefaultCustomerGroupdId = 
                Mage::getStoreConfig('customer/create_account/default_group');
        $tokenModel = Mage::getModel('advancedexport/configuration')
                ->load('default_customer_group', 'config_code');
        
        if ($tokenModel->getId()) {
            $oldDefaultCustomerGroupId = $tokenModel->getConfigValue();
            if ($newDefaultCustomerGroupdId != $oldDefaultCustomerGroupId) {
                $tokenModel->setConfigValue($newDefaultCustomerGroupdId);
                $tokenModel->save();
                $pushDataToQueue = true;
            }
        } else {
            $data = array();
            $newConfData = Mage::getModel('advancedexport/configuration');
            $data['config_code'] = 'default_customer_group';
            $data['config_value'] = $newDefaultCustomerGroupdId;
            $newConfData->setData($data);
            $newConfData->save();
            $pushDataToQueue = true;
        }
        
        if ($pushDataToQueue) {
            $customerGroup = Mage::getModel('customer/group')
                    ->load($newDefaultCustomerGroupdId);
            $this->pushCustomerGroupData(
                $customerGroup, 
                'systemConfigChangedSectionCustomer_CustomerGroup'
            );
        }
    }
    
    private function pushCustomerGroupData($customerGroup, $source)
    {
        if (!$this->getIsExtEnabled()) {
            return false;
        }
        //prevent non-extension API call from triggering notification
        if ($this->getIsApiRunning()) {
            Mage::log(
                "[$source] File generation skipped. API Call...", 
                1, 
                'freestyle.log'
            );
            return true;
        }
        
        $entityToExport = 'customergroup';
        $action = 'added';
        
        $customerGroupId = $customerGroup->getCustomerGroupId();
        $customerGroupCode = $customerGroup->getCustomerGroupCode();
                
        $this->pushData(
            $entityToExport, 
            $customerGroupId, 
            $action, 
            NULL, 
            $customerGroupCode
        );
    }

}
