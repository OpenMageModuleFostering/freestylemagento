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
class Freestyle_Advancedexport_Block_Adminhtml_Form_Edit_Tab_FilesPassiveRender 
    extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{

    public function render(Varien_Object $row)
    {
        $value = $row->getData($this->getColumn()->getIndex());
        $arr = unserialize($value);
        $str = '';
        $counter = 1;
        $baseUrl = Mage::getStoreConfig('web/secure/base_url');
        $helper = Mage::helper('advancedexport');
        if ($arr) {
            foreach ($arr as $key => $one) {
                unset($key);
                if (isset($one['fileName'])) {
                    $fileName = trim($one['fileName']);
                    if ($helper->getIsFileExist($fileName)) {
                        $str.='<span>' . $counter++ . '. <a href="' . $baseUrl 
                              . $helper->getExportfolder() . DS . $fileName . 
                              '" target="_blank">' . $fileName . 
                              '</a></span><br>';
                    } else {
                        $str.='<span>' . $counter++ . '. ' . $fileName . 
                              ' - <span style="font-weight:bold; color:red;">'
                                . 'deleted</span>' . '</span><br>';
                    }
                    if ($counter > 15) {
                        $str.='<span>...</span>';
                        break;
                    }
                }
            }
        } else {
            return 'No files were created during this period';
        }
        unset($baseUrl);
        unset($fileName);
        return $str;
    }
}
