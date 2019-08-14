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

class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_UpdatetimeRender
extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    //put your code here
    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $format = Mage::app()->getLocale()->getDateTimeFormat(
            Mage_Core_Model_Locale::FORMAT_TYPE_MEDIUM
        );
        //$arr = unserialize($value);
        //Mage::log($value, 1, "updatetime.log");
        if ($value == "0000-00-00 00:00:00") {
            $str = ' ';
        } else {
            $str = Mage::app()->getLocale()
                    ->date(
                        $value,
                        Varien_Date::DATETIME_INTERNAL_FORMAT
                    )
                    ->toString($format);
        }
        return $str;
    }
}
