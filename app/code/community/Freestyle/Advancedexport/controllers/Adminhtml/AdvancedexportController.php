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

class Freestyle_Advancedexport_Adminhtml_AdvancedexportController 
extends Mage_Adminhtml_Controller_Action
{

    const MAX_BATCH_SIZE = 1000000;
    const MAX_SOCKET_TIME_OUT = 9999999;
    const MAX_PROCESS_TIME_LIMIT = 99999999;

    public $exportErrors;

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }

    protected function _initAction()
    {
        $this->loadLayout()->_setActiveMenu('advancedexport');
        if (Mage::getSingleton('cms/wysiwyg_config')->isEnabled()) {
            $this->getLayout()->getBlock('head')->setCanLoadTinyMce(true);
        }
        return $this;
    }

    //apply ACL
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')
                        ->isAllowed('freestyle_advancedexport/exportblock');
    }

    public function indexAction()
    {
        if (!$this->getHelper()->getIsExtEnabledForApi()) {
            $this->_initAction();
            $displayText = '<h1>Extension Disabled</h1><br/>' .
                    '<p>Please go to '
                    . '<strong>System >> '
                    . 'Configuration >> '
                    . 'Freestyle >> '
                    . 'Advanced Export >> '
                    . 'Settings</strong> to enable.</p>';
            //$block = $this->getLayout()->createBlock('core/text')
            //->setText('<h1>Extension Disabled</h1>');
            $block = $this->getLayout()->createBlock('core/text')
                    ->setText($displayText);
            $this->_addContent($block);
            $this->renderLayout();
        } else {
            $this->_initAction();
            $this->_addContent(
                $this->getLayout()
                    ->createBlock('advancedexport/adminhtml_form_edit')
            )
                ->_addLeft(
                    $this->getLayout()
                    ->createBlock('advancedexport/adminhtml_form_edit_tabs')
                );
            $this->renderLayout();
        }
    }

    public function processdataAction()
    {
        $fatal = false;

        $this->exportErrors = array();
        $helper = $this->getHelper();
        //$postData = $this->getRequest()->getPost();  /*unused*/

        $entityToExport = $this->getRequest()->getPost('export_entity', false);
        $startDate = $this->getRequest()->getPost('date_start', false);
        $endDate = $this->getRequest()->getPost('date_end', false);
        $idsToExport = (string) $this->getRequest()
                ->getPost('ids_to_export', '');
        $batchsize = (int) $this->getRequest()
                ->getPost('batch_size', self::MAX_BATCH_SIZE);

        $getPassiveModel = $this->getRequest()
                ->getPost('switchToPassiveMode', 'no_action');

        $sendNotification = $this->getRequest()
                ->getPost('sendnotificationflag', 'no_action');
        $passiveModeIds = $this->getRequest()
                ->getPost('notifyId', '');

        $resendNotification = $this->getRequest()
                ->getPost('resendnotification', 'no_action');
        $resendId = $this->getRequest()->getPost('notifyIncrementId', '');

        //DE-8384
        $websiteToExport = $this->getRequest()->getPost('website_id', 1);

        //Proccess Passive Mode
        $exportFiles = ($getPassiveModel == 'no_action') 
                && ($sendNotification == 'no_action') 
                && ($resendNotification == 'no_action');
        if ($exportFiles) {


            //Add last Params to Session
            Mage::getSingleton('adminhtml/session')
                ->setData(
                    'advancedExportValues', array(
                    'export_entity' => $entityToExport,
                    'batch_size' => $batchsize,
                    'date_start' => $startDate,
                    'date_end' => $endDate,
                    'ids_to_export' => $idsToExport,
                    'website_id' => $websiteToExport
                    )
                );

            if (!$batchsize) {
                $batchsize = self::MAX_BATCH_SIZE;
            }

            $date = $helper->getCurrentTime();

            $dateTimeStart = new DateTime();

            $priority = $helper
                    ->getParamsPriority($startDate, $endDate, $idsToExport);
            
            $fsAbstract = 
                    Mage::getModel('advancedexport/exportmodels_abstract');
            $dataCollection = $fsAbstract->getEntityIdsCollection(
                $entityToExport, 
                $priority, 
                $websiteToExport
            )->getData();

            $file = 'datatemp' . $date . '.txt';
            $filesBatchArray = 'batchFiles' . $date . '.txt';

            try {
                $permissionsResult = $helper->checkFoldersPremissions();
                if (!$permissionsResult) {
                    $fatal = true;
                    $this->exportErrors[] = 'Can not put write to '
                        . '"freestyleexport" or '
                        . '"freestyleexport/tempFiles" folder(s). '
                        . 'Check permissions';
                    Mage::log(
                        'Can not [WRITE] to "freestyleexport" or '
                        . '"freestyleexport/tempFiles" folder(s). '
                        . 'Check permissions', 
                        1, 
                        'freestyle.log'
                    );
                } else {
                    $tempfile = Mage::getBaseDir() . DS 
                            . Mage::Helper('advancedexport')->getExportfolder() 
                            . DS . 'tempFiles' . DS . $file;
                    $putResult = file_put_contents(
                        $tempfile, 
                        serialize($dataCollection)
                    );

                    $tempfileBatches = Mage::getBaseDir() . DS 
                            . Mage::Helper('advancedexport')->getExportfolder() 
                            . DS . 'tempFiles' . DS . $filesBatchArray;
                    $putResult = file_put_contents(
                        $tempfileBatches, 
                        serialize(array())
                    );
                }
            } catch (Exception $e) {
                $this->exportErrors[] = 'Can not write to "freestyleexport" '
                        . 'or "freestyleexport/tempFiles" folder(s). '
                        . 'Check permissions';
                Mage::log(
                    'Can not [WRITE] to "freestyleexport" '
                    . 'or "freestyleexport/tempFiles" folder(s). '
                    . 'Check permissions', 
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
            ) 
                    . '?export_entity=' 
                    . $entityToExport 
                    . '&batch_size=' . $batchsize 
                    . '&tempfile=' . $file 
                    . '&batchesfile=' . $filesBatchArray 
                    . '&datetimeinit=' . $date 
                    . '&website_id=' . $websiteToExport;

            $stepCounter = 0;

            if (!$fatal) {
                do {
                    $httpResult = file_get_contents($url);
                    $goFromRequests = false;

                    if ($httpResult === false) {
                        $this->exportErrors[] = 'Server Response Error';
                        $errorMessage = 'Server Response Error is blank '
                                . 'export_entity=' . $entityToExport
                                . '&batch_size=' . $batchsize
                                . '&tempfile=' . $file
                                . '&batchesfile=' . $filesBatchArray
                                . '&datetimeinit=' . $date;
                        Mage::log($errorMessage, 1, 'freestyle.log');
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
                                $stepCounter 
                                . '. Export Next Step. Record Start : ' 
                                . $structure['last_exported_info']
                                ['record_number'], 
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
                            'Server Response Error ' . $e->getMessage(), 
                            1, 
                            'freestyle.log'
                        );
                        Mage::log(
                            'Server Response: ' . $httpResult, 
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
            $historyData['export_date'] = $dateTimeStart->format('Y-m-d H:i:s');
            $historyData['export_date_time_start'] = $dateTimeStart
                    ->format('Y-m-d H:i:s');
            $historyData['export_date_time_end'] = $dateTimeEnd
                    ->format('Y-m-d H:i:s');
            $historyData['created_files'] = serialize($fileResult);
            $historyData['init_from'] = 'Magento Admin Panel';
            $historyData['export_entity'] = $entityToExport;
            $historyData['errors'] = serialize($this->exportErrors);
            $historyModel->setData($historyData);
            $historyModel->save();
        } elseif ($sendNotification == 'sendnotify') {

            /* Send Notification */
            $result = Mage::getModel('advancedexport/passivemode')
                    ->sendNotification($passiveModeIds);
        } elseif ($resendNotification == 'sendnotify') {
            /* Send Notification */
            if (Mage::helper('advancedexport/queue')->getEnableQueue()) {
                $result = Mage::getModel('advancedexport/queue')
                        ->resendOrder(trim($resendId));
            } else {
                $result = Mage::getModel('advancedexport/resend')
                        ->sendNotification(trim($resendId));
            }
        } else {

            /* For Passive Mode */
            $result = $helper->processPassiveMode($getPassiveModel);

            if (!empty($result['errors'])) {
                foreach ($result['errors'] as $one) {
                    Mage::getSingleton('core/session')->addError($one);
                }
            }
        }

        $this->_redirectReferer();
    }

    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock(
                    'advancedexport/adminhtml_form_edit_tab_history'
                )->toHtml()
        );
    }

    public function gridpassiveAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('advancedexport/adminhtml_form_edit_tab_passive')
                ->toHtml()
        );
    }

    public function gridqueueAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()
                ->createBlock('advancedexport/adminhtml_form_edit_tab_queue')
                ->toHtml()
        );
    }

    public function deleteexportfilesAction()
    {
        $path = Mage::getBaseDir() . DS . 
                Mage::Helper('advancedexport')->getExportfolder();
        $this->deleteFiles($path);
        $this->_redirectReferer();
    }

    public function deleteFiles($path)
    {
        if (file_exists($path)) {
            $directoryIterator = new DirectoryIterator($path);

            foreach ($directoryIterator as $fileInfo) {
                $filePath = $fileInfo->getPathname();
                if (!$fileInfo->isDot()) {
                    if ($fileInfo->isFile()) {
                        unlink($filePath);
                    } elseif ($fileInfo->isDir()) {
                        $this->deleteFiles($filePath);
                    }
                }
            }
        }
    }

    public function checkAction()
    {
        $result = 1;
        Mage::app()->getResponse()->setBody($result);
    }

    public function testconnectionAction()
    {
        //echo "You found me!\n";
        $result['message'] = "Unable to connect.\n" 
                . "Please confirm the credentials "
                . "and SAVE config before attempting again.";
        $result['type'] = 'error'; //success, error, warn, notice
        $objAuthenticate = Mage::helper("advancedexport/notificationSender");
    /*
      $authenticationUrl = Mage::app()->getRequest()->getParam('authurl');
      $authUser = Mage::app()->getRequest()->getParam('authuser');
      $authPwd = Mage::app()->getRequest()->getParam('authpwd');
      parameters:{authuser:$('freestyle_advancedexport_api_api_username').value,
      authpwd: $('freestyle_advancedexport_api_api_password').value,
      authurl: $('freestyle_advancedexport_api_api_authorization_url').value
      },
     *
     *
     */
        //A Error Message
        //Mage::getSingleton('checkout/session')
        //->addError("Your cart has been updated successfully!");
        //A Info Message (See link below)
        //Mage::getSingleton('checkout/session')
        //->addNotice("This is just a FYI message...");
        try {
            //$token = $objAuthenticate->setIsApiCall(false)
            //->testconnection($authenticationUrl, $authUser, $authPwd);
            $token = $objAuthenticate->setIsApiCall(false)->authentification();
        } catch (Exception $e) {
            $result['message'] = $e->getMessage();
            Mage::getSingleton('adminhtml/session')->addError($result);
        }
        //echo "Token = ".$token;
        if ($token) {
            $result['message'] = "Success!\n" . "Token Received:" . $token;
            $result['type'] = 'success';
            //Mage::getSingleton('adminhtml/session')->addSuccess($result);
        } else {
            //Mage::getSingleton('adminhtml/session')->addError($result);
        }
        //Mage::app()->getResponse()->setBody($result);
        Mage::app()->getResponse()
                ->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function sendjustoneAction()
    {
        $entityToExport = $this->getRequest()->getParam('id', false);
        $result['message'] = "Nice!";
        $result['type'] = 'success'; //success, error, warn, notice
        if ($entityToExport) {
            $queueModel = Mage::getModel('advancedexport/queue');
            //$queueModel->addToQueue($entityToExport,$entityId,$action);
            //send this one record immediately...
            //$queueModel->getEntitiesToExport(1, $entityToExport); //refactored
            $queueModel->sendMixCollection(1, $entityToExport);
        }
        //Mage::getSingleton('adminhtml/session')->addSuccess('BOOM!');
        //$this->_redirectReferer();
        Mage::app()->getResponse()
                ->setBody(Mage::helper('core')->jsonEncode($result));
    }

    public function batchSendAction()
    {
        $idsToSend = $this->getRequest()->getParam('queue');

        if (!is_array($idsToSend)) {
            Mage::getSingleton('adminhtml/session')
                    ->addError($this->__('Please select record(s) to send.'));
        } else {
            try {
                //$model = Mage::getSingleton('my_ads/listing');
                $queueModel = Mage::getModel('advancedexport/queue');
                //$model->load($adId)->delete();
                $numOfIdsToSend = count($idsToSend);
                $numLeftToProcess = $numOfIdsToSend;
                $batchSize = (int) Mage::Helper('advancedexport/queue')
                        ->getQueueBatchSize();
                $counter = 0;
                $counterForErrors = 0;
                do {
                    if ($numOfIdsToSend == 0) {
                        break;
                    }
                    $batchToSend = array_slice(
                        $idsToSend, 
                        $counter, 
                        $batchSize
                    );
                    //$queueModel
                    //->getEntitiesToExport(count($batchToSend), $batchToSend);
                    $queueModel
                        ->sendMixCollection(count($batchToSend), $batchToSend);
                    $numLeftToProcess = $numLeftToProcess - count($batchToSend);
                    $counter = $counter + count($batchToSend);
                    if ($queueModel->_transmissionHasError === true)
                    {
                        $counterForErrors += count($batchToSend);
                    }
                } while ($numLeftToProcess > 0);
                
                if ($counterForErrors > 0)
                {
                    Mage::getSingleton('adminhtml/session')
                        ->addError(
                            $this->__(
                                '%d record(s) were sent unsuccessfully.', 
                                count($counterForErrors)
                            )
                        );                    
                } elseif (count($idsToSend) - $counterForErrors > 0) {
                Mage::getSingleton('adminhtml/session')
                        ->addSuccess(
                            $this->__(
                                'Total of %d record(s) were sent.', 
                                count($idsToSend) - $counterForErrors
                            )
                        );
                }
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }

    //DE-11662
    public function batchUpdateAction()
    {
        $idsToSend = $this->getRequest()->getParam('queue');

        if (!is_array($idsToSend)) {
            Mage::getSingleton('adminhtml/session')
                    ->addError($this->__('Please select record(s) to send.'));
        } else {
            try {
                //$model = Mage::getSingleton('my_ads/listing');
                $queueModel = Mage::getModel('advancedexport/queue');
                foreach ($idsToSend as $entityToExport) {
                    //$model->load($adId)->delete();
                    $queueModel->load($entityToExport);
                    $queueModel->setStatus('sent')->save();
                }
                Mage::getSingleton('adminhtml/session')
                        ->addSuccess(
                            $this->__(
                                'Total of %d record(s) were updated.', 
                                count($idsToSend)
                            )
                        );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')
                        ->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/');
    }
}
