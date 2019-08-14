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

class Freestyle_Advancedexport_Model_Import_Entity_Customer 
extends Mage_ImportExport_Model_Import_Entity_Customer
{

    protected function _importData()
    {
        /*
        $resource = Mage::getModel('customer/customer');
        
        $table = $resource->getResource()->getEntityTable();
        
        $nextEntityId = Mage::getResourceHelper('importexport')
                ->getNextAutoincrement($table);
        */
        
        //comment out next line
        Mage::Helper('advancedexport')->processPassiveMode('set_to_passive');
        $emails = array();
        while ($bunch = $this->_dataSourceModel->getNextBunch()) {
            foreach ($bunch as $rowData) {
                if ($rowData[self::COL_EMAIL]) {
                    $websiteCheck = isset(
                            $this->_websiteCodeToId[$rowData[self::COL_WEBSITE]]
                        );
                    if ($websiteCheck && isset($rowData[self::COL_EMAIL])) {
                        $data = array(
                            'website' => $this->
                                _websiteCodeToId[$rowData[self::COL_WEBSITE]], 
                            'email' => $rowData[self::COL_EMAIL]
                            );
                        $emails[] = $data;
                    }
                }
            }
        }
        $testCondition = 
            Mage_ImportExport_Model_Import::BEHAVIOR_DELETE 
                == $this->getBehavior();
        if ($testCondition) {
            $action = 'deleted';
            Mage::dispatchEvent(
                'customer_custom_import_finish_before', 
                array(
                    'changedids' => $emails, 
                    'event' => $action
                )
            );
            $this->_deleteCustomers();
        } else {
            $this->_saveCustomers();
            $this->_addressEntity->importData();
            //$action = 'updated';
            $action = 'added';
            Mage::dispatchEvent(
                'customer_custom_import_finish_before', 
                array(
                    'changedids' => $emails, 
                    'event' => $action
                )
            );
        }
        //comment out next line
        $passiveResults = Mage::Helper('advancedexport')
                ->processPassiveMode('disable_passive');
        /* Send Notification */
    Mage::getModel('advancedexport/passivemode')
            ->sendNotification($passiveResults['id']);

        return true;
    }
}
