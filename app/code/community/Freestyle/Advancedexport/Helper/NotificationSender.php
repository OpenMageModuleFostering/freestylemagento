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

class Freestyle_Advancedexport_Helper_NotificationSender 
    extends Mage_Core_Helper_Abstract
{

    public $isApiCall;
    public $isSendButton;
    public $sendErrorMessage;

    public function setIsApiCall($value)
    {
        $this->isApiCall = $value;
        return $this;
    }

    public function setIsSendButton($value)
    {
        $this->isSendButton = $value;
        return $this;
    }

    public function getIsApiCall()
    {
        return $this->isApiCall;
    }

    public function getIsSendButton()
    {
        return $this->isSendButton;
    }

    public function getNotificationType()
    {
        if ($this->getIsApiCall()) {
            return 1;
        }

        if ($this->getIsSendButton()) {
            return 2;
        }

        return 0;
    }

    public function getChanelId()
    {
        return Mage::Helper('advancedexport')->getChanelId();
    }

    public function getEntityType($entity)
    {
        $types = array(
            'order' => 0,
            'customer' => 1,
            'product' => 2,
            'category' => 3,
            'customergroup' => 4
        );

        if (isset($types[$entity])) {
            return $types[$entity];
        }

        return 'not_defined';
    }

    public function getEntityEventType($event)
    {
        if ($this->getIsApiCall() || $this->getIsSendButton()) {
            return -1;
        }


        $eventsTypes = array(
            'added' => 1,
            'updated' => 1,
            'deleted' => 3,
        );

        if (isset($eventsTypes[$event])) {
            return $eventsTypes[$event];
        }

        return 'not_defined';
    }

    public function getTokenFromDb()
    {
        $tokenModel = Mage::getModel('advancedexport/configuration')
                ->load('token', 'config_code');
        if ($tokenModel->getId()) {
            return $tokenModel->getConfigValue();
        }

        return false;
    }

    public function putTokenToBase($token)
    {
        $data = array();
        $tokenModel = Mage::getModel('advancedexport/configuration')
                ->load('token', 'config_code');
        if ($tokenModel->getId()) {
            $tokenModel->setConfigValue($token);
            $tokenModel->save();
        } else {
            $newConfData = Mage::getModel('advancedexport/configuration');
            $data['config_code'] = 'token';
            $data['config_value'] = $token;
            $newConfData->setData($data);
            $newConfData->save();
        }
    }

    public function authentification()
    {
        $mainHelper = Mage::Helper('advancedexport');
        $authenticationUrl = $mainHelper->getApiAuthenticationUrl();
        $authUser = $mainHelper->getApiUserName();
        $authPwd = $mainHelper->getApiUserPassword();
        unset($mainHelper);
        return $this->testconnection($authenticationUrl, $authUser, $authPwd);
    }

    public function testconnection($authenticationUrl, $authUser, $authPwd)
    {
        $json = json_encode(
            array(
                "UserName" => $authUser,
                "Password" => $authPwd
            )
        );
        $curlObj = Mage::helper("advancedexport/curl");
        if ($curlObj->curlSend($authenticationUrl, $json)) {
            //we got a good response
            try {
                $dataObj = json_decode($curlObj->curlResult);
                $token = $dataObj->Data;
                if ($token) {
                    $this->putTokenToBase($token);
                } else {
                    Mage::log(
                        '[WARNING] - Can not [AUTHENTICATE] with Freestyle: ', 
                        1, 
                        'freestyle.log'
                    );
                    return false;
                }
            } catch (Exception $e) {
                Mage::log(
                    '[EXCEPTION] - Can not [AUTHENTICATE] with Freestyle: ' 
                    . $e->getMessage() . ' ' . $e->getFile() . '::' 
                    . $e->getLine(), 
                    1, 
                    'freestyle.log'
                );
                return false;
            }
        } else {
            return false;
        }
        unset($curlObj);
        return $token;
    }

    public function sendNotification(
        $entityToExport, 
        $entityIdForNotify, 
        $zipFile, 
        $entityEvent = false, 
        $scopeValue = 1
    )
    {
        Mage::log('[INFO] - Send Notification Start', 1, 'freestyle.log');

        $mainHelper = Mage::Helper('advancedexport');

        /* Authentificate process */

        $notificationUrl = $mainHelper->getApiNotificationUrl();

        $token = $this->retreiveToken();

        /* Required Info */

        /* Notification Type: Although there are 2 types, 
         * only 0 is supported as the value (0 = EntityChanged), 
         * value 1 = API Request */
        $notificationType = $this->getNotificationType();
        $channelId = $mainHelper->getChanelId($scopeValue);
        $entityType = $this->getEntityType($entityToExport);
        $entityEventType = $this->getEntityEventType($entityEvent);
        $entityId = $entityIdForNotify;
        $dataXmlFileUrl = $zipFile;

        /* Only When Passive mod disabled. 
         * For enabled Passive Mode will be used other solution */
        $numberOfEntities = 1;
        /* -------------------------------------------------------------------*/

        /* Create Data Array for sending */

        $params = array();

        $params["Token"] = $token;
        $params["NotificationType"] = $notificationType;
        $params["channelId"] = $channelId;
        $params["entityType"] = $entityType;
        $params["entityEventType"] = $entityEventType;
        $params["entityId"] = $entityId;
        $fileLink = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB) 
            . Mage::Helper('advancedexport')->getExportfolder() . DS 
            . $dataXmlFileUrl;
        $params["dataXmlFileUrl"] = (string) $fileLink;
        $params["numberOfEntities"] = $numberOfEntities;
        Mage::log(
            '[INFO] - Notify Specs: Event=[' . $entityEvent 
            . "]; Entity Type=[" 
            . $entityToExport . "]; Entity ID=[" . $entityId . "]", 
            1, 
            'freestyle.log'
        );
        Mage::log(
            '[INFO] - Notify Parameters for Freestyle:  ' 
            . serialize($params), 
            1, 
            'freestyle.log'
        );

        /* Send Notification (Curl) */
        $attemptCount = 0;
        $isErrorInStatus = false;

        do {
            $attemptCount++;
            $jsonDataString = json_encode($params);
            $curlObj = Mage::helper("advancedexport/curl");
            if ($curlObj->curlSend($notificationUrl, $jsonDataString)) {
                //we got something good?
                $decodeResult = json_decode($curlObj->curlResult);
                $status = $decodeResult->Data;
                Mage::log(
                    'Try To Notify: ' 
                    . $curlObj->curlResult, 
                    1, 
                    'freestyle.log'
                );
                $isErrorInStatus = strpos($status, 'Error');

                if ($isErrorInStatus !== false) {
                    Mage::log(
                        '[ALERT] - Error in response: ' . $status 
                        . ', token is - ' . $params["Token"] 
                        . ' , try again.... ' . 'Attempt Number is: ' 
                        . $attemptCount, 
                        1, 
                        'freestyle.log'
                    );
                    $token = $this->authentification();
                    if ($token != false) {
                        $params["Token"] = $token;
                    } else {
                        $attemptCount++;
                    }
                } else {
                    Mage::log(
                        '[INFO] - Notification have been sent', 
                        1, 
                        'freestyle.log'
                    );
                }
            }
        } while (($attemptCount < 3) && ($isErrorInStatus !== false));

        Mage::log('[INFO] - Send Notification End', 1, 'freestyle.log');

        if ($isErrorInStatus !== false) {
            return false;
        }

        //write it to the history
        $this->writeHistory($dataXmlFileUrl, $entityToExport);
        return true;
    }
    
    protected function writeHistory($dataXmlFileUrl, $entityToExport)
    {
        $dateTimeStart = new DateTime();
        $historyModel = Mage::getModel('advancedexport/history');
        $historyData['export_date'] = $dateTimeStart->format('Y-m-d H:i:s');
        $historyData['export_date_time_start'] = 
                $dateTimeStart->format('Y-m-d H:i:s');
        $historyData['export_date_time_end'] = 
                $dateTimeStart->format('Y-m-d H:i:s');
        $writeFile[] = $dataXmlFileUrl;
        $historyData['created_files'] = serialize($writeFile);
        $historyData['init_from'] = 'Event Observer';
        $historyData['export_entity'] = $entityToExport;
        //$historyData['errors'] = serialize($this->exportErrors);
        $historyModel->setData($historyData);
        $historyModel->save();
        return;
    }
    
    public function sendQueue($jsonOutArray, $websiteId = 1)
    {
        if (empty($jsonOutArray)) {
            return false;
        }

        $mainHelper = Mage::Helper('advancedexport');

        /* Authenticate process */
        $notificationUrl = Mage::Helper('advancedexport/queue')
                ->getQueueServiceUrl();
        $channelId = $mainHelper->getChanelId($websiteId);

        $tryAgain = false;
        do {
            $token = $this->retreiveToken();
            $jsonData = array("Token" => $token,
                "SalesChannelId" => $channelId,
                "Entities" => $jsonOutArray
            );

            try {
                $jsonDigest = json_encode($jsonData);
            } catch (Exception $ex) {
                Mage::log(
                    '[EXCEPTION] - Notify Parameters for Freestyle:  ' 
                    . serialize($jsonData), 1, 'freestyle.log'
                );
                if (version_compare(phpversion(), '5.3.0', '>=')) {
                    Mage::log(
                        '[EXCEPTION] - JSON ERROR = ' . json_last_error(), 
                        1, 
                        'freestyle.log'
                    );
                }
                Mage::log(
                    $ex->getMessage() . ' ' . $ex->getFile() . '::' 
                    . $ex->getLine(), 1, 'freestyle.log'
                );
                $tryAgain = false;
                return false;  //send false to mark record(s) as error
            }
            
            try {
                $tryAgain = false;
                $curlObj = Mage::helper("advancedexport/curl");
                if ($curlObj->curlSend($notificationUrl, $jsonDigest)) {
                    //we got something good?
                    Mage::log(
                        '[INFO] - Try To Notify: ' 
                        . $curlObj->curlResult, 
                        1, 
                        'freestyle.log'
                    );
                    $tryAgain = $this->parseResult($curlObj, $token);
                    unset($curlObj);
                    unset($jsonData);
                    unset($jsonDigest);
                } else {
                    //we got something bad
                    $tryAgain = false;
                    return false;
                }//$this->curlSend($notificationUrl, $jsonDigest)
            } catch (Exception $e) {
                $exceptionMessage = 
                    'Can not [SEND] Notification to Freestyle: ' 
                    . $e->getMessage();
                Mage::log(
                    '[EXCEPTION] - ' . $exceptionMessage . ' ' . $ex->getFile() 
                    . '::' . $ex->getLine(), 
                    1, 
                    'freestyle.log'
                );
                $tryAgain = false;
                //$curlObj->sendErrorMessage = $status;
                $this->sendErrorMessage = $exceptionMessage;
                return false;
            }
        } while ($tryAgain);
        unset($curlObj);
        return true;
    }

    public function sendMixQueue($jsonOutArray)
    {
        if (empty($jsonOutArray)) {
            return false;
        }

        //$mainHelper = Mage::Helper('advancedexport');

        /* Authentificate process */
        $notificationUrl = Mage::Helper('advancedexport/queue')
                ->getQueueServiceUrl();
        $notificationUrl = trim($notificationUrl) . "MultiStore";

        $tryAgain = false;
        do {
            $token = $this->retreiveToken();
            $jsonData = array("Token" => $token,
                "Entities" => $jsonOutArray
            );
            
            try {
                $jsonDigest = json_encode($jsonData);
            } catch (Exception $ex) {
                Mage::log(
                    '[EXCEPTION] - Notify Parameters for Freestyle:  ' 
                    . serialize($jsonData), 
                    1, 
                    'freestyle.log'
                );
                if (version_compare(phpversion(), '5.3.0', '>=')) {
                    Mage::log(
                        '[EXCEPTION] - JSON ERROR = ' . json_last_error(), 
                        1, 
                        'freestyle.log'
                    );
                }
                Mage::log(
                    $ex->getMessage() . ' ' . $ex->getFile() 
                    . '::' . $ex->getLine(), 
                    1, 
                    'freestyle.log'
                );
                $tryAgain = false;
                return false;  //send false to mark record(s) as error
            }
            
            try {
                $tryAgain = false;
                $curlObj = Mage::helper("advancedexport/curl");
                if ($curlObj->curlSend($notificationUrl, $jsonDigest)) {
                    //we got something good?
                    Mage::log(
                        '[INFO] - Try To Notify: ' . $curlObj->curlResult, 
                        1, 
                        'freestyle.log'
                    );
                    $tryAgain = $this->parseResult($curlObj, $token);
                    unset($curlObj);
                    unset($jsonData);
                    unset($jsonDigest);
                } else {
                    //we got something bad
                    $tryAgain = false;
                    return false;
                }//$this->curlSend($notificationUrl, $jsonDigest)
            } catch (Exception $e) {
                $exceptionMessage = 
                    'Can not [SEND] Notification to Freestyle: ' 
                    . $e->getMessage();
                Mage::log(
                    '[EXCEPTION] - ' . $exceptionMessage . ' ' . $e->getFile() 
                    . '::' . $e->getLine(), 
                    1, 
                    'freestyle.log'
                );
                $tryAgain = false;
                //$curlObj->sendErrorMessage = $status;
                $this->sendErrorMessage = $exceptionMessage;
                return false;
            }
        } while ($tryAgain);
        unset($curlObj);
        return !empty($this->sendErrorMessage) ? false : true;
    }
    
    protected function parseResult($curlObj, $token)
    {
        $status = json_decode($curlObj->curlResult);  //extract the status
        $isErrorInStatus = strpos($status, 'Error');  //integer if found;
                                                      // FALSE if not found
        if ($isErrorInStatus !== false) {
            Mage::log(
                '[WARNING] - Error in response: [' . $curlObj->curlResult 
                . '], token is - ' . $token, 
                1, 
                'freestyle.log'
            );
            if (strpos($status, 'Invalid token')) {
                Mage::log(
                    '[INFO] - Attempting to [RE-AUTHENTICATE]', 
                    1, 
                    'freestyle.log'
                );
                $token = $this->authentification();
                if ($token != false) {
                    Mage::log(
                        '[INFO] - [RE-AUTHENTICATE] Successful. Token is ' 
                        . $token, 
                        1, 
                        'freestyle.log'
                    );
                    return true;
                } else {
                    Mage::log(
                        '[ERROR] - [RE-AUTHENTICATE] failed.', 
                        1, 
                        'freestyle.log'
                    );
                    return true;
                }//$token != false
            } else {
                //we got some other type of error
                $this->sendErrorMessage = $status;
                Mage::log($status, 1, 'freestyle.log');
                return false;
            }
        }//$isErrorInStatus !== false
        return false;
    }
    
    protected function retreiveToken()
    {
        if ($this->getTokenFromDb()) {
            $token = $this->getTokenFromDb();
        } else {
            $token = $this->authentification();
        }
        return $token;
    }
}
