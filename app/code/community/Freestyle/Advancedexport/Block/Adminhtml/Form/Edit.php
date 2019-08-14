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
class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit 
    extends Mage_Adminhtml_Block_Widget_Form_Container
{
    
    public function __construct()
    {
        parent::__construct();
                  
        $this->_objectId = 'export_id';
        $this->_blockGroup = 'advancedexport';
        $this->_controller = 'adminhtml_form';       
        $this->_removeButton('save');
        $this->_removeButton('reset');
        $this->_removeButton('back');
    }
 
    public function getHeaderText()
    {
        return Mage::helper('advancedexport')->__('Export');
    }
}
