<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

require_once dirname(__FILE__) . '/../../../Other/plivo/plivo.php';

class ViaMe_Vm_Im_Plivo
{
    const AUTH_ID                   = 'MANJVLODI0MTQYMGU4OD';
    const AUTH_TOKEN                = 'MjZiMzJhM2RkNDBlM2IyZDg1Y2M3NmVlMTVhNWU2';
    
    protected static $_auth_id          = null;
    protected static $_auth_token       = null;
    
    protected static $_api              = null;
    protected static $_instance         = null;
    
    protected static $_numberPool       = array(
        '15597853555',
        '16193831767',
        '16193831768',
        '16193831769',
        '16193831770',
        '16193831771',
        '16193831772',
        '16193831773',
        '16193831774',
        '16193831775',
        '16193831776'
    );
    
    public function __construct($auth_id = null, $auth_token = null) {
        if ($auth_id) { self::$_auth_id = $auth_id; }
        else { self::$_auth_id = self::AUTH_ID; }
        
        if ($auth_token) { self::$_auth_token = $auth_token; }
        else { self::$_auth_token = self::AUTH_TOKEN; }
        
        self::$_api = new RestAPI(self::$_auth_id, self::$_auth_token);
        
        self::$_instance = $this;
    }
    
    public static function getInstance() {
        if (self::$_instance === null) {
            self::$_instance = new self(null, null);
        }

        return self::$_instance;
    }
    
    public static function getApi() {
        if (self::$_api === null) {
            self::$_api = new RestAPI(self::$_auth_id, self::$_auth_token);
        }

        return self::$_api;
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
    
    // We don't want these methods.  We want it all set in the constructor. 
    // We don't want changing of id or token because we will need a new RestAPI.
    /*
    public function setAuthid($auth_id = null) {
        if ($auth_id) { self::$_auth_id = $auth_id; }
        
        return $this;
    }
    
    public function setAuthtoken($auth_token = null) {
        if ($auth_token) { self::$_auth_token = $auth_token; }
        
        return $this;
    }
    */
    
    public function send_message($to = array(), $message = '', $options = array()) {
        $return = $batch = array();
        $to_copy = $to;
        $pool_count = count(self::$_numberPool);
        
        // Load them up into batches
        if (is_array($to_copy) && count($to_copy)) {
            for ($i = $pool_count; $i > 0; $i--) {
                foreach ($to_copy as $index => $token) {
                    $key = $id = null;
                    if (is_array($token)) {
                        if (isset($token['key'])) { $key = $token['key']; }
                        if (isset($token['id'])) { $id = $token['id']; }
                        else {
                            $id = $token[0];
                            if (isset($token[1])) { $key = $token[1]; }
                        }
                    }
                    else { $id = $token; }
                    
                    if ($id) {
                        if (!$key) { $key = substr($id, 0, 7); }
                        
                        if (!($key % $i)) {
                            $batch[$i - 1][] = $id;
                            unset($to_copy[$index]);
                        }
                    }
                }
            }
        }
        #Zend_Debug::Dump($batch);
        unset($to_copy);
        
        // Send out the batches
        foreach ($batch as $key => $val) {
            $return[] = self::$_api->send_message(array_merge(array(
                'src' => self::$_numberPool[$key],
                'dst' => implode('<', $val),
                'text' => $message
            ), $options));
        }
        
        return $return;
    }
    
    
    public function make_call($to = array(), $options = array()) {
        $return = $batch = array();
        $to_copy = $to;
        $pool_count = count(self::$_numberPool);
        
        // Load them up into batches
        if (is_array($to_copy) && count($to_copy)) {
            for ($i = $pool_count; $i > 0; $i--) {
                foreach ($to_copy as $index => $token) {
                    $key = $id = null;
                    if (is_array($token)) {
                        if (isset($token['key'])) { $key = $token['key']; }
                        if (isset($token['id'])) { $id = $token['id']; }
                        else {
                            $id = $token[0];
                            if (isset($token[1])) { $key = $token[1]; }
                        }
                    }
                    else { $id = $token; }
                    
                    if ($id) {
                        if (!$key) { $key = substr($id, 0, 7); }
                        
                        if (!($key % $i)) {
                            $batch[$i - 1][] = $id;
                            unset($to_copy[$index]);
                        }
                    }
                }
            }
        }
        #Zend_Debug::Dump($batch);
        unset($to_copy);
        
        // Send out the batches
        foreach ($batch as $key => $val) {
            $return[] = self::$_api->make_call(array_merge(array(
                'from' => self::$_numberPool[$key],
                'to' => implode('<', $val)
            ), $options));
        }
        
        return $return;
    }
    
    
    public function get_matched_io_phone_number ($token) {
        $pool_count = count(self::$_numberPool);
        
        if ($token) {
            $key = $id = null;
            
            if (is_array($token)) {
                if (isset($token['key'])) { $key = $token['key']; }
                if (isset($token['id'])) { $id = $token['id']; }
                else {
                    $id = $token[0];
                    if (isset($token[1])) { $key = $token[1]; }
                }
            }
            else { $id = $token; }
            
            for ($i = $pool_count; $i > 0; $i--) {
                if ($id) {
                    if (!$key) { $key = substr($id, 0, 7); }
                    
                    if (!($key % $i)) {
                        return self::$_numberPool[$i - 1];
                        break;
                    }
                }
            }
        }
    }
    
    
    // Catch all the rest of the API functions
    public function __call($method, $args) {
        if (method_exists(self::$_api , $method)) {
            return call_user_func_array(array(self::$_api, $method), $args);
        }
        else {
            throw new Exception('Invalid method called.');   
        }
    }
}