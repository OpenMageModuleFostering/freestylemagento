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

class Freestyle_Advancedexport_FrontprocessController 
extends Mage_Core_Controller_Front_Action
{

    const MAX_BATCH_SIZE = 1000000;
    const MAX_SOCKET_TIME_OUT = 9999999;
    const MAX_PROCESS_TIME_LIMIT = 99999999;

    public $exportErrors;

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }

    public function processexportAction()
    {
        $helper = Mage::Helper('advancedexport');
        if (!$helper->getIsExtEnabledForApi()) {
            return false;
        }

        // + Execution Time + Memory Limit
        set_time_limit(99999999);
        ini_set('memory_limit', $helper->getMemoryLimit() . 'M');

        $entityToExport = $this->getRequest()->getParam('export_entity', false);
        $batchsize = $this->getRequest()->getParam('batch_size', false);
        $tempfile = $this->getRequest()->getParam('tempfile', false);
        $batchesfile = $this->getRequest()->getParam('batchesfile', false);
        $dateTimeInit = $this->getRequest()->getParam('datetimeinit', '');

        $websiteToExport = $this->getRequest()->getParam('website_id', 1);

        $lastExportedStepInfo = array(
            'last_entity_value' => $this->getRequest()
                ->getParam('last_entity_value', 0),
            'last_record_value' => $this->getRequest()
                ->getParam('last_record_value', 0),
        );

        $fatal = false;

        try {
            //Read the temp file to get the data entityids 
            //that need files generated
            $fileFullPath = Mage::getBaseDir() . DS . 
                    $helper->getExportfolder() . DS . 
                    'tempFiles' . DS . $tempfile;
            $fileContent = file_get_contents($fileFullPath);
            $dataCollection = unserialize($fileContent);

            //Read the temp file to get the where 
            //we write the contents into a file
            $fileFullPathBatch = Mage::getBaseDir() . DS . 
                    $helper->getExportfolder() . DS . 
                    'tempFiles' . DS . $batchesfile;
            $fileContentBatches = file_get_contents($fileFullPathBatch);
            $batchesFiles = unserialize($fileContentBatches);
        } catch (Exception $e) {
            $exportStepResult['error'] = 
                    '[EXCEPTION] - Can not create temp Files. '
                    . 'Check Premissins for '
                    . $helper->getExportfolder()
                    . ' and advancedexport/tempFiles Folders. ';
            $exportStepResult['error_fatal'] = true;
            $fatal = true;
            Mage::log(
                'Can not [CREATE] temp Files. Check permissions for '
                . $helper->getExportfolder()
                . ' and advancedexport/tempFiles Folders'
                . $e->getMessage() . ' ' 
                . $e->getFile() . '::' 
                . (string) $e->getLine(), 
                1, 
                'freestyle.log'
            );
        }

        if (!$fatal) {
            //now generate the files
            $fsAbstract = Mage::getModel(
                'advancedexport/exportmodels_abstract'
            );
            $exportStepResult = $fsAbstract
                    ->getExportDataMemoryControll(
                        $entityToExport, 
                        $batchsize, 
                        $dataCollection, 
                        $tempfile, 
                        $dateTimeInit, 
                        $lastExportedStepInfo, 
                        $batchesFiles, 
                        $websiteToExport
                    );
            file_put_contents(
                $fileFullPathBatch, 
                serialize($exportStepResult['batches_files'])
            );
        }

        $this->getResponse()->setBody(serialize($exportStepResult));
    }

    /* Action For Processing Api call */

    public function startApiExportAction()
    {
        if (!$this->getHelper()->getIsExtEnabledForApi()) {
            return false;
        }

        $fatal = false;

        $this->exportErrors = array();
        $helper = $this->getHelper();

        $entityToExport = $this->getRequest()->getParam('export_entity', false);
        $startDate = $this->getRequest()->getParam('date_start', false);
        $endDate = $this->getRequest()->getParam('date_end', false);
        $idsToExport = (string) $this->getRequest()
                ->getParam('ids_to_export', '');
        $batchsize = (int) $this->getRequest()
                ->getParam('batch_size', self::MAX_BATCH_SIZE);
        //$requestId = (int) $this->getRequest()->getParam('requestid', false);

        if (!$batchsize) {
            $batchsize = self::MAX_BATCH_SIZE;
        }

        $date = $helper->getCurrentTime();
        $dateTimeStart = new DateTime();

        $priority = $helper
                ->getParamsPriority($startDate, $endDate, $idsToExport);
        $fsAbstract = Mage::getModel('advancedexport/exportmodels_abstract');
        $dataCollection = $fsAbstract
                ->getEntityIdsCollection($entityToExport, $priority)->getData();


        $file = 'datatemp' . $date . '.txt';
        $filesBatchArray = 'batchFiles' . $date . '.txt';

        try {
            $premissionsResult = $helper->checkFoldersPremissions();
            if (!$premissionsResult) {
                $fatal = true;
                $this->exportErrors[] = 
                        'Can Not put File To "tempFiles" or '
                        . '"advancedexport" Folder. Check Premissions';
                Mage::log(
                    'Can Not put File To "tempFiles" or '
                    . '"advancedexport" Folder. Check Premissions', 
                    1, 
                    'freestyle.log'
                );
            } else {
                $tempfile = Mage::getBaseDir() . DS . 
                        $helper->getExportfolder() . DS . 
                        'tempFiles' . DS . $file;
                $putResult = file_put_contents(
                    $tempfile, 
                    serialize($dataCollection)
                );

                $tempfileBatches = Mage::getBaseDir() . DS . 
                        $helper->getExportfolder() . DS . 
                        'tempFiles' . DS . $filesBatchArray;
                $putResult = file_put_contents(
                    $tempfileBatches, 
                    serialize(array())
                );
            }
        } catch (Exception $e) {
            $this->exportErrors[] = 'Can Not put File To "tempFiles" or '
                    . '"advancedexport" Folder. Check permissions';
            Mage::log(
                'Can not [WRITE] to "tempFiles" or '
                . '"advancedexport" Folder. Check permissions', 
                1, 
                'freestyle.log'
            );
            $fatal = true;
        }

        /* + Execution Time + Memory Limit */
        ini_set('default_socket_timeout', self::MAX_SOCKET_TIME_OUT);
        set_time_limit(self::MAX_PROCESS_TIME_LIMIT);
        ini_set('memory_limit', $helper->getMemoryLimit() . 'M');

        $url = Mage::getUrl(
            'advancedexport/frontprocess/processexport', 
            array('_secure' => true)
        ) . '?export_entity=' . $entityToExport 
            . '&batch_size=' . $batchsize 
            . '&tempfile=' . $file 
            . '&batchesfile=' . $filesBatchArray 
            . '&datetimeinit=' . $date;

        $stepCounter = 0;

        if (!$fatal) {
            do {
                $httpResult = file_get_contents($url);
                $goFromRequests = false;

                if (!$httpResult) {
                    $this->exportErrors[] = 'Server Response Error';
                    Mage::log(
                        '[WARNING] - Server Response Error ' . $e->getMessage() 
                        . ' ' .$e->getFile().'::'.(string)$e->getLine(), 
                        1, 
                        'freestyle.log'
                    );
                }

                try {
                    $structure = unserialize($httpResult);

                    if (isset($structure['error_fatal'])) {
                        if (count($structure['error'])) {
                            foreach ($structure['error'] as $one) {
                                $this->exportErrors[] = $one;
                                Mage::log($one, 1, 'freestyle.log');
                            }
                        }
                        break;
                    }

                    if (!$structure['export_finished']) {

                        // Add Params To Url For Next Step
                        $url .= '&last_entity_value=' 
                                . $structure['last_exported_info']['value'] 
                                . '&last_record_value=' 
                                . $structure['last_exported_info']
                                ['record_number'];
                        Mage::log(
                            '[INFO] - ' . $stepCounter . '. Export Next Step. '
                            . 'Record Start : ' 
                            . $structure['last_exported_info']['record_number'],
                            1, 
                            'freestyle.log'
                        );
                    }

                    if (count($structure['error'])) {
                        foreach ($structure['error'] as $one) {
                            $this->exportErrors[] = $one;
                            Mage::log($one, 1, 'freestyle.log');
                        }
                    }
                } catch (Exception $e) {
                    $this->exportErrors[] = 'Server Response Error';
                    Mage::log(
                        '[EXCEPTION] - Server Response Error ' 
                        . $e->getMessage(). ' ' .$e->getFile() . '::' 
                        . (string)$e->getLine(), 
                        1, 
                        'freestyle.log'
                    );
                    Mage::log(
                        '[EXCEPTION] - Server Response: ' . $httpResult, 
                        1, 
                        'freestyle.log'
                    );

                    $goFromRequests = true;
                    $structure['action'] = 'end';
                    $structure['export_finished'] = 1;
                }

                $stepCounter++;
            } while (($structure['action'] == 'next_step') && (!$structure['export_finished']) && (!$goFromRequests));
        }


        $dateTimeEnd = new DateTime();

        /* Save Export To History */
        if (isset($structure['zip_file'])) {
            $fileResult = $structure['zip_file'];
        } else {
            $fileResult = array();
        }

        $historyModel = Mage::getModel('advancedexport/history');
        $historyData['export_date'] = $dateTimeStart
                ->format('Y-m-d H:i:s');
        $historyData['export_date_time_start'] = $dateTimeStart
                ->format('Y-m-d H:i:s');
        $historyData['export_date_time_end'] = $dateTimeEnd
                ->format('Y-m-d H:i:s');
        $historyData['created_files'] = serialize($fileResult);
        $historyData['init_from'] = 'API Request';
        $historyData['export_entity'] = $entityToExport;
        $historyData['errors'] = serialize($this->exportErrors);
        $historyModel->setData($historyData);
        $historyModel->save();

        /* Send Notification To NextGen */
        $fileLink = 'bad_file';
        if (isset($fileResult[0])) {
            if ($fileResult[0]) {
                $fileLink = $fileResult[0];
            }
        }
        Mage::helper('advancedexport/notificationSender')
                ->setIsApiCall(true)
                ->sendNotification($entityToExport, $entityId = -1, $fileLink);
    }
}
