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

class Freestyle_Advancedexport_Model_Website extends Mage_Core_Model_Abstract
{
    //put your code here
    public function toOptionArray()
    {
        $helper = Mage::Helper('advancedexport/website');
        $_websites = $helper->getWebsites(true);
        
        $optArray = array(array('value'=>'', 'label'=>''));
        
        foreach ($_websites as $website) {
            $optArray[] = array(
                'value'=>$website['WebsiteId'], 
                'label'=>$website['WebsiteName']
            );
        }
        return $optArray;
    }
}
