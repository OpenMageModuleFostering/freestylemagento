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

// app/code/community/Freestyle/Advancedexport/Model/Queuebatchsize.php
class Freestyle_Advancedexport_Model_Queuebatchsize 
extends Mage_Core_Model_Config_Data
{

    const VALIDATION_MESSAGE = 'Queue Batch Size must be between 1 and 500';

    public function save()
    {
        $batchSize = $this->getValue();
        if ($batchSize < 1) {
            Mage::throwException(self::VALIDATION_MESSAGE);
            //Mage::getSingleton('core/session')
            //->addError(self::VALIDATION_MESSAGE);
        }

        if ($batchSize > 500) {
            Mage::throwException(self::VALIDATION_MESSAGE);
            //Mage::getSingleton('core/session')
            //->addError(self::VALIDATION_MESSAGE);
        }

        return parent::save();
    }
}
