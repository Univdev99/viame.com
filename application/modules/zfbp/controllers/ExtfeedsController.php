<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 *
 * XML Generator - Like a TwiML Replacement
 */

class Zfbp_ExtfeedsController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        // Load up the registry variables
        $this->registryLoader($this);
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        #$contextSwitch->removeContext('json');
        #$contextSwitch->removeContext('xml');
        
        if (!$contextSwitch->hasContext('atom')) {
            $contextSwitch->addContext('atom', 
                array(
                    #'suffix'    => 'atom',
                    'headers'   => array('Content-Type' => 'application/atom+xml')
                )
            );
        }
        if (!$contextSwitch->hasContext('rss')) {
            $contextSwitch->addContext('rss', 
                array(
                    #'suffix'    => 'rss',
                    'headers'   => array('Content-Type' => 'application/rss+xml')
                )
            );
        }
        
        $contextSwitch
            ->addActionContext('index', array('json', 'xml', 'atom', 'rss'))
            ->setAutoJsonSerialization(false)
            ->initContext();
            
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
    }
    
    public function preDispatch() { }
    
    public function indexAction()
    {
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $data = array();
        $data['title'] = 'External Feeds';
        $data['link'] = 'http://' . $this->vars->host;
        $data['published'] = time();
        $data['lastUpdate'] = time();
                    
        $data['charset'] = 'UTF-8';
        $data['language'] = 'en-US';
        $data['email'] = (isset($this->community->email) && $this->community->email ? $this->community->email : $this->config->admin->email);
        $data['webmaster'] = (isset($this->community->email) && $this->community->email ? $this->community->email : $this->config->admin->email);
            
        $entries = array();
        
        $feed_urls = array(
            'http://feeds.finance.yahoo.com/rss/2.0/headline?region=US&lang=en-US&s={symbol}',
        	'https://www.google.com/finance/company_news?output=rss&q={symbol}',
        	'http://www.foxbusiness.com/search-results/urss?ticker={symbol}',
        	'http://feeds2.benzinga.com/feed-SCNkeau6rksdf903/{symbol}/rss.xml'
        );
        if ($this->_getParam('set') == 'blogs') {
            $feed_urls = array(
            'https://ajax.googleapis.com/ajax/services/search/blogs?v=1.0&rsz=8&start=0&q={symbol}',
        	'https://ajax.googleapis.com/ajax/services/search/blogs?v=1.0&rsz=8&start=8&q={symbol}',
        	'https://ajax.googleapis.com/ajax/services/search/blogs?v=1.0&rsz=8&start=16&q={symbol}',
        	'https://ajax.googleapis.com/ajax/services/search/blogs?v=1.0&rsz=8&start=24&q={symbol}'
        );
        }
        elseif ($this->_getParam('set') == 'twitter') {
            $feed_urls = array(
                'https://api.twitter.com/1.1/search/tweets.json?q=${symbol}'
            );
        }
        elseif ($this->_getParam('set') == 'featured') {
            /*
            $feed_urls = array(
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=ETFM',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=GIGL',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=SUNE',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=AVID',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=SQBG',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=ALU',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=FSI',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=ACI',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=NVTA',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=ENZY',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=STAF',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=EMGL',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=SCQBF',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=BRFH',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=FONU',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=CDNL',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=PMCB',
                'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10&symbol=SPYR'
            );
            */
            $feed_urls = array();
            
            foreach ($this->db->fetchCol("SELECT DISTINCT symbol FROM quote_symbols WHERE featured ORDER BY symbol") as $feature) {
                $feed_urls[] = 'https://www.smallcapnetwork.com/zfbp/extfeeds/?limit=10' . ($this->_getParam('twitter') ? '&twitter=1' : '') . '&symbol=' . $feature;
            }
        }
        
        $feed_urls = preg_replace_callback('/\{.*?\}/', function($match) { $match = preg_replace('/\{(.*?)\}/', '$1', $match); return urlencode($this->_getParam($match[0])); }, $feed_urls);
        
        if (count($feed_urls)) {
            if ($this->_getParam('set') == 'twitter') {
                // Twitter Only - Have to manually create the BEARER token
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer AAAAAAAAAAAAAAAAAAAAAAIlDAAAAAAAQKVL2AWhL8sudumJPeZj5KIL71E%3DIbsYDUea4Ru0mwCNkUq6QzSt6JWzJhuGiOt8Sfm7XlWhVVHqCT'));
                
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
                
                $date = new Zend_Date();
                
                foreach ($feed_urls as $url) {
                    if (isset($this->cache) && (($raw = $this->cache->memcache->load(hash('md5', $url))))) {
                        $response_status = 200;
                    }
                    else {
                        curl_setopt($ch, CURLOPT_URL, $url);
                
                        $raw = curl_exec($ch);
                        $response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                        
                        if (isset($this->cache)) { $this->cache->memcache->save($raw, null, array(), 3600); }
                    }
                    
                    #Zend_Debug::Dump($response_status);
                    #Zend_Debug::Dump(json_decode($raw));
                    
                    if ($response_status == 200) {
                        $data_in = json_decode($raw);
                        foreach ($data_in->statuses as $item){
                            #Zend_Debug::Dump($item);
                            #Zend_Debug::Dump($item->created_at);
                            $date->set(date(DATE_RSS, strtotime($item->created_at)), Zend_Date::RSS);
                            #echo date(DATE_RSS, strtotime($item->created_at));
                            
                            $entries[] = array(
                                'title' => $item->text,
                                'link' => 'http://twitter.com/' . $item->user->screen_name,
                                'guid' => $item->text,
                                'description' => 'No Description',
                                'summary' => null,
                                'content' => null,
                                'published' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $date->get(Zend_Date::ISO_8601) : $date->getTimestamp()),
                                'lastUpdate' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $date->get(Zend_Date::ISO_8601) : $date->getTimestamp()),
                                'sortby' => $date->getTimestamp()
                            );
                            
                        }
                    }
                }
                
                curl_close ($ch);
            }
            elseif ($this->_getParam('set') == 'blogs') {
                // Google Blog Search Ajax API
                $ch = curl_init();
                
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 60);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
                
                $date = new Zend_Date();
                
                foreach ($feed_urls as $url) {
                    if (isset($this->cache) && (($raw = $this->cache->memcache->load(hash('md5', $url))))) {
                        $response_status = 200;
                    }
                    else {
                        curl_setopt($ch, CURLOPT_URL, $url . '&userip=' . $_SERVER['REMOTE_ADDR']);
                        
                        $raw = curl_exec($ch);
                        $response_status = strval(curl_getinfo($ch, CURLINFO_HTTP_CODE));
                        
                        if (isset($this->cache)) { $this->cache->memcache->save($raw, null, array(), 3600); }
                    }
                    
                    #Zend_Debug::Dump($response_status);
                    #Zend_Debug::Dump(json_decode($raw));
                    
                    if ($response_status == 200) {
                        $data_in = json_decode($raw);
                        foreach ($data_in->responseData->results as $item){
                            #Zend_Debug::Dump($item);
                            #Zend_Debug::Dump($item->created_at);
                            $date->set(date(DATE_RSS, strtotime($item->publishedDate)), Zend_Date::RSS);
                            #echo date(DATE_RSS, strtotime($item->created_at));
                            
                            $entries[] = array(
                                'title' => $item->titleNoFormatting,
                                'link' => $item->postUrl,
                                'guid' => $item->postUrl,
                                'description' => ($item->content ? $item->content : 'No Description'),
                                'summary' => $item->content,
                                'content' => $item->content,
                                'published' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $date->get(Zend_Date::ISO_8601) : $date->getTimestamp()),
                                'lastUpdate' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $date->get(Zend_Date::ISO_8601) : $date->getTimestamp()),
                                'sortby' => $date->getTimestamp()
                            );
                            
                        }
                    }
                }
                
                curl_close ($ch);
            }
            else {
                $feed = new Zend_Feed_Reader();
                $feed->setHttpClient(new Zend_Http_Client(null, array('adapter' => 'Zend_Http_Client_Adapter_Curl')));
                if (isset($this->cache->memcache)) {
                    $feed->setCache($this->cache->memcache);
                }
                
                foreach ($feed_urls as $url) {
                    #echo $url . '<br />';
                    try {
                        $data_in = $feed->import($url);
                        #Zend_Debug::Dump($data_in);
                        if (isset($data_in) && $data_in) {
                            foreach ($data_in as $item){
                                if ($item->getDateCreated()) {
                                    $entries[] = array(
                                        'title' => $item->getTitle() . ($this->_getParam('twitter') && $this->_getParam('symbol') ? ' $' . $this->_getParam('symbol') : ''),
                                        'link' => htmlentities($item->getPermalink()),
                                        'guid' => $item->getId(),
                                        'description' => ($item->getDescription() ? $item->getDescription() : 'No Description'),
                                        'summary' => $item->getSummary(),
                                        'content' => $item->getContent(),
                                        'published' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $item->getDateCreated()->get(Zend_Date::ISO_8601) : $item->getDateCreated()->getTimestamp()),
                                        'lastUpdate' => (($contextSwitch->getCurrentContext() == 'json' || $contextSwitch->getCurrentContext() == 'xml') ? $item->getDateCreated()->get(Zend_Date::ISO_8601) : $item->getDateCreated()->getTimestamp()),
                                        'sortby' => $item->getDateCreated()->getTimestamp()
                                    );
                                }
                            }
                        }
                    } catch (Exception $e) { }
                }
            }
        }
        
        if (count($entries)) {
            // Sort
            usort($entries, function($a, $b) {
                return $b['sortby'] - $a['sortby'];
            });
            
            // Remove Duplicates and Limit
            $displayed = array();
            $counter = 0;
            for ($i = 0; $i < count($entries); $i++) {
                if (isset($displayed[$entries[$i]['link']]) || isset($displayed[$entries[$i]['title']]) || isset($displayed[$entries[$i]['guid']])) {
                    array_splice($entries, $i, 1);
                    continue;
                }
                
                $displayed[$entries[$i]['link']] = $displayed[$entries[$i]['title']] = $displayed[$entries[$i]['guid']] = true;
                $counter++;
                
                if ($this->_getParam('limit') && (($counter) >= $this->_getParam('limit'))) {
                    array_splice($entries, $counter, count($entries));
                    break;
                }
            }
            $data['entries'] = $entries;
            
            #Zend_Debug::Dump($entries);
        }
        
        #Zend_Debug::Dump($data);
        
        if ($contextSwitch->getCurrentContext() == 'json') {
            if ($this->_getParam('callback')) { echo $this->_getParam('callback') . '('; }
            echo json_encode($data);
            if ($this->_getParam('callback')) { echo ');'; }
            #Zend_Debug::Dump($data);
        }
        elseif ($contextSwitch->getCurrentContext() == 'xml') {
            require_once dirname(__FILE__) . '/../../../library/Other/Array2XML.php';
            $xml = @Array2XML::createXML('feed', $data);
            echo $xml->saveXML();
        }
        else {
            #Zend_Debug::Dump($data);
            $feed = @Zend_Feed::importBuilder(new Zend_Feed_Builder($data), ($contextSwitch->getCurrentContext() ? $contextSwitch->getCurrentContext() : 'rss'));
            $feed->send();
        }
    }
}