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

class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_ErrorsRender 
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $arr = unserialize($value);
        $str = '';
        if ($arr) {
            foreach ($arr as $key => $one) {
                unset($key);
                $str.='<span>' . $one . '</span><br>';
            }
        }
        return $str;
    }
}
