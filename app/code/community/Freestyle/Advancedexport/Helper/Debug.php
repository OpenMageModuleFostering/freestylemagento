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
class Freestyle_Advancedexport_Helper_Debug extends Mage_Core_Helper_Abstract
{

    public function getDebugUseProxy()
    {
        $classDesc = 'freestyle_advancedexport/debug/curl_use_proxy';
        return Mage::getStoreConfig($classDesc);
    }

    public function getDebugLoginUser()
    {
        $classDesc = 'freestyle_advancedexport/debug/curl_proxy_login';
        return Mage::getStoreConfig($classDesc);
    }

    public function getDebugLoginPass()
    {
        $classDesc = 'freestyle_advancedexport/debug/curl_proxy_pass';
        $value = Mage::getStoreConfig($classDesc);
        $decrypt = Mage::helper('core')->decrypt($value);
        return $decrypt;
    }

    public function getDebugProxyIp()
    {
        $classDesc ='freestyle_advancedexport/debug/curl_proxy_ip';
        return Mage::getStoreConfig($classDesc);
    }

    public function getDebugProxyPort()
    {
        $classDesc = 'freestyle_advancedexport/debug/curl_proxy_ip';
        return Mage::getStoreConfig($classDesc);
    }
    
    public function getDebugVerifypeer()
    {
        $classDesc = 'freestyle_advancedexport/debug/curl_verify_peer';
        return Mage::getStoreConfig($classDesc);
    }    
}
