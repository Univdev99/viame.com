<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Quote_FeedController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    
    public function init()
    {
        $this->_helper->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->setHeader('Content-Type', 'text/plain');
    }
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        $lc_params = array(
            'crdelim'       => '',
            'fields'        => '',
            'fieldnames'    => '',
            #'format'        => '',
            #'getnext'       => '',
            #'getprev'       => '',
            'hidepackage'   => '',
            'package'       => 'price',
            'showstatus'    => '',
            'ticker'        => '',
            'symbols'       => '',
            's'             => '',
            'emulate'       => '',
            'f'             => ''
        );
        foreach ($this->_getAllParams() as $key => $val) {
            $lc_params[strtolower($key)] = $val;
        }
        foreach (array('ticker', 'symbol', 's') as $key) {
            if (isset($lc_params[$key])) {
                $lc_params[$key] = preg_replace('/,{2,}/', ',', $lc_params[$key]);
                $lc_params[$key] = preg_replace('/^,*(.*?),*$/', '${1}', $lc_params[$key]);
            }
        }
        
        if (strtolower($lc_params['emulate']) == 'pcquote' && strtolower($lc_params['package']) == 'namelook') {
            return $this->_forward('lookup');
        }
        
        $quotes = new ViaMe_Vm_Quotes();
        
        if (strtolower($lc_params['emulate']) == 'stockgroup') {
            $symbols = $lc_params['symbols'];
            if (!$symbols) { $symbols = $lc_params['s']; }
            
            if ($symbols) {
                $fields = 'snl1c1p2hgvbat1d1kjrxb6a5opt7k3edyq';
                
                $results = $quotes->fetch(preg_split('/[\s,]+/', $symbols, -1, PREG_SPLIT_NO_EMPTY), $fields);
                
                $this->view->results = $results;
            }
            else {
                $this->view->results = array();
            }
            
            $this->getResponse()->setHeader('Content-Type', 'text/xml');
            $this->render('emulate/stockgroup');
        }
        elseif (strtolower($lc_params['emulate']) == 'pcquote') {
            $symbols = $lc_params['ticker'];
            if (!$symbols) { $symbols = $lc_params['s']; }
            
            $conversion = array(
                "ask"            => "a",
                "ask_dec"        => "a",
                "asksize"        => "a5",
                "bid"            => "b",
                "bid_dec"        => "b",
                "bidsize"        => "b6",
                "close"          => "p",
                "close_dec"      => "p",
                "divdate"        => "q",
                "dividend"       => "d",
                "earnings"       => "e",
                "exchange"       => "x",
                "high"           => "h",
                "high_dec"       => "h",
                "last"           => "l1",
                "last_dec"       => "l1",
                "low"            => "g",
                "low_dec"        => "g",
                "name"           => "n",
                "net"            => "c1",
                "net_dec"        => "c1",
                "open"           => "o",
                "open_dec"       => "o",
                "peratio"        => "r",
                "size"           => "k3",
                "tick"           => "s",
                "time"           => "t1",
                "volume"         => "v",
                "yield"          => "y",
                "yrhigh"         => "k",
                "yrlow"          => "j",
                "yrhigh_dec"     => "k",
                "yrlow_dec"      => "j"
            );
            /*
            bidasksize
            country 
            divfreq
            errormessage
            shares
            status
            volatility
            */
            switch (strtolower($lc_params['package'])) {
                case 'fundamental':
                    $fields = 'tick,name,yrhigh,yrlow,divfreq,earnings,dividend,shares,peratio,divdate,volatility,yield';
                    break;
                case 'custom':
                    $fields = strtolower($lc_params['fields']);
                    break;
                default:
                    $this->_setParam('package', 'price');
                    $fields = 'tick,last,net,time,size,volume,open,high,low,close,bid,ask,bidasksize,exchange';
                    break;
            }
            
            $fields_array = preg_split('/,\s*/', strtolower($fields), -1, PREG_SPLIT_NO_EMPTY);
            
            $fetchfields = null;
            foreach ($fields_array as $temp_field) {
                if (array_key_exists($temp_field, $conversion)) {
                    $fetchfields .= $conversion[$temp_field];
                }
            }
            $fetchfields = 'b6a5sxe1'.$fetchfields;
            
            $results = $quotes->fetch(preg_split('/[\s,]+/', $symbols, -1, PREG_SPLIT_NO_EMPTY), $fetchfields);
            
            // DISPLAY
            if (!$lc_params['hidepackage']) {
                echo 'Package: ' . strtoupper($lc_params['package']) . "\n";
            }
            if ($lc_params['showstatus']) {
                echo "Status: OK\n\n";
            }
            
            if ($symbols) {
                $odata = array(
                    'country'       => 'N/A',
                    'divfreq'       => 'N/A',
                    'errormessage'  => 'errormessage',
                    'shares'        => 'N/A',
                    'status'        => 'OK',
                    'volatility'    => 'N/A'
                );
                foreach (preg_split('/[\s,]+/', $symbols, -1, PREG_SPLIT_NO_EMPTY) as $index => $symbol) {
                    if (isset($results[$index])) {
                        $line = $results[$index];
                        list($tbidsize, $tasksize, $tsymbol, $texchange, $terror) = array_splice($line, 0, 5);
                        if ($tbidsize != 'N/A' && $tasksize != 'N/A') {
                            $odata{'bidasksize'} = "${tbidsize}x${tasksize}";
                        }
                        else {
                            $odata{'bidasksize'} = 'N/A';
                        }
                        
                        if ($terror == 'N/A') {
                            $count = 0;
                            foreach ($fields_array as $ind => $data) {
                                $to_display = '';
                                $errorfield = false;
                                
                                if (!(array_key_exists($data, $conversion) || array_key_exists($data, $odata))) {
                                    $errorfield = true;
                                }
                                
                                if ($lc_params['fieldnames'] && !$errorfield) {
                                    $to_display .= strtoupper($data) . '=';
                                }
                                
                                if (!$errorfield) {
                                    if (array_key_exists($data, $odata)) {
                                        $to_display .= ($lc_params['crdelim'] ? $odata{$data} : str_replace(',', '', $odata{$data}));
                                        $count--;
                                    }
                                    else {
                                        $to_display .= ($lc_params['crdelim'] ? $line[$count] : str_replace(',', '', $line[$count]));
                                    }
                                }
                                else {
                                    $to_display .= "(Unrecognized field=". strtoupper($data) .')';
                                    $count--;
                                }
                                
                                if ($lc_params['crdelim']) {
                                    $to_display .= "\n";
                                }
                                elseif (($ind + 1) < count($fields_array)) {
                                    $to_display .= ', ';
                                }
                                
                                echo $to_display;
                                $count++;
                            }
                        }
                        else {
                            echo "No Data found for Ticker: $symbol";
                        }
                    }
                    else {
                        echo "No Data found for Ticker: $symbol";
                    }
                    
                    echo "\n";
                }
            }
        }
        else {
            // Default - Yahoo Relay
            $symbols = $lc_params['s'];
            $fields = $lc_params['f'];
            
            $results = $quotes->fetch(preg_split('/[\s,]+/', $symbols, -1, PREG_SPLIT_NO_EMPTY), $fields);
            
            foreach ($results as $line) {
                for ($i = 0; $i < count($line); $i++) { $line[$i] = str_replace(',', '', $line[$i]); }
                echo implode(', ', $line) . "\n";
            }
        }
    }
    
    
    public function lookupAction()
    {
        $lc_params = array(
            'crdelim'       => '',
            'fields'        => '',
            'fieldnames'    => '',
            #'format'        => '',
            #'getnext'       => '',
            #'getprev'       => '',
            'hidepackage'   => '',
            'package'       => 'NAMELOOK',
            'showstatus'    => '',
            'ticker'        => '',
            'keyword'       => '',
            's'             => '',
            'query'         => '',
            'emulate'       => '',
            'f'             => ''
        );
        foreach ($this->_getAllParams() as $key => $val) {
            $lc_params[strtolower($key)] = $val;
        }
        
        $quotes = new ViaMe_Vm_Quotes();
        
        if (strtolower($lc_params['emulate']) == 'stockgroup') {
            $query = $lc_params['keyword'];
            if (!$query) { $query = $lc_params['s']; }
            
            if ($query) {
                $results = $quotes->lookup($query);
                
                $this->view->results = $results;
                
                $this->getResponse()->setHeader('Content-Type', 'text/xml');
            }
            else {
                $this->view->results = array();
            }
            
            $this->render('emulate/stockgroup-lookup');
        }
        elseif (strtolower($lc_params['emulate']) == 'pcquote') {
            $query = $lc_params['ticker'];
            if (!$query) { $query = $lc_params['s']; }
            
            $conversion = array(
                "tick"        => "symbol",
                "name"        => "name",
                "exchange"    => "exchDisp"
            );
            
            $fields = strtolower($lc_params['fields']);
            if (!$fields) { $fields = 'tick,name,exchange,country'; }
            $fields_array = preg_split('/,\s*/', strtolower($fields), -1, PREG_SPLIT_NO_EMPTY);
            
            $results = $results = $quotes->lookup($query);
            
            // DISPLAY
            if (!$lc_params['hidepackage']) {
                echo "Package: NAMELOOK\n";
            }
            if ($lc_params['showstatus']) {
                echo "Status: OK\n\n";
            }
            
            if ($results) {
                $odata = array(
                    'country'       => 'N/A'
                );
                
                foreach ($results as $line) {
                    if (!$lc_params['crdelim']) { $line['name'] = str_replace(',', '', $line['name']); }
                    
                    $count = 0;
                    foreach ($fields_array as $ind => $data) {
                        $to_display = '';
                        $errorfield = false;
                        
                        if (!(array_key_exists($data, $conversion) || array_key_exists($data, $odata))) {
                            $errorfield = true;
                        }
                        
                        if ($lc_params['fieldnames'] && !$errorfield) {
                            $to_display .= strtoupper($data) . '=';
                        }
                        
                        if (!$errorfield) {
                            if (array_key_exists($data, $odata)) {
                                $to_display .= $odata{$data};
                                $count--;
                            }
                            elseif (isset($line[$conversion{$data}])) {
                                $to_display .= $line[$conversion{$data}];
                            }
                        }
                        else {
                            $to_display .= "(Unrecognized field=". strtoupper($data) .')';
                            $count--;
                        }
                        
                        if ($lc_params['crdelim']) {
                            $to_display .= "\n";
                        }
                        elseif (($ind + 1) < count($fields_array)) {
                            $to_display .= ', ';
                        }
                        
                        echo $to_display;
                        $count++;
                    }
                    
                    echo "\n";
                }
            }
        }
        else {
            // Default - Yahoo Relay
            $query = $lc_params['s'];
            
            if ($query) {
                $results = $quotes->lookup($query);
                
                foreach ($results as $line) {
                    $line['name'] = str_replace(',', '', $line['name']);
                    echo implode(', ', $line) . "\n";
                }
            }
        }
    }
    
    
    public function vcsqpAction()
    {
        echo file_get_contents("https://api.iextrading.com/1.0/stock/market/batch?types=quote&filter=latestPrice&symbols=" . $this->_getParam('s'));
        
        return;
    }
}