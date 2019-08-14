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

class Freestyle_Advancedexport_Model_Import_Entity_Product 
extends Mage_ImportExport_Model_Import_Entity_Product
{

    protected function _importData()
    {
        $ids = array();
        $skus = array();

        //re-enabled 11-21-2013
        Mage::Helper('advancedexport')->processPassiveMode('set_to_passive');
        $behaviorTest = Mage_ImportExport_Model_Import::BEHAVIOR_DELETE 
                == $this->getBehavior();
        if ($behaviorTest) {
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $rowValidation = $this->validateRow($rowData, $rowNum);
                    $selfValidation = self::SCOPE_DEFAULT == 
                            $this->getRowScope($rowData);
                    if ($rowValidation && $selfValidation) {
                        //echo '<pre>'; print_r($rowData);
                        $ids [] = $this
                                ->_oldSku[$rowData[self::COL_SKU]]['entity_id'];
                    }
                    unset($rowValidation);
                    unset($selfValidation);
                }
            }


            $action = 'deleted';
            Mage::dispatchEvent(
                'catalog_custom_product_import_finish_before', 
                array('changedids' => $ids, 'eventtype' => $action)
            );

            $this->_deleteProducts();
        } else {
            while ($bunch = $this->_dataSourceModel->getNextBunch()) {
                foreach ($bunch as $rowNum => $rowData) {
                    $rowValidation = $this->validateRow($rowData, $rowNum);
                    $selfValidation = self::SCOPE_DEFAULT == 
                            $this->getRowScope($rowData);
                    if ($rowValidation && $selfValidation) {
                        $skus [] = $rowData[self::COL_SKU];
                    }
                    unset($rowValidation);
                    unset($selfValidation);                    
                }
            }


            $this->_saveProducts();
            $this->_saveStockItem();
            $this->_saveLinks();
            $this->_saveCustomOptions();

            foreach ($this->_productTypeModels as $productTypeModel) {
                $productTypeModel->saveData();
            }

            $action = 'updated';
            Mage::dispatchEvent(
                'catalog_custom_product_import_finish_before', 
                array('changedids' => $skus, 'eventtype' => $action)
            );
        }

        Mage::dispatchEvent(
            'catalog_product_import_finish_before', array('adapter' => $this)
        );
        //re-enabled 11-21-2013
        $passiveResults = Mage::Helper('advancedexport')
                ->processPassiveMode('disable_passive');
        /* Send Notification */
    Mage::getModel('advancedexport/passivemode')
            ->sendNotification($passiveResults['id']);
        return true;
    }
}
