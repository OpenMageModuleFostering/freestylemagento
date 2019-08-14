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

class Freestyle_Advancedexport_Model_Cronjob
{

    public function send()
    {
        //Mage::dispatchEvent('advancedexport_notify');
        //$batchSize = 100;
        $advHelper = Mage::Helper('advancedexport/queue');
        if ($advHelper->getSendAsync() != '1') {
            return true;
        }

        $batchSize = $advHelper->getQueueBatchSize();

        $_websites = Mage::Helper('advancedexport/website')->getWebsites(true);
        foreach ($_websites as $website) {
            $batchSizeSent = 0;
            do {
                $batchSizeSent = Mage::getModel('advancedexport/queue')
                    ->sendWebsiteCollection($batchSize, $website['WebsiteId']);
            } while ($batchSizeSent == $batchSize);
        }
        //store the date so we can compare
        $this->putCronStatusToDb(
            Mage::getModel('core/date')->timestamp(time())
        );
    }

    public function purge()
    {
        Mage::getModel('advancedexport/queue')->purgeQueue();
        //store the date so we can compare
        $this->putCronStatusToDb(
            Mage::getModel('core/date')->timestamp(time())
        );
    }

    public function putCronStatusToDb($status)
    {
        $data = array();
        $tokenModel = Mage::getModel('advancedexport/configuration')
            ->load('cronstatus', 'config_code');
        if ($tokenModel->getId()) {
            $tokenModel->setConfigValue($status);
            $tokenModel->save();
        } else {
            $newConfData = Mage::getModel('advancedexport/configuration');
            $data['config_code'] = 'cronstatus';
            $data['config_value'] = $status;
            $newConfData->setData($data);
            $newConfData->save();
        }
    }

    public function getCronStatusFromDb()
    {
        $tokenModel = Mage::getModel('advancedexport/configuration')
            ->load('cronstatus', 'config_code');
        if ($tokenModel->getId()) {
            return $tokenModel->getConfigValue();
        }

        return false;
    }
}
