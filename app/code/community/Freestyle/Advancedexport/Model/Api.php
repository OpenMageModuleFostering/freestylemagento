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

class Freestyle_Advancedexport_Model_Api 
    extends Mage_Api_Model_Resource_Abstract
{

    const MAX_BATCH_SIZE = 1000000;
    const MAX_SOCKET_TIME_OUT = 9999999;
    const MAX_PROCESS_TIME_LIMIT = 99999999;

    public $exportErrors;

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }

    public function startexport($parametrs)
    {
        $helper = $this->getHelper();
        if (!$helper->getIsExtEnabledForApi()) {
            return 'access denied';
        }


        Mage::log('[INFO] - API Call Start... ', 1, 'freestyle.log');

        try {
            $parametrsArray = (array) json_decode($parametrs);

            $filters = (array) $parametrsArray['filters'];
            $processRequestId = $parametrsArray['requestid'];
            $entity = $parametrsArray['entity'];
            $batchSize = $parametrsArray['batch_size'];

            //return  $filters;
        } catch (Exception $e) {
            Mage::log(
                '[EXCEPTION] - Wrong Parameters Format; ' . $e->getMessage() 
                . ' ' . $e->getFile() . '::' . $e->getLine(), 1, 'freestyle.log'
            );
            $this->_fault('data_invalid', 'Wrong Parameters Format');
            return false;
        }

        if (!$processRequestId) {
            $this->_fault('data_invalid', 'Missed Process Id');
        } elseif (!$entity) {
            $this->_fault('data_invalid', 'Missed Entity Type');
        } elseif (!$batchSize) {
            $this->_fault('data_invalid', 'Missed Batch Size');
        }

        if (!isset($filters['ids'])) {
            $filters['ids'] = '';
        }
        if (!isset($filters['datefrom'])) {
            $filters['datefrom'] = '';
        }
        if (!isset($filters['dateto'])) {
            $filters['dateto'] = '';
        }

        try {
            $filterPriority = $helper->getParamsPriority(
                $filters['datefrom'], 
                $filters['dateto'], 
                $filters['ids']
            );
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }

        if (!$helper->getEntityModel($entity)) {
            $this->_fault(
                'data_invalid', 
                'Unsupported Entity Type: ' . $entity
            );
        }

        $callResult = $this->startExportProcess(
            $processRequestId, 
            $entity, 
            $filterPriority, 
            $batchSize
        );
        return $callResult;
    }

    public function startExportProcess(
        $processRequestId, 
        $entity, 
        $filters, 
        $batchSize
    )
    {
        $url = Mage::getUrl(
            'advancedexport/frontprocess/startApiExport', 
            array('_secure' => true)
        ) . '?requestid=' . $processRequestId . '&export_entity=' . $entity . 
            '&batch_size=' . $batchSize . '&date_start=' . 
            $filters['datefrom'] . '&date_end=' . $filters['dateto'] . 
            '&ids_to_export=' . $filters['ids'];

        /* $result = file_get_contents($url); */

        try {
            $params = array();
            $params['export_entity'] = $entity;
            $params['batch_size'] = $batchSize;
            $params['date_start'] = $filters['datefrom'];
            $params['date_end'] = $filters['dateto'];
            $params['ids_to_export'] = $filters['ids'];

            foreach ($params as $key => &$val) {
                if (is_array($val)) {
                    $val = implode(',', $val);
                }
                $postParams[] = $key . '=' . urlencode($val);
            }
            $postString = implode('&', $postParams);

            $parts = parse_url($url);
            
            $errno = false;
            $errstr = false;

            $fSock = fsockopen(
                $parts['host'], 
                isset($parts['port']) ? $parts['port'] : 80, 
                $errno, 
                $errstr, 
                30
            );

            $out = "POST " . $parts['path'] . " HTTP/1.1\r\n";
            $out.= "Host: " . $parts['host'] . "\r\n";
            $out.= "Content-Type: application/x-www-form-urlencoded\r\n";
            $out.= "Content-Length: " . strlen($postString) . "\r\n";
            $out.= "Connection: Close\r\n\r\n";
            if (isset($postString)) {
                $out.= $postString;
            }

            fwrite($fSock, $out);
            fclose($fSock);
        } catch (Exception $e) {
            $exceptionMessage = '[EXCEPTION] - API Call Error: ' 
                . $e->getMessage() . ' ' . $e->getFile() . '::' . $e->getLine();
            Mage::log($exceptionMessage, 1, 'freestyle.log');
            return 'error';
        }

        //return $result;
        return 'success';
    }

    public function getentityxml($entityType, $entityId, $storeId = null)
    {
        $helper = $this->getHelper();
        if (!$helper->getIsExtEnabledForApi()) {
            return 'access denied';
        }

        if ($entityType === 'product' && $storeId === null) {
            //store id value is now required for product
            $this->_fault(
                'data_invalid', 
                'Store Id is required for Product Entity'
            );
        }

        if ($entityId === null) {
            //entity id value is required
            $this->_fault(
                'data_invalid', 
                'Entity Id is required for Product Entity'
            );
        }

        $model = $helper->getEntityModel($entityType);
        if (!$model) {
            $this->_fault(
                'data_invalid', 
                'Unsupported Entity Type: ' . $entityType
            );
        }

        $entityData = false;
        $entityXml = '';
        try {
            //initialize XML stuffs
            $xmlVersionHeader = $helper->getXmlVersionHeader();
            $xmlVersion = $helper->getMainXmlTagWithParams();
            $xmlEndTag = $helper->getMainXmlTagEnd();
            if ($entityType === 'product') {
                $entityData = $model->info($entityId, $storeId);
            } else {
                $entityData = $model->info($entityId);
            }
            $data = array($entityType => $entityData);
            $entityXmlBase = 
                Mage::getModel('advancedexport/exportmodels_abstract')
                    ->arrayToXml($entityType, $data);
            $entityXml = $xmlVersionHeader 
                . "<$xmlVersion>" 
                . $entityXmlBase 
                . "</$xmlEndTag>";
        } catch (Mage_Core_Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        } catch (Exception $e) {
            $this->_fault('data_invalid', $e->getMessage());
        }
        return $entityXml;
    }

    public function getallcarriers()
    {
        Mage::log('API Call Start... ', 1, 'freestyle.log');
        $allCarriers = Mage::getSingleton('shipping/config')->getAllCarriers();
        foreach ($allCarriers as $carrierCode => $carrierModel) {
            $options = array();
            if ($carrierMethods = $carrierModel->getAllowedMethods()) {
                foreach ($carrierMethods as $methodCode => $method) {
                    $code = $carrierCode . '_' . $methodCode;
                    $options[] = array('value' => $code, 'label' => $method);
                }
                $carrierString = 'carriers/' . $carrierCode . '/title';
                $carrierTitle = Mage::getStoreConfig($carrierString);
            }
            $methodsAll[] = array(
                'value' => $options, 
                'label' => $carrierTitle
            );
        }
        return $methodsAll;
    }
}
