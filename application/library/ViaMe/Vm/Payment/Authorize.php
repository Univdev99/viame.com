<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

require_once dirname(__FILE__) . '/../../../Other/Array2XML.php';
require_once dirname(__FILE__) . '/../../../Other/XML2Array.php';

class ViaMe_Vm_Payment_Authorize
{
    const DEFAULT_SOFT_DESCRIPTOR   = 'Levelogic';
    
    const LIVE_API_SERVER           = 'https://api.authorize.net/xml/v1/request.api';
    const LIVE_CRED_LOGIN_ID        = '9u5L9aCvy6';
    const LIVE_CRED_TRANS_KEY       = '8DVzpp826697cZ8s';
    const LIVE_MD5HASH_KEY          = 'gRX4uVwd6sdxkuNt';
    const LIVE_OTHER_MD5_LOGIN      = 'ArthurKang71';
    
    const TEST_API_SERVER           = 'https://apitest.authorize.net/xml/v1/request.api';
    const TEST_CRED_LOGIN_ID        = '5gL8rD5L';
    const TEST_CRED_TRANS_KEY       = '236k2eVD5tm5yQM6';
    const TEST_MD5HASH_KEY          = 'OtUOZmToefk39Dal';
    const TEST_OTHER_MD5_LOGIN      = 'ArthurKang71';
    
    // Community Credentials For LIVE ONLY
    protected static $_community_credentials = array(
        'ninjaplays' => array(
            'Softdescriptor' => 'Ninja Plays',
            'User' => '7D9cxC72cG92',
            'Password' => '735NU5EvD8c8Er8k',
            'Md5key' => '6mVHXbWaDC5j',
            'Othermd5login' => 'ArthurKangNP'
        )
    );
    protected static $_soft_descriptor  = null;
    protected static $_api_server       = null;
    protected static $_cred_login_id    = null;
    protected static $_cred_trans_key   = null;
    protected static $_md5_hash_key     = null;
    protected static $_other_md5_login  = null;
    
    protected static $_mode             = null;
    protected static $_instance         = null;
    
    public function __construct($mode = null, $community = null, $options = null) {
        if ($mode === 'LIVE') {
            self::$_mode = 'LIVE';
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_api_server      = self::LIVE_API_SERVER;
            self::$_cred_login_id   = self::LIVE_CRED_LOGIN_ID;
            self::$_cred_trans_key  = self::LIVE_CRED_TRANS_KEY;
            self::$_md5_hash_key    = self::LIVE_MD5HASH_KEY;
            self::$_other_md5_login = self::LIVE_OTHER_MD5_LOGIN;
        }
        else {
            self::$_mode = 'TEST';
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_api_server      = self::TEST_API_SERVER;
            self::$_cred_login_id   = self::TEST_CRED_LOGIN_ID;
            self::$_cred_trans_key  = self::TEST_CRED_TRANS_KEY;
            self::$_md5_hash_key    = self::TEST_MD5HASH_KEY;
            self::$_other_md5_login = self::TEST_OTHER_MD5_LOGIN;
        }
        
        // Change credentials for LIVE mode - Do first so we can override with any passed in options
        if ($mode === 'LIVE' && $community && isset(self::$_community_credentials[$community])) {
            $this->setOptions(self::$_community_credentials[$community]);
        }
        
        if (is_array($options)) {
            $this->setOptions($options);
        }
        
        self::$_instance = $this;
    }
    
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self(self::$_mode);
        }

        return self::$_instance;
    }
	
    public static function curlPost($url, $data) {
        if ($url && $data) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            #curl_setopt($ch, CURLOPT_VERBOSE, true);
            #curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            #curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            #curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            #curl_setopt($ch, CURLOPT_USERPWD, "username:password");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: text/xml'));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data); 
            
            $raw = curl_exec($ch);
            
            $response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
            
            $errno = $errstr = null;
            
            $status = true;
            
            if ($raw === false || $raw == '0') {
                $errno = curl_errno($ch);
                $errstr = curl_error($ch);
                $status = false;
            }
            
            curl_close($ch);
            unset($ch);
            
            return array('status' => $status, 'raw' => $raw, 'response_status' => $response_status, 'errno' => $errno, 'errstr' => $errstr);
        }
        else {
            return array('status' => false, 'raw' => null, 'response_status' => null, 'errno' => null, 'errstr' => 'Insufficient data to transact');
        }
    }
    
     public static function transact($api = null, array $trxn = array()) {
        if ($api && is_array($trxn) && count($trxn)) {
            //Array2XML::init('1.0', 'UTF-8', false);
            $xml = Array2XML::createXML($api, array_merge(
                array(
                    '@attributes' => array(
                        'xmlns' => 'AnetApi/xml/v1/schema/AnetApiSchema.xsd'
                    ),
                    'merchantAuthentication' => array(
                        'name' => self::$_cred_login_id,
                        'transactionKey' => self::$_cred_trans_key
                    ),
                ),
                $trxn
            ));
            
            #Zend_Debug::Dump($xml->saveXML());
            
            $result = self::curlPost(self::$_api_server, $xml->saveXML());
            $tempRes = XML2Array::createArray($result['raw']);
            $result['res'] = $tempRes[key($tempRes)];
            
            // Test MD5 Hash - Is there a need to validate?
            
            // DO NOT set the default status to false.  Check the responseCode back in the app
            #$result['status'] = false;
            
            if (isset($result['res']['messages']['resultCode']) && $result['res']['messages']['resultCode'] == 'Error') {
                // Error
                $result['status'] = false;
                $result['errno'] = $result['res']['messages']['message']['code'];
                $result['errstr'] = $result['res']['messages']['message']['text'];
            }
            elseif (
                (isset($result['errno']) && $result['errno']) ||
                (isset($result['res']['transactionResponse']['errors']['error']['errorCode']) && $result['res']['transactionResponse']['errors']['error']['errorCode'])
            )
            {
                $result['status'] = false;
            }
            elseif (
                (isset($result['res']['messages']['resultCode']) && $result['res']['messages']['resultCode'] == 'Ok') ||
                (isset($result['res']['transactionResponse']['responseCode']) && $result['res']['transactionResponse']['responseCode'] == '1')
            ) {
                // Successful - responseCode of 1 should catch all succusses
                $result['status'] = true;
            }
            else {
                // Default rest to false??
                $result['status'] = false;
            }
            
            // Little bit more detailed error messages
            if (isset($result['res']['transactionResponse']['errors']['error'])) {
                //$result['status'] = false;
                $result['errno'] = $result['res']['transactionResponse']['errors']['error']['errorCode'];
                $result['errstr'] = $result['res']['transactionResponse']['errors']['error']['errorText'];
            }
            
            #Zend_Debug::Dump($result);
            
            return $result;
        }
        else {
            return array('status' => false, 'raw' => null, 'response_status' => null, 'errno' => null, 'errstr' => 'Incorrect parameters sent to transact');
        }
    }
    
    public static function webhook($postData = null) {
        $postArray = array();
        
        if (is_string($postData)) {
            parse_str($postData, $postArray);
        }
        elseif (is_array($postData)) {
            $postArray = $postData;
        }
        elseif (isset($_POST) && count($_POST)) {
            $postArray = $_POST;
        }
        else {
            // use raw POST data
            $raw_post_array = explode('&', file_get_contents('php://input'));
            foreach ($raw_post_array as $keyval) {
                $keyval = explode('=', $keyval);
                if (count($keyval) == 2) {
                    if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) { 
                        $keyval[1] = stripslashes($value); 
                    }
                    $postArray[$keyval[0]] = urldecode($keyval[1]);
                }
            }
        }
        
        if (count($postArray)) {
            $results = array('status' => false);
            
            if (self::getInstance()->getMd5key() && isset($postArray['x_trans_id']) && isset($postArray['x_amount']) && isset($postArray['x_MD5_Hash'])) {
                // ARB
                if (isset($postArray['x_subscription_id']) && $postArray['x_subscription_id'] && isset($postArray['x_subscription_paynum']) && $postArray['x_subscription_paynum']) {
                    if (strtoupper(md5(self::getInstance()->getMd5key() . $postArray['x_trans_id'] . $postArray['x_amount'])) == $postArray['x_MD5_Hash']) {
                        $results['status'] = true;
                    }
                }
                else {
                    $try_logins = array();
                    if (self::getInstance()->getUser()) { array_push($try_logins, self::getInstance()->getUser()); }
                    if (self::getInstance()->getOthermd5login()) { $try_logins = array_merge($try_logins, explode('|', self::getInstance()->getOthermd5login())); }
                    
                    foreach ($try_logins as $try) {
                        if (strtoupper(md5(self::getInstance()->getMd5key() . $try . $postArray['x_trans_id'] . $postArray['x_amount'])) == $postArray['x_MD5_Hash']) {
                            $results['status'] = true;
                            break;
                        }
                    }
                }
            }
            
            return $results;
        }
        else {
            return array('status' => false, 'errno' => 0, 'errstr' => 'No data passed for transaction.');
        }
    }
    
    public function setOptions(array $options = array()) {
        $reflector = new ReflectionObject(self::getInstance());
        foreach ($options as $key => $val) {
            $methodName = 'set' . ucfirst($key);
            if ($reflector->hasMethod($methodName)) {
                $this->{$methodName}($val);
            }
        }
        
        return $this;
    }
    
    public function setSoftdescriptor($softdescriptor = null) {
        if ($softdescriptor) { self::$_soft_descriptor = $softdescriptor; }
        
        return $this;
    }
    
    public function getSoftdescriptor() {
        return self::$_soft_descriptor;
    }
    
    public function setApiserver($server = null) {
        if ($server) { self::$_api_server = $server; }
        
        return $this;
    }
    
    public function getApiserver() {
        return self::$_api_server;
    }
    
    public function setUser($user = null) {
        if ($user) { self::$_cred_login_id = $user; }
        
        return $this;
    }
    
    public function getUser() {
        return self::$_cred_login_id;
    }
    
    public function setPassword($password = null) {
        if ($password) { self::$_cred_trans_key = $password; }
        
        return $this;
    }
    
    public function getPassword() {
        return self::$_cred_trans_key;
    }
    
    public function setMd5key($key = null) {
        if ($key) { self::$_md5_hash_key = $key; }
        
        return $this;
    }
    
    public function getMd5key() {
        return self::$_md5_hash_key;
    }
    
    public function setOthermd5login($key = null) {
        if ($key) { self::$_other_md5_login = $key; }
        
        return $this;
    }
    
    public function getOthermd5login() {
        return self::$_other_md5_login;
    }
}