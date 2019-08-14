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

class Freestyle_Advancedexport_WebsiteController 
extends Mage_Core_Controller_Front_Action
{

    public function syncAction()
    {
        $helper = $this->getHelper();
        $isEnabled = $helper->getIsExtEnabledForApi();
        if (!$isEnabled) {
            // extension is not enabled.. redirect to 404
            Mage::app()->getFrontController()
                    ->getResponse()
                    ->setHeader('HTTP/1.1', '404 Not Found', true);
            Mage::app()->getFrontController()
                    ->getResponse()
                    ->setHeader('Status', '404 File not found', true);

            $pageId = Mage::getStoreConfig('web/default/cms_no_route');
            //$url = rtrim(Mage::getUrl($pageId), '/');

            if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
                $this->_forward('defaultNoRoute');
            }
            return;
        }

        //we will have more than 1 channel id
        //$paramChannelID = (string) $this->getRequest()
        //->getPost('channelid', '');
        $paramApiUser = $this->getRequest()->getPost('apiuser', '');
        $paramApiKey = $this->getRequest()->getPost('apikey', '');

        if ($helper->apiAuthenticate($paramApiUser, $paramApiKey)) {
            //api credentials check out!
            $siteData = json_encode(
                Mage::Helper('advancedexport/website')->getWebsites()
            );
            header('Content-Type: application/json');
            echo $siteData;
        } else {
            //we did not find a channel
            header('Content-type: text/xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" '
                    . 'xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . "\n";
            echo '<error>Invalid Parameter</error>';
            echo "</data>";
        }
    }

    protected function getHelper()
    {
        return Mage::Helper('advancedexport');
    }
}
