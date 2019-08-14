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

class Freestyle_Advancedexport_Model_Passivemode 
extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('advancedexport/passivemode');
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
        $passiveModeDataCollcetor = $this->load($this->getEnabledId());
        $data = $passiveModeDataCollcetor->getData();
        $files = unserialize($data['created_files']);
        $files[] = $filesToAdd;
        $data['created_files'] = serialize($files);
        $passiveModeDataCollcetor->setData($data);
        $passiveModeDataCollcetor->save();

        return true;
    }

    public function sendNotification($modeIds)
    {
        $helper = $this->getHelper();

        $explodedIds = explode(',', $modeIds);
        $zipEntityPackages = array();

        foreach ($explodedIds as $modeIdBad) {
            $modeId = trim($modeIdBad);
            if ($modeId) {
                $modeObj = Mage::getModel('advancedexport/passivemode')
                        ->load($modeId);
                if ($modeObj->getId()) {
                    if ($modeObj->getPassivemodEnabled()) {
                        $message = 'Passive Mode Id: ' . $modeId . 
                            '. in Enabled State, before Notificate, '
                            . 'disable it.';
                        Mage::getSingleton('core/session')->addError($message);
                        continue;
                    }
                    $createdFiles = unserialize($modeObj->getCreatedFiles());

                    /* Collect Files By Entity Type, 
                     * for different .zip packages */

                    foreach ($createdFiles as $file) {
                        $zipEntityPackages[$modeIdBad]
                                        ['files']
                                        [$file['entity']]
                                        [] = $file;
                        $zipEntityPackages[$modeIdBad]['datefinished'] = 
                                $modeObj->getPassivemodEnd();
                    }

                } else {
                    $message = 'Wrong Passive Mode Id: ' 
                        . $modeId . '. Status: Skipped.';
                    Mage::getSingleton('core/session')->addError($message);
                }
            }
        }

        $notificationStorage = array();

        if (count($zipEntityPackages)) {
            $chanelName = $helper->getChanelName();
            $chanelId = $helper->getChanelId();
            $baseDir = Mage::getBaseDir();
            $exportFolder = $helper->getExportfolder();

            foreach ($zipEntityPackages as $modeId => $oneMode) {
                $dateTimeInit = $helper
                    ->getTimeByStamp($oneMode['datefinished']);

                foreach ($oneMode['files'] as $entityToExp => $oneEntityFiles) {
                    $zip = new ZipArchive();
                    $zipFile = '[' . $chanelName . ']_'
                             . '[' . $chanelId . ']_'
                             . '[' . $entityToExp . ']_'
                             . '[' . $dateTimeInit . ']';
                    $zipFileFullPath = $baseDir . DS . 
                            $exportFolder . DS . $zipFile;

                $notificationStorage[$modeId][$entityToExp]['zip']['filename'] 
                        = $zipFile . '.zip';
                $notificationStorage[$modeId][$entityToExp]['zip']['fullPath'] 
                        = $zipFileFullPath;

                    try {
                        $zip->open(
                            $zipFileFullPath . '.zip', 
                            ZIPARCHIVE::CREATE
                        );
                    } catch (Exception $e) {
                        $this->stepErrors[] = 'Can not [CREATE] Zip Archive';
                        Mage::log(
                            'Can not [CREATE] Zip Archive', 
                            1, 
                            'freestyle.log'
                        );
                    }

                    foreach ($oneEntityFiles as $file) {
                        try {
                            $zip->addFile($file['filePath'], $file['fileName']);
                        } catch (Exception $e) {
                            $this->stepErrors[] = 'Can not [ADD] file ' . 
                                $fileName . ' to Zip Archive';
                            Mage::log(
                                'Can not [ADD] file ' . $fileName . 
                                ' to Zip Archive', 
                                1, 
                                'freestyle.log'
                            );
                        }
                    }

                    $zip->close();
                }
            }
        }

        /* Send Notification Tlo NextGen */

        foreach ($notificationStorage as $modeId => $zips) {
            foreach ($zips as $entity => $filesInfo) {
                Mage::log(
                    'Sending Notification For Mode Id: ' . $modeId . 
                    ', Entity Type: ' . $entity, 
                    1, 
                    'freestyle.log'
                );

                $sendResult = Mage::helper('advancedexport/notificationSender')
                        ->sendNotification(
                            $entity, 
                            $entityId = -1, 
                            $filesInfo['zip']['filename'], 
                            'updated'
                        );
                Mage::log(
                    'files = '. $filesInfo['zip']['filename'], 
                    1, 
                    'freestyle.log'
                );

                if ($sendResult) {
                    $modeObj = Mage::getModel('advancedexport/passivemode')
                        ->load($modeId);
                    $modeObj->setIsNotificationSent(1);
                    $modeObj->save();
                    Mage::log(
                        'Notification For Mode Id: ' . $modeId . 
                        ', Entity Type: ' . $entity . ' , sent succesfully.', 
                        1, 
                        'freestyle.log'
                    );
                } else {
                    Mage::log(
                        'Notification For Mode Id: ' . $modeId . 
                        ', Entity Type: ' . $entity . ' , failed.', 
                        1, 
                        'freestyle.log'
                    );
                }
            }
        }
    }

    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }
}
