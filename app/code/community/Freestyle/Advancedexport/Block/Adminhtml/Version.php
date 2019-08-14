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

class Freestyle_Advancedexport_Block_Adminhtml_Version 
    extends Mage_Adminhtml_Block_System_Config_Form_Field
{

    protected function _getElementHtml(
        Varien_Data_Form_Element_Abstract $element
    )
    {
        return (string) Mage::helper('advancedexport')->getExtensionVersion();
    }
}