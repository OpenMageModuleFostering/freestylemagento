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

class Freestyle_Advancedexport_Helper_Curl{
    public $sendErrorMessage    = array();
    public $exceptionMessage    = array();
    public $isSuccess           = true;
    public $curlResult          = '';    
    
    public $useProxy            = false;
    public $proxyIp             = '127.0.0.1';
    public $proxyPort           = 8080; 
    public $proxyType           = 'HTTP';
    public $loginUser           = '';
    public $loginPass           = '';
    
    public function __construct() 
    {
        //fill in the proxy info
        $helper = Mage::Helper('advancedexport/debug');
        $this->useProxy = true === (bool)$helper->getDebugUseProxy();
        $this->verifyPeer = true === (bool)$helper->getDebugVerifyPeer();
        if($this->useProxy)
        {
            $this->proxyIp   = $helper->getDebugProxyIp();
            $this->proxyPort = $helper->getDebugProxyPort();
            $this->loginUser = $helper->getDebugLoginUser();
            $this->loginPass = $helper->getDebugLoginPass();
        }
    }

    public function resetProperties()
    {
        $this->sendErrorMessage = array();
        $this->exceptionMessage = array();
        $this->isSuccess = true;
        $this->curlResult = '';
    }

    public function getHttpCodes()
    {
        //[Informational 1xx]
        $httpCodes[100] = "Continue";
        $httpCodes[101] = "Switching Protocols";

        //[Successful 2xx]
        $httpCodes[200] = "OK";
        $httpCodes[201] = "Created";
        $httpCodes[202] = "Accepted";
        $httpCodes[203] = "Non-Authoritative Information";
        $httpCodes[204] = "No Content";
        $httpCodes[205] = "Reset Content";
        $httpCodes[206] = "Partial Content";

        //[Redirection 3xx]
        $httpCodes[300] = "Multiple Choices";
        $httpCodes[301] = "Moved Permanently";
        $httpCodes[302] = "Found";
        $httpCodes[303] = "See Other";
        $httpCodes[304] = "Not Modified";
        $httpCodes[305] = "Use Proxy";
        $httpCodes[306] = "(Unused)";
        $httpCodes[307] = "Temporary Redirect";

        //[Client Error 4xx]
        $httpCodes[400] = "Bad Request";
        $httpCodes[401] = "Unauthorized";
        $httpCodes[402] = "Payment Required";
        $httpCodes[403] = "Forbidden";
        $httpCodes[404] = "Not Found";
        $httpCodes[405] = "Method Not Allowed";
        $httpCodes[406] = "Not Acceptable";
        $httpCodes[407] = "Proxy Authentication Required";
        $httpCodes[408] = "Request Timeout";
        $httpCodes[409] = "Conflict";
        $httpCodes[410] = "Gone";
        $httpCodes[411] = "Length Required";
        $httpCodes[412] = "Precondition Failed";
        $httpCodes[413] = "Request Entity Too Large";
        $httpCodes[414] = "Request-URI Too Long";
        $httpCodes[415] = "Unsupported Media Type";
        $httpCodes[416] = "Requested Range Not Satisfiable";
        $httpCodes[417] = "Expectation Failed";

        //[Server Error 5xx]
        $httpCodes[500] = "Internal Server Error";
        $httpCodes[501] = "Not Implemented";
        $httpCodes[502] = "Bad Gateway";
        $httpCodes[503] = "Service Unavailable";
        $httpCodes[504] = "Gateway Timeout";
        $httpCodes[505] = "HTTP Version Not Supported";

        return $httpCodes;
    }

    /**
     * Instantiates curl and POSTS
     *
     * @param string $endPointUrl - url to post
     * @param string $jsonData - data to post
     * @return boolean
     */
    public function curlSend($endPointUrl, $jsonData)
    {
        try {
            $curl = curl_init();
            if ($curl) {
                curl_setopt($curl, CURLOPT_URL,             $endPointUrl);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER,  true);
                curl_setopt($curl, CURLOPT_POST,            true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,  $this->verifyPeer);
                if($this->useProxy)
                {
                    $loginPassWord = trim($this->loginUser) . ':' . trim($this->loginPass);
                    curl_setopt($curl, CURLOPT_PROXYPORT,     $this->proxyPort);
                    curl_setopt($curl, CURLOPT_PROXYTYPE,     $this->proxyType);
                    curl_setopt($curl, CURLOPT_PROXY,         $this->proxyIp);
                    if (!empty($loginPassWord))
                        curl_setopt($curl, CURLOPT_PROXYUSERPWD,  $loginPassWord);                    
                }
                curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
                curl_setopt(
                    $curl, 
                    CURLOPT_HTTPHEADER, 
                    array(
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData)
                    )
                );

                $this->curlResult = curl_exec($curl);
            } else {
                //unable to create curl
                Mage::log(
                    '[WARNING] - Unable to initialize cURL handle', 
                    1, 
                    'freestyle.log'
                );
                return false;
            }
        } catch (Exception $ex) {
            $this->exceptionMessage = $ex->getMessage();
            Mage::log(
                '[EXCEPTION] - An exception occurred during this operation. '
                . 'Exception Message: ' . $this->exceptionMessage . ' ' 
                . $ex->getFile() . '::' . (string) $ex->getLine(), 
                1, 
                'freestyle.log'
            );
            curl_close($curl);
            return false;
        }

        if (empty($this->curlResult)) {
            //some kind of error happened
            $this->sendErrorMessage = curl_error($curl);
            Mage::log(
                "[WARNING] - No response was provided connecting to " . 
                $endPointUrl . " " . $this->sendErrorMessage, 
                1, 
                'freestyle.log'
            );
            curl_close($curl);
            return false;
        } else {
            $info = curl_getinfo($curl);
            curl_close($curl);
            if (empty($info['http_code'])) {
                $this->sendErrorMessage = 
                    "[WARNING] - No HTTP code was returned connecting to " 
                    . $endPointUrl;
                Mage::log($this->sendErrorMessage, 1, 'freestyle.log');
                return false;
            } else {
                $httpCodes = $this->getHttpCodes();
                $okResponses = array(200, 201, 202, 203, 204, 205, 206);
                if (!in_array($info['http_code'], $okResponses)) {
                    $this->sendErrorMessage = "[WARNING] - Server responded " 
                        . $info['http_code'] . " " 
                        . $httpCodes[$info['http_code']] . "connecting to " 
                        . $endPointUrl;
                    Mage::log($this->sendErrorMessage, 1, 'freestyle.log');
                    return false;
                }
            }
        }
        return true;
    }
}
