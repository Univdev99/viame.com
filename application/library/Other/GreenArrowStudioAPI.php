<?php
class GreenArrowStudioAPI
{
    var $base_url = 'http://lm.levelogic.com/ss/';
    var $apikey = 'a459c2cb';

    var $debug = 0;

    function __construct ($base_url = null, $apikey = null)
    {
        if ( !is_null($base_url) ) $this->base_url = $base_url;
        if ( !is_null($apikey)   ) $this->apikey   = $apikey;
    }

    private static function error_array($message)
    {
        return Array('success' => 0, 'error_message' => $message);
    }

    function call_method($method, $parameters)
    {
        // Check input

        if ( $method == '' )
            return self::error_array('method argument to call_method is required');

        if ( ! is_array($parameters) )
            return self::error_array('parameters argument to call_method must be an Array');

        // Check that config parameters are supplied

        if ( is_null($this->base_url) || $this->base_url == '' )
            return self::error_array('base_url must be set before using call_method');

        if ( is_null($this->apikey) || $this->apikey == '' )
            return self::error_array('apikey must be set before using call_method');

        // Make request 
        $parts = Array();

        $parts[] = "apikey=" . urlencode($this->apikey);
        $parts[] = "method=" . urlencode($method);

        foreach ( $parameters as $key => $value )
        {
            $parts[] = urlencode($key) . "=" . urlencode($value);
        }

        $url = preg_replace('{/+$}', '', $this->base_url) . "/api.php?" . implode("&", $parts);

        if ( $this->debug ) echo("url = $url\n");

        // Do the request
        $data = file_get_contents($url);
        
        if ( $this->debug ) echo("data = $data\n");
        if ( substr($data, 0, 2) != 'a:' ) return self::error_array("Bad data from server: $data");
        $result = unserialize($data);
        if ( ! is_array($result) ) return self::error_array("Bad data from server: $data");

        return $result;
    }
}
