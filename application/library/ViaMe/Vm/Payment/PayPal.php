<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Vm_Payment_PayPal
{
    const API_VERSION               = '95.0';
    const DEFAULT_SOFT_DESCRIPTOR   = 'Levelogic';
    
    const LIVE_API_SERVER           = 'https://api-3t.paypal.com/nvp/';
    const LIVE_IPN_SERVER           = 'https://www.paypal.com/cgi-bin/webscr/';
    const LIVE_CRED_USER            = 'akang_api1.levelogic.com';
    const LIVE_CRED_PASSWORD        = 'X6YVCKMAH4376NVC';
    const LIVE_CRED_SIGNATURE       = 'Ark.0In-VVQ11cVLUapLGTwtbICMAC31TEKBwB28nZRgkBau-QxFjIya';
    
    /*
    const LIVE_API_SERVER           = 'https://api-3t.sandbox.paypal.com/nvp/';
    const LIVE_IPN_SERVER           = 'https://www.sandbox.paypal.com/cgi-bin/webscr/';
    const LIVE_CRED_USER            = 'scn_1355217140_biz_api1.levelogic.com';
    const LIVE_CRED_PASSWORD        = '1355217209';
    const LIVE_CRED_SIGNATURE       = 'AZZQXc9Y.RHZ76975RaoJDnaHf52AXsYcggxiH6DXt8-fkwJ0JZ1RNUD';
    */
    
    const TEST_API_SERVER           = 'https://api-3t.sandbox.paypal.com/nvp/';
    const TEST_IPN_SERVER           = 'https://www.sandbox.paypal.com/cgi-bin/webscr/';
    const TEST_CRED_USER            = 'scn_1355217140_biz_api1.levelogic.com';
    const TEST_CRED_PASSWORD        = '1355217209';
    const TEST_CRED_SIGNATURE       = 'AZZQXc9Y.RHZ76975RaoJDnaHf52AXsYcggxiH6DXt8-fkwJ0JZ1RNUD';
    
    protected static $_api_server       = null;
    protected static $_ipn_server       = null;
    protected static $_soft_descriptor  = null;
    protected static $_subject          = null;
    protected static $_cred_user        = null;
    protected static $_cred_password    = null;
    protected static $_cred_signature   = null;
    
    protected static $_instance = null;
    
    public function __construct($mode = null) {
        if ($mode === 'LIVE') {
            self::$_api_server      = self::LIVE_API_SERVER;
            self::$_ipn_server      = self::LIVE_IPN_SERVER;
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_cred_user       = self::LIVE_CRED_USER;
            self::$_cred_password   = self::LIVE_CRED_PASSWORD;
            self::$_cred_signature  = self::LIVE_CRED_SIGNATURE;
        }
        else {
            self::$_api_server      = self::TEST_API_SERVER;
            self::$_ipn_server      = self::TEST_IPN_SERVER;
            self::$_soft_descriptor = self::DEFAULT_SOFT_DESCRIPTOR;
            self::$_cred_user       = self::TEST_CRED_USER;
            self::$_cred_password   = self::TEST_CRED_PASSWORD;
            self::$_cred_signature  = self::TEST_CRED_SIGNATURE;
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
            $res = null;
            
            $trxn = array_merge(
                array(
                    'USER'              => self::$_cred_user,
                    'PWD'               => self::$_cred_password,
                    'SIGNATURE'         => self::$_cred_signature,
                    'SOFTDESCRIPTOR'    => self::$_soft_descriptor,
                    'MERCHDESCR'        => self::$_soft_descriptor,
                    'MERCHSVC'          => $_SERVER['SERVER_NAME'],
                    //'SUBJECT'          => self::$subject,
                    'VERSION'           => self::API_VERSION,
                    'IPADDRESS'         => $_SERVER['REMOTE_ADDR']
                ),
                $trxn
            );
            
            $results = self::curlPost(self::$_api_server, http_build_query($trxn)); // $results[status,raw|response_status|errno|errstr]
            
            if ($results['status'] && $results['raw']) {
                parse_str($results['raw'], $res);
                
                $results['status'] = ($res['ACK'] == 'Success' ? true : false); // Change the status based on ACK
                $results['res'] = $res;
            }
               
            return $results;
        }
        else {
            return array('status' => false, 'errno' => 0, 'errstr' => 'No data passed for transaction.');
        }
    }
    
    public static function webhook($postData = null) {
        $postString = 'cmd=_notify-validate';
        
        if (is_string($postData)) {
            $postString .= "&" . $postData;
        }
        elseif (is_array($postData)) {
            // use provided data array
            foreach ($postData as $key => $value) {
                $postString .= "&$key=" . urlencode($value);
            }
        }
        else {
            // use raw POST data
            $raw_post_array = explode('&', file_get_contents('php://input'));
            $myPost = array();
            foreach ($raw_post_array as $keyval) {
                $keyval = explode ('=', $keyval);
                if (count($keyval) == 2) {
                    $myPost[$keyval[0]] = urldecode($keyval[1]);
                }
            }
            // read the post from PayPal system and add 'cmd'
            foreach ($myPost as $key => $value) {        
                if(function_exists('get_magic_quotes_gpc') && get_magic_quotes_gpc() == 1) { 
                    $value = urlencode(stripslashes($value)); 
                } else {
                    $value = urlencode($value);
                }
                $postString .= "&$key=$value";
            }
        }
        
        if ($postString) {
            $results = self::curlPost(self::$_ipn_server, $postString); // $results[status,raw|response_status|errno|errstr]
            
            if ($results['status'] && $results['raw']) {
                $results['status'] = (($results['response_status'] == '200') && ($results['raw'] == 'VERIFIED' ? true : false)); // Change the status based on raw
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
    
    public function setIpnserver($server = null) {
        if ($server) { self::$_ipn_server = $server; }
        
        return $this;
    }
    
    public function getIpnserver() {
        return self::$_ipn_server;
    }
    
    public function setSoftdescriptor($softdescriptor = null) {
        if ($softdescriptor) { self::$_soft_descriptor = $softdescriptor; }
        
        return $this;
    }
    
    public function getSoftdescriptor() {
        return self::$_soft_descriptor;
    }
    
    public function setSubject($subject = null) {
        if ($subject) { self::$_subject = $subject; }
        
        return $this;
    }
    
    public function getSubject() {
        return self::$_subject;
    }
    
    public function setUser($user = null) {
        if ($user) { self::$_cred_user = $user; }
        
        return $this;
    }
    
    public function getUser() {
        return self::$_cred_user;
    }
    
    public function setPassword($password = null) {
        if ($password) { self::$_cred_password = $password; }
        
        return $this;
    }
    
    public function getPassword() {
        return self::$_cred_password;
    }
    
    public function setSignature($signature = null) {
        if ($signature) { self::$_cred_signature = $signature; }
        
        return $this;
    }
    
    public function getSignature() {
        return self::$_cred_signature;
    }
}