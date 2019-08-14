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

class Freestyle_Advancedexport_IndexController 
extends Mage_Core_Controller_Front_Action
{
    public function getHelper()
    {
        return Mage::Helper('advancedexport');
    }
    
    public function getallshipmethAction()
    {
        $isEnabled = $this->getHelper()->getIsExtEnabledForApi();
        if (!$isEnabled) {
            // extension is not enabled.. redirect to 404
            Mage::app()->getFrontController()
                    ->getResponse()
                    ->setHeader('HTTP/1.1', '404 Not Found', true);
            Mage::app()->getFrontController()
                    ->getResponse()
                    ->setHeader('Status', '404 File not found', true);

            $pageId = Mage::getStoreConfig('web/default/cms_no_route');
            //$pageId = Mage::getStoreConfig('cms/index/noRoute');
            //$url = rtrim(Mage::getUrl($pageId), '/');
            //$url = Mage::helper('core/url')->getCurrentUrl();
            //Mage::app()->getFrontController()
            //->getResponse()
            //->setRedirect($url,404);
            if (!Mage::helper('cms/page')->renderPage($this, $pageId)) {
                $this->_forward('defaultNoRoute');
            }
            return;
        }

        //get ChannelId
        //$ChannelID = $this->getHelper()->getChanelId();
        $salesChannelIds = Mage::Helper('advancedexport/website')
                ->getWebsitesUtils(true);
        $paramChannelID = (string) $this->getRequest()
                ->getPost('channelid', '');

        if (!in_array($paramChannelID, $salesChannelIds)) {
            //if (1 == 2) {
            header('Content-type: text/xml');
            echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" .
            '<data xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
                    . ' xmlns:xsd="http://www.w3.org/2001/XMLSchema">' . "\n";
            echo '<error>Invalid Parameter</error>';
            echo "</data>";
        } else {
            try {
                $methods = Mage::getSingleton('shipping/config')
                        ->getAllCarriers();
            } catch (Exception $e) {
                Mage::log("Unable to call getAllCarriers", 1, "freestyle.log");
                $methods = false;
            }

            $xmlstr = "<?xml version='1.0' encoding='UTF-8'?>"
                . "<data xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'"
                    . " xmlns:xsd='http://www.w3.org/2001/XMLSchema'></data>";
            //$shipxml = new SimpleXMLElement($xmlstr);
            $shipxml = 
    new Freestyle_Advancedexport_Model_Exportmodels_SimpleXMLExtended($xmlstr);

            if ($methods) {
                foreach ($methods as $ccode => $carrier) {
                    //print "$_ccode => $_carrier\n";
                    try {
                        if ($_methods = $carrier->getAllowedMethods()) {
                            !$titleCheck = 
                                Mage::getStoreConfig("carriers/$ccode/title");
                            if ($titleCheck) {
                                $_title = $ccode;
                            }
                            foreach ($_methods as $jmcode => $jmethod) {
                                $ucode = $ccode . '_' . $jmcode;
                                $shippingmethod = 
                                    $shipxml->addChild('ShippingMethod');
                                $shippingmethod
                                        ->addChild('magentoID')
                                        ->addCData($ucode);
                                $shippingmethod
                                        ->addChild('title')
                                        ->addCData($jmethod);
                                $shippingmethod
                                        ->addChild('carrier')
                                        ->addCData($_title);
                                $shippingmethod->addChild('active', 0);
                                $shippingmethod->addChild('website_id', 1);
                                //->addChild($keyToAdd)->addCData($value)
                            }//foreach ($_methods as $_mcode => $_method)
                        }//if ($_methods = $_carrier->getAllowedMethods())
                    } catch (Exception $e) {
                        Mage::log(
                            "Unable to iterate through shipping methods.  " 
                            . $e->getMessage(), 
                            1, 
                            "freestyle.log"
                        );
                    }//try
                }//foreach ($methods as $_ccode => $_carrier)
            }

            //get the active shipping methods
            //find xml and update node;
            try {
                $methods = Mage::getSingleton('shipping/config')
                        ->getActiveCarriers();
            } catch (Exception $e) {
                Mage::log("Unable to call getAllCarriers", 1, "freestyle.log");
                $methods = false;
            }

            if ($methods) {
                foreach ($methods as $ccode => $carrier) {
                    //print "$_ccode => $_carrier\n";
                    try {
                        if ($_methods = $carrier->getAllowedMethods()) {
                            $checkTitle = !$_title 
                            = Mage::getStoreConfig("carriers/$ccode/title");
                            if ($checkTitle) {
                                $_title = $ccode;
                            }
                            foreach ($_methods as $jmcode => $jmethod) {
                                $ucode = $ccode . '_' . $jmcode;
                                $pathVal = "/data/ShippingMethod"
                                        . "[magentoID = \"$ucode\"]";
                                $result = $shipxml
                                    ->xpath($pathVal);
                                //print_r($result);
                                $result[0]->active = 1;
                                //echo $result."\n";
                            }
                        }
                    } catch (Exception $e) {
                        Mage::log(
                            "Unable to iterate Active through shipping methods."
                            . "  " . $e->getMessage(), 
                            1, 
                            "freestyle.log"
                        );
                    }//try
                }//foreach ($methods as $_ccode => $_carrier)
            }//if($methods)
            //$methods = Mage::getSingleton('shipping/config')
            //->getActiveCarriers();
            header('Content-type: text/xml');
            echo $shipxml->asXML();
            //Mage::app()->getResponse()->setBody($result);
        }
    }
}
