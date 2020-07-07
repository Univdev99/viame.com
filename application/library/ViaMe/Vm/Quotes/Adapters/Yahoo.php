<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

require_once 'Zend/Http/Client.php';

class ViaMe_Vm_Quotes_Adapters_Yahoo
{
    const FEED_URL = 'http://download.finance.yahoo.com/d/quotes.csv';
    
    #const LOOKUP_URL = 'http://y.d.yimg.com/autoc.finance.yahoo.com/autoc';
    #const LOOKUP_URL = 'http://d.yimg.com/aq/autoc';
    #const LOOKUP_URL = 'http://autoc.finance.yahoo.com/autoc';
    const LOOKUP_URL = 'https://static.viame.com/yfacp/autoc';
    protected static $_httpClient = null;    
    
    public static function setHttpClient(Zend_Http_Client $httpClient)
    {
        self::$_httpClient = $httpClient;
    }


    public static function getHttpClient()
    {
        if (!self::$_httpClient instanceof Zend_Http_Client) {
            require_once 'Zend/Http/Client.php';
            self::$_httpClient = new Zend_Http_Client();
        }

        return self::$_httpClient;
    }
    
    
    public static function fetch($symbols = null, $format = null)
    {
        $results = array();
        
        $field_adjust = false;
        
        if (is_string($symbols) && strlen($symbols)) { $symbols = array($symbols); }
        
        if (is_array($symbols) && count($symbols)) {
            // FIXUP - Some symbols return NO results!  Stupid!
            $SYMBOLS_NO_RESULTS = array('^DJI');
            $SYMBOLS_NO_RESULTS_REPLACE = array();
            for ($i = 0; $i < count($symbols); $i++) {
                $match = false;
                if (!isset($symbols[$i])) { continue; }
                
                for ($j = 0; $j < count($SYMBOLS_NO_RESULTS); $j++) {
                    if (strtoupper($symbols[$i]) == $SYMBOLS_NO_RESULTS[$j]) {
                        $symbols[$i] = 'TEST';
                        $SYMBOLS_NO_RESULTS_REPLACE[$i] = $SYMBOLS_NO_RESULTS[$j];
                        break;
                    }
                }
                
                if ($match) {
                    break;
                }
            }

            // Create the str_getcsv function - Remove with upgrade to php 5.3
            if(!function_exists('str_getcsv')) {
                function str_getcsv($input, $delimiter = ",", $enclosure = '"', $escape = "\\") {
                    $fp = fopen("php://memory", 'r+');
                    fputs($fp, $input);
                    rewind($fp);
                    $data = fgetcsv($fp, null, $delimiter, $enclosure); // $escape only got added in 5.3.0
                    fclose($fp);
                    return $data;
                }
            }
            
            try {
                self::setHttpClient(new Zend_Http_Client(
                    null,
                    array(
                        'adapter'     => 'Zend_Http_Client_Adapter_Curl'
                    )
                ));
            } catch (Exception $e) { }
            
            $client = self::getHttpClient();
            $client->setConfig(array('keepalive' => true));
            
            $client->setUri(self::FEED_URL);
            
            $fields = $format;
            
            // Adjust fields - Always start with the symbol, and adjust the fields with commas so we can split later
            $bad_fields = array('k3', 'b6', 'a5', 'f6', 't');
            $fields_array = preg_split('/\s+/', preg_replace(array('/(\d+)/', '/([^\d\s])/'), array('${1} ', ' ${1}'), $fields), -1, PREG_SPLIT_NO_EMPTY);
            if (array_intersect($bad_fields, $fields_array)) {
                foreach ($fields_array as $line => $data) {
                    if (in_array($data, $bad_fields)) {
                        $fields_array[$line] = "sl1t1" . $data . "sl1t1";
                    }
                }
                
                $fields = 'sl1d1t1' . implode('', $fields_array);
                $field_adjust = true;
            }
            
            $client->setParameterGet('f', $fields);
            
            $feed = '';
            while (count($symbols)) {
                $client->setParameterGet('s', implode('+', array_splice($symbols, 0, 200, array())));
                
                $retry = 0;
                $success = false;
                do {
                    try {
                        $response = $client->request(Zend_Http_Client::GET);
                        if ($response->getStatus() == 200) {
                            $feed .= $response->getBody();
                            $success = true;
                        } elseif ($retry > 2) {
                            require_once 'Zend/Feed/Exception.php';
                            throw new Zend_Feed_Exception('Feed failed to load, got response code ' . $response->getStatus());
                        }
                    } catch (Exception $e) { }
                    $retry++;
                } while(!$success && ($retry < 2) && sleep(1));
            }
            
            # 03-09-2015 - No longer sending \r?
            #$results = preg_split('/\r\n/', $feed, -1, PREG_SPLIT_NO_EMPTY);
            $results = preg_split('/\r?\n/', $feed, -1, PREG_SPLIT_NO_EMPTY);
            
            // Fixup Results
            if ($field_adjust) {
                foreach ($results as $line => $data) {
                    if (substr_count($data, ',') >= 4) {
                        list($symbol, $last, $date, $time, $rest) = explode(',', $data, 5);
                        $symbol = preg_quote($symbol, '/');
                        $last = preg_quote($last, '/');
                        $date = preg_quote($date, '/');
                        $time = preg_quote($time, '/');
                        
                        #$results[$line] = preg_replace("/$symbol,$last,$time,(.*?),$symbol,$last,$time/", '"${1}"', $rest);
                        // Remove commas and spaces from the fixed fields
                        $results[$line] = preg_replace_callback("/$symbol,$last,$time,(.*?),$symbol,$last,$time/",
                            create_function('$matches', 'return preg_replace("/[,\s]*/", "", $matches[1]);'),
                            $rest);
                    }
                }
            }
            
            // REPLACE BAD SYMBOLS WITH NO DATA
            foreach ($SYMBOLS_NO_RESULTS_REPLACE as $key => $val) {
                $results[$key] = preg_replace('/TEST/', $val, $results[$key]);
            }
            
            // Break into an array
            foreach ($results as $line => $data) {
                $results[$line] = str_getcsv($data);
            }
        }
        
        return $results;
    }
    
    
    public static function lookup($search = null)
    {
        if (is_string($search) && $search) {
            $client = self::getHttpClient();
            $client->setConfig(array('keepalive' => true));
            
            $client->setUri(self::LOOKUP_URL);
            
            $client->setParameterGet('callback', 'YAHOO.Finance.SymbolSuggest.ssCallback');
            $client->setParameterGet('query', $search);
            $client->setParameterGet('region', 'US');
            $client->setParameterGet('lang', 'en-US');
            
            $response = $client->request(Zend_Http_Client::GET);
            if ($response->getStatus() !== 200) {
                /**
                 * @see Zend_Feed_Exception
                 */
                require_once 'Zend/Feed/Exception.php';
                throw new Zend_Feed_Exception('Feed failed to load, got response code ' . $response->getStatus());
            }
            $feed = $response->getBody();
            
            try {
                $results = Zend_Json::decode(preg_replace('/YAHOO.Finance.SymbolSuggest.ssCallback\((.*)\)/', '${1}', $feed));
            } catch (Exception $e) {
                return;
            }
            
            return $results["ResultSet"]["Result"];
        }
    }
}
