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

class Freestyle_Advancedexport_StatusController 
extends Mage_Core_Controller_Front_Action
{

    /**
     * DE-11782: Health Check Ability
     */
    public function queueAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }

            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            $paramStart = $this->getRequest()->getPost('start', '');
            $paramEnd = $this->getRequest()->getPost('end', '');
            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //api credentials check out!
                $queueModel = Mage::getModel('advancedexport/queue');
                $siteData = json_encode(
                    $queueModel->getEntityQueue($paramStart, $paramEnd)
                );
                header('Content-Type: application/json');
                echo $siteData;
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->queueAction: "
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function getqueuecollectionAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }

            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            $paramStart = $this->getRequest()->getPost('start', '');
            $paramEnd = $this->getRequest()->getPost('end', '');
            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //api credentials check out!
                $queueModel = Mage::getModel('advancedexport/queue');
                $siteData = json_encode(
                    $queueModel->getEntityQueue($paramStart, $paramEnd)
                );
                header('Content-Type: application/json');
                echo $siteData;
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->queueAction: "
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function checkcronAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }
            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //api credentials check out!
                //get the resource model
                $resource = Mage::getSingleton('core/resource');

                //retrieve the read connection
                $readConnection = $resource->getConnection('core_read');

                //retrieve the table name
                $table = $resource->getTableName('cron/schedule');

                //SELECT Statement
                $query = 'SELECT * FROM ' . $table . ' WHERE `job_code` '
                    . 'like \'advancedexport_%\' AND status != \'success\';';

                //execute the query
                $result = $readConnection->fetchAll($query);
                $siteData = json_encode($result);

                header('Content-Type: application/json');
                echo $siteData;
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->checkcronAction: "
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function readlogAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }
            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //api credentials check out!
                header('Content-Type: text/plain');
                echo $helper->readLogFile(true);
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->readlogAction: " 
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function checkconfigAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }
            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //api credentials check out!
                //get the resource model
                $resource = Mage::getSingleton('core/resource');

                //retrieve the read connection
                $readConnection = $resource->getConnection('core_read');

                //retrieve the table name
                $table = $resource->getTableName('core/config_data');

                //SELECT Statement
                $query = 'SELECT * FROM ' . $table 
                    . ' WHERE `path` like \'freestyle_advancedexport_%\';';

                //execute the query
                $result = $readConnection->fetchAll($query);

                $siteData = json_encode($result);

                header('Content-Type: application/json');
                echo $siteData;
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->"
                . "checkconfigAction: " 
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function checkextensionAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }
            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                $modules = Mage::getConfig()->getNode('modules')->children();
                $modulesArray = (array) $modules;

                //$siteData = json_encode($modulesArray);
                //header('Content-Type: application/json');
                //echo $siteData;
                header('Content-type: text/plain');
                while ($myExtension = current($modulesArray)) {
                    if (!strstr(key($modulesArray), "Mage_")) {
                        echo key($modulesArray) . "\n";
                    }
                    next($modulesArray);
                }
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->"
                . "checkextensionAction: " 
                . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    public function magecheckAction()
    {
        try {
            $helper = $this->getHelper();
            $isEnabled = $helper->getIsExtEnabledForApi();
            if (!$isEnabled) {
                // extension is not enabled.. redirect to 404
                $this->render404();
                return;
            }
            $paramApiUser = $this->getRequest()->getPost('apiuser', '');
            $paramApiKey = $this->getRequest()->getPost('apikey', '');

            if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
                //do the work here
                header('Content-type: text/plain');
                echo $this->extension_check(
                    array(
                    'curl',
                    'dom',
                    'gd',
                    'hash',
                    'iconv',
                    'mcrypt',
                    'pcre',
                    'pdo',
                    'pdo_mysql',
                    'simplexml',
                    'soap'
                    )
                );
            } else {
                //authentication failed
                header('Content-type: text/plain');
                echo "Invalid Parameter.";
            }
        } catch (Exception $ex) {
            Mage::log(
                "[EXCEPTION] - An Error Occured on "
                . "Freestyle_Advancedexport_StatusController->"
                . "checkextensionAction: " . $ex->getMessage(), 
                1, 
                "freestyle.log"
            );
            header('Content-type: text/plain');
            echo "An Error Occurred.  Please review the log files if enabled.";
        }
    }

    protected function getHelper()
    {
        return Mage::Helper('advancedexport');
    }

    protected function extension_check($extensions)
    {
        $fail = '';
        $pass = '';

        $returnMsg = '';

        if (version_compare(phpversion(), '5.2.0', '<')) {
            $fail .= '<li>You need<strong> PHP 5.2.0</strong>'
                    . ' (or greater)</li>';
        } else {
            $pass .='<li>You have<strong> PHP 5.2.0</strong>'
                    . ' (or greater)</li>';
        }

        if (!ini_get('safe_mode')) {
            $pass .='<li>Safe Mode is <strong>off</strong></li>';
            preg_match(
                '/[0-9]\.[0-9]+\.[0-9]+/', 
                shell_exec('mysql -V'), 
                $version
            );

            if (version_compare($version[0], '4.1.20', '<')) {
                $fail .= '<li>You need<strong> MySQL 4.1.20</strong>'
                        . ' (or greater)</li>';
            } else {
                $pass .='<li>You have<strong> MySQL 4.1.20</strong>'
                        . ' (or greater)</li>';
            }
        } else {
            $fail .= '<li>Safe Mode is <strong>on</strong></li>';
        }

        if (!ini_get('allow_url_fopen')) {
            $fail .= '<li>You need<strong> allow_url_fopen</strong>'
                    . ' enabled</li>';
        } else {
            $pass .='<li>You have<strong> allow_url_fopen</strong>'
                    . ' enabled</li>';
        }

        if (class_exists('ZipArchive')) {
            $pass .='<li>You have<strong> ZipArchive</strong> enabled</li>';
        } else {
            $fail .= '<li>You need<strong> ZipArchive</strong> enabled</li>';
        }
        foreach ($extensions as $extension) {
            if (!extension_loaded($extension)) {
                $fail .= '<li> You are missing the <strong>' 
                        . $extension . '</strong> extension</li>';
            } else {
                $pass .= '<li>You have the <strong>' 
                        . $extension . '</strong> extension</li>';
            }
        }

        if ($fail) {
            $returnMsg = '<p><strong>Your server does not meet the following'
                    . ' requirements in order to install Magento.</strong>'
                    . '<br>The following requirements failed, please contact '
                    . 'your hosting provider in order to receive assistance '
                    . 'with meeting the system requirements for Magento:'
                    . '<ul>' . $fail . '</ul></p>'
                    . 'The following requirements were successfully met:'
                    . '<ul>' . $pass . '</ul>';
        } else {
            $returnMsg = '<p><strong>Congratulations!</strong> '
                    . 'Your server meets the requirements for Magento.</p>'
                    . '<ul>' . $pass . '</ul>';
        }
        return $returnMsg;
    }
    
    protected function render404()
    {
        Mage::app()->getFrontController()
                ->getResponse()
                ->setHeader('HTTP/1.1', '404 Not Found', true);
        Mage::app()->getFrontController()
                ->getResponse()
                ->setHeader('Status', '404 File not found', true);

        $pageId = Mage::getStoreConfig('web/default/cms_no_route');
        //$url = rtrim(Mage::getUrl($pageId), '/');

        if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
            $this->_forward('defaultNoRoute');
        }
        return;        
    }
}
