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

class Freestyle_Advancedexport_Helper_Website extends Mage_Core_Helper_Abstract
{
    public function isWebSiteStoreViewSupported($websiteId = 0)
    {
        //check if there is a channel id for this website / store view
        //$helper = Mage::helper('advancedexport');
        //if the website_id passed is 0 (default scope).. 
        //then we may need to iterate
        //otherwise, perform the check
        $helper = Mage::Helper('advancedexport');
        $defaultChannelId = $helper->getChanelId(0);
        $thisChannelId = $helper->getChanelId($websiteId);
        if ($thisChannelId === 'channelId') {
            return false;
        }
        
        if ($defaultChannelId != $thisChannelId || $websiteId === null) {
            return true;
        }
        return false;
    }
    
    public function getWebsites($hideUnsynced = false)
    {
        $syncedWebsites = array();
        $helper = Mage::Helper('advancedexport');
        foreach (Mage::app()->getWebsites() as $website) {
            $currentWebsite['WebsiteId'] = $website->getId();
            $currentWebsite['WebsiteName'] = $website->getName();
            $salesChannelId = $helper->getChanelId($website->getId());
            $salesChannelId = $salesChannelId == 'channelId' ? '' 
                    : $salesChannelId;
            $currentWebsite['SalesChannelId'] = $salesChannelId;
            if ($hideUnsynced && $salesChannelId == '') {
                continue;
            } else {
                array_push($syncedWebsites, $currentWebsite);
            }
        }
        return $syncedWebsites;
    }
    public function getWebsitesUtils($hideUnsynced = false)
    {
        $currentWebsite = array();
        $helper = Mage::Helper('advancedexport');
        foreach (Mage::app()->getWebsites() as $website) {
            //$currentWebsite['WebsiteId'] = $website->getId();
            //$currentWebsite['WebsiteName'] = $website->getName();
            $salesChannelId = $helper->getChanelId($website->getId());
            $salesChannelId = $salesChannelId == 'channelId' ? '' 
                    : $salesChannelId;
            if ($hideUnsynced && $salesChannelId == '') {
                continue;
            } else {
                $currentWebsite[] = $salesChannelId;
            }
        }
        return $currentWebsite;
    }
    
    public function getWebsiteByStoreId($storeId)
    {
        return Mage::getModel('core/store')->load($storeId)->getWebsiteId();
    }
}
