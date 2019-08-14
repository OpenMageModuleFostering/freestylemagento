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

class Freestyle_Advancedexport_Model_Resend extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancedexport/resend');
    }

    public function getIsPassiveEnabled()
    {
        $passiveEnabled = $this->getCollection()
                ->addFieldToFilter('passivemod_enabled', array('eq' => '1'));
        if ($passiveEnabled->count()) {
            return true;
        }
        return false;
    }

    public function getEnabledId()
    {
        $passiveEnabled = $this->getCollection()
                ->addFieldToFilter('passivemod_enabled', array('eq' => '1'));
        if ($passiveEnabled->count()) {
            return $passiveEnabled->getLastItem()->getId();
        }
        return false;
    }

    public function addFileDataToCollector($filesToAdd)
    {
        if (isset($filesToAdd['zipFilePath'])) {
            unset($filesToAdd['zipFilePath']);
        }
        //echo '<pre>'; print_r($filesToAdd);
        $passvModeDataCollectr = $this->load($this->getEnabledId());
        $data = $passvModeDataCollectr->getData();
        $files = unserialize($data['created_files']);
        $files[] = $filesToAdd;
        $data['created_files'] = serialize($files);
        $passvModeDataCollectr->setData($data);
        $passvModeDataCollectr->save();

        return true;
    }

    /* DE-10150 - resend orders to freestyle using GUI in admin panel */

    public function sendNotification($modeIds)
    {
        if (!$this->getIsExtEnabled() || empty($modeIds)) {
            return false;
        }

        Mage::log(
            "[INFO] - Resend File generation "
            . "[Order->IncrementId=$modeIds]", 
            1, 
            'freestyle.log'
        );
        $entityId = trim($modeIds);
        $sendResult = false;
        $sendResultCustomer = false;
        $helper = $this->getHelper();

        //load the order
        $order = Mage::getModel('sales/order');
        $order->loadByIncrementId($entityId);

        //export the customer...
        $customerIdtoExport = $order->customer_id;
        if (!empty($customerIdtoExport) && $customerIdtoExport != "0") {
            $entityToExport = 'customer';
            $action = 'updated';

            $zipFile = $helper->generateAndSaveExportFile(
                $entityToExport, 
                $customerIdtoExport, 
                $action
            );
            if ($zipFile) {
                $noteSender = Mage::helper('advancedexport/notificationSender');
                $sendResultCustomer = $noteSender->sendNotification(
                    $entityToExport, 
                    $customerIdtoExport, 
                    $zipFile, 
                    $action
                );
            }

            if ($sendResultCustomer) {
                $resultMsg = "Success! Customer $customerIdtoExport "
                        . "sent to Freestyle";
                Mage::getSingleton('adminhtml/session')->addSuccess($resultMsg);
                Mage::log(
                    "[INFO] - Customer [RESEND] "
                    . "[Order->IncrementId=$modeIds], "
                    . "[Customer->Id=$customerIdtoExport] success", 
                    1, 
                    'freestyle.log'
                );
            } else {
                $resultMsg = "Unable to send Customer $customerIdtoExport "
                        . "to Freestyle.  Please ensure you have "
                        . "entered a valid Order Number";
                Mage::getSingleton('adminhtml/session')->addError($resultMsg);
                Mage::log(
                    "[ALERT] - Resend File generation "
                    . "[Order->IncrementId=$modeIds], "
                    . "[Customer->Id=$customerIdtoExport] FAILED", 
                    1, 
                    'freestyle.log'
                );
            }
        } else {
            // guest order.. nothing to export
            $sendResultCustomer = true;
        }

        if ($sendResultCustomer) {
            //export the order
            $entityToExport = 'order';
            $action = 'updated';

            $zipFile = $helper->generateAndSaveExportFile(
                $entityToExport, 
                $entityId, 
                $action
            );
            if ($zipFile) {
                $sendResult = Mage::helper('advancedexport/notificationSender')
                        ->sendNotification(
                            $entityToExport, 
                            $entityId, 
                            $zipFile, 
                            $action
                        );
            }

            if ($sendResult) {
                $resultMsg = "Success! Order $modeIds sent to Freestyle";
                Mage::getSingleton('adminhtml/session')->addSuccess($resultMsg);
                Mage::log(
                    "[INFO] - Order [RESEND] [Order->IncrementId=$modeIds], "
                    . "[Customer->Id=$customerIdtoExport] success", 
                    1, 
                    'freestyle.log'
                );
            } else {
                $resultMsg = "Unable to send Order $modeIds to Freestyle.  "
                        . "Please ensure you have entered a valid Order Number";
                Mage::getSingleton('adminhtml/session')->addError($resultMsg);
                Mage::log(
                    "[ALERT] - [RESEND] [Order->IncrementId=$modeIds], "
                    . "[Customer->Id=$customerIdtoExport] FAILED", 
                    1, 
                    'freestyle.log'
                );
            }
        }

        Mage::app()->getResponse()->setBody($resultMsg);
        Mage::log(
            "[INFO] - End Resend File generation [Order->IncrementId=$modeIds]",
            1, 
            'freestyle.log'
        );
    }

    public function getIsExtEnabled()
    {
        return Mage::Helper('advancedexport')->getIsExtEnabledForApi();
    }

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }
}
