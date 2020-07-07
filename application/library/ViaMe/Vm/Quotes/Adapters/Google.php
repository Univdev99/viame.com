<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
    
    11/12/2013 - Google discontinued igoogle and access to http://www.google.com/ig/api
*/

require_once 'Zend/Http/Client.php';
require_once 'Other/Array2XML.php';
require_once 'Other/XML2Array.php';

class ViaMe_Vm_Quotes_Adapters_Google
{
    const FEED_URL = 'http://www.google.com/finance/info';
    #const FEED_URL2 = 'http://www.google.com/ig/api';
    
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
    
    
    public static function fetch($symbols = null)
    {
        $results = $symbols_backup = array();
        
        if (is_string($symbols) && strlen($symbols)) { $symbols = array($symbols); }
        
        if (is_array($symbols) && count($symbols)) {
            $symbols_backup = $symbols;
            
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
            
            $feed = $feed2 = '';
            while (count($symbols)) {
                $symbol_set = array_splice($symbols, 0, 200, array()); // 200 at a time
                
                $client->setUri(self::FEED_URL);
                $client->setParameterGet('client', 'ig');
                $client->setParameterGet('q', implode(',', $symbol_set));
                
                $retry = 0;
                $success = false;
                do {
                    try {
                        $response = $client->request(Zend_Http_Client::GET);
                        if ($response->getStatus() == 200) {
                            $feed .= trim(preg_replace(array('/[\r\n]/', '/.*\[(.*)\]/s', '/  /', '/" ,"/', '/" : "/', '/\}[\s,]*?\{/s', '/LINEBREAK/'), array(' ', '$1', ' ', '", "', '": "', '}LINEBREAK{', "\n"), $response->getBody())) . "\n";
                            $success = true;
                        } elseif ($retry > 2) {
                            require_once 'Zend/Feed/Exception.php';
                            throw new Zend_Feed_Exception('Feed failed to load, got response code ' . $response->getStatus());
                        }
                    } catch (Exception $e) { }
                    $retry++;
                } while(!$success && ($retry < 2) && sleep(1));
                
                /*
                $sym_params = array();
                foreach ($symbol_set as $temp_symbol) {
                    array_push($sym_params, "stock=$temp_symbol");
                }
                $client->setUri(self::FEED_URL2 . '?' . implode('&', $sym_params));
                
                $retry = 0;
                $success = false;
                do {
                    try {
                        $response = $client->request(Zend_Http_Client::GET);
                        if ($response->getStatus() == 200) {
                            $tempArray = XML2Array::createArray($response->getBody());
                            if (count($symbols_backup) == 1) {
                                $tempArray['xml_api_reply']['finance'] = array($tempArray['xml_api_reply']['finance']);
                            }
                            foreach ($tempArray['xml_api_reply']['finance'] as $line) {
                                $feed2 .= '{ ';
                                $counter = 0;
                                foreach ($line as $name => $item) {
                                    if (isset($line[$name]['@attributes']['data'])) {
                                        if ($counter) { $feed2 .= ', '; }
                                        $feed2 .= '"' . $name . '": "' . $line[$name]['@attributes']['data'] . '"';
                                        $counter++;
                                    }
                                }
                                $feed2 .= " }\n";
                            }
                            $success = true;
                        } elseif ($retry > 2) {
                            require_once 'Zend/Feed/Exception.php';
                            throw new Zend_Feed_Exception('Feed failed to load, got response code ' . $response->getStatus());
                        }
                    } catch (Exception $e) { }
                    $retry++;
                } while(!$success && ($retry < 2) && sleep(1));
                */
            }
            
            #Zend_Debug::Dump($feed);
            #Zend_Debug::Dump($feed2);
            
            $data = array();
            
            foreach (explode("\n", $feed) as $line) {
                $zz = json_decode($line, true);
                if ($zz && isset($zz['t'])) {
                    if (!isset($data[$zz['t']])) { $data[strtoupper($zz['t'])] = array(); }
                    $data[strtoupper($zz['t'])] = array_merge($data[$zz['t']], $zz);
                }
            }
            /*
            foreach (explode("\n", $feed2) as $line) {
                $zz = json_decode($line, true);
                if ($zz && isset($zz['symbol'])) {
                    if (!isset($data[$zz['symbol']])) { $data[strtoupper($zz['symbol'])] = array(); }
                    $data[strtoupper($zz['symbol'])] = array_merge($data[$zz['symbol']], $zz);
                }
            }
            */
            
            #Zend_Debug::Dump($data);
            
            foreach ($symbols_backup as $symbol) {
                if (
                    isset($data[strtoupper($symbol)]) &&
                    (
                        #(isset($data[strtoupper($symbol)]['delay']) && $data[strtoupper($symbol)]['delay'] == 0) ||
                        (
                            isset($data[strtoupper($symbol)]['e']) && 
                            in_array(strtoupper($data[strtoupper($symbol)]['e']),
                                array(
                                    'CNSX',		// Canadian National Stock Exchange
                                    'NASDAQ',	// The NASDAQ Stock Market, Inc. – NASDAQ Last Sale
                                    'NYSE',		// New York Stock Exchange
                                    'NYSEARCA',	// NYSE ARCA
                                    'BIT',		// Borsa Italiana Milan Stock Exchange
                                    'CPH',		// NASDAQ OMX Copenhagen
                                    'FRA',		// Deutsche Börse Frankfurt Stock Exchange
                                    'HEL',		// NASDAQ OMX Helsinki
                                    'ICE',		// NASDAQ OMX Iceland
                                    'LON',		// London Stock Exchange
                                    'MCX',		// Moscow Exchange
                                    'RSE',		// NASDAQ OMX Riga
                                    'STO',		// NASDAQ OMX Stockholm
                                    'TAL',		// NASDAQ OMX Tallinn
                                    'VSE',		// NASDAQ OMX Vilnius
                                    'BOM',		// Bombay Stock Exchange Limited
                                    'NSE',		// National Stock Exchange of India
                                    'SGX',		// Singapore Exchange
                                    'SHA',		// Shanghai Stock Exchange
                                    'SHE',		// Shenzhen Stock Exchange
                                    'TPE',		// Taiwan Stock Exchange
                                )
                            )
                        )
                    )
                ) {
                    array_push($results, $data[strtoupper($symbol)]);
                }
                else {
                    array_push($results, null);
                }
            }
        }
        
        return $results;
    }
}
