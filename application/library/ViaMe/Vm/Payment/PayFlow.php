<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Vm_Payment_PayFlow
{
    const VERBOSITY                 = 'LOW';
    const DEFAULT_SOFT_DESCRIPTOR   = 'Levelogic';
    
    const LIVE_API_SERVER           = 'https://pilot-payflowpro.paypal.com';
    const LIVE_CRED_USER            = 'ScnApiLimited';
    const LIVE_CRED_VENDOR          = 'arthurkang';
    const LIVE_CRED_PARTNER         = 'PayPal';
    const LIVE_CRED_PASSWORD        = 'fHjqYceJjZe6BuyuRkoH';
    
    
    const TEST_API_SERVER           = 'https://pilot-payflowpro.paypal.com';
    const TEST_CRED_USER            = 'ScnApiLimited';
    const TEST_CRED_VENDOR          = 'arthurkang';
    const TEST_CRED_PARTNER         = 'PayPal';
    const TEST_CRED_PASSWORD        = 'fHjqYceJjZe6BuyuRkoH';
    
    protected static $_soft_descriptor  = null;
    protected static $_api_server       = null;
    protected static $_cred_user        = null;
    protected static $_cred_vendor      = null;
    protected static $_cred_partner     = null;
    protected static $_cred_password    = null;
    
    protected static $_instance = null;
    
    public function __construct($mode = null) {
        if ($mode === 'LIVE') {
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_api_server      = self::LIVE_API_SERVER;
            self::$_cred_user       = self::LIVE_CRED_USER;
            self::$_cred_vendor     = self::LIVE_CRED_VENDOR;
            self::$_cred_partner    = self::LIVE_CRED_PARTNER;
            self::$_cred_password   = self::LIVE_CRED_PASSWORD;
        }
        else {
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_api_server      = self::TEST_API_SERVER;
            self::$_cred_user       = self::TEST_CRED_USER;
            self::$_cred_vendor     = self::TEST_CRED_VENDOR;
            self::$_cred_partner    = self::TEST_CRED_PARTNER;
            self::$_cred_password   = self::TEST_CRED_PASSWORD;
        }
    }
    
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }
    
    public static function curlPost($url, $data) {
        if ($url && $data) {
            $ch = curl_init($url);
            #curl_setopt($ch, CURLOPT_URL, self::$_api_server);
            #curl_setopt($ch, CURLOPT_VERBOSE, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            #curl_setopt($ch, CURLOPT_SSLVERSION, 3);
            #curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            #curl_setopt($ch, CURLOPT_USERPWD, "username:password");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
            curl_setopt($ch, CURLOPT_POST, true);
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
            
            return array('status' => $status, 'raw' => $raw, 'response_status' => $response_status, 'errno' => $errno, 'errstr' => $errstr);
        }
        else {
            return array('status' => false, 'raw' => null, 'response_status' => null, 'errno' => null, 'errstr' => null);
        }
    }
    
    public static function transact($trxn = null) {
        if (is_array($trxn) && count($trxn)) {
            $res = $data = null;
            
            $trxn = array_merge(
                array(
                    'USER'              => self::$_cred_user,
                    'VENDOR'            => self::$_cred_vendor,
                    'PARTNER'           => self::$_cred_partner,
                    'PWD'               => self::$_cred_password,
                    
                    'VERBOSITY'         => self::VERBOSITY,
                    'SOFTDESCRIPTOR'    => self::$_soft_descriptor,
                    'MERCHDESCR'        => self::$_soft_descriptor,
                    'MERCHSVC'          => $_SERVER['SERVER_NAME'],
                    'CUSTIP'            => $_SERVER['REMOTE_ADDR']
                ),
                $trxn
            );
            
            // FixUp the data string
            foreach ($trxn as $key => $val) {
                if ($data) { $data .= '&'; }
                
                $data .= "$key";
                if (preg_match('/[\&\=]/', $val)) {
                    $data .= '[' . strlen($val) . ']';
                }
                $data .= '=';
                
                $data .= $val;
            }
            
            $results = self::curlPost(self::$_api_server, $data);
            
            if ($results['status'] && $results['raw']) {
                parse_str($results['raw'], $res);
                
                $results['status'] = ($res['RESULT'] == '0' ? true : false);
                $results['res'] = $res;
                
                if (!$results['status']) {
                    $results['errno'] = $res['RESULT'];
                    $results['errstr'] = $res['RESPMSG'];
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
    
    public function setApiserver($server = null) {
        if ($server) { self::$_api_server = $server; }
        
        return $this;
    }
    
    public function getApiserver() {
        return self::$_api_server;
    }
    
    public function setSoftdescriptor($softdescriptor = null) {
        if ($softdescriptor) { self::$_soft_descriptor = $softdescriptor; }
        
        return $this;
    }
    
    public function getSoftdescriptor() {
        return self::$_soft_descriptor;
    }
    
    public function setUser($user = null) {
        if ($user) { self::$_cred_user = $user; }
        
        return $this;
    }
    
    public function getUser() {
        return self::$_cred_user;
    }
    
    public function setVendor($vendor = null) {
        if ($vendor) { self::$_cred_vendor = $vendor; }
        
        return $this;
    }
    
    public function getVendor() {
        return self::$_cred_vendor;
    }
    
    public function setPartner($partner = null) {
        if ($partner) { self::$_cred_partner = $partner; }
        
        return $this;
    }
    
    public function getPartner() {
        return self::$_cred_partner;
    }
    
    public function setPassword($password = null) {
        if ($password) { self::$_cred_password = $password; }
        
        return $this;
    }
    
    public function getPassword() {
        return self::$_cred_password;
    }
}