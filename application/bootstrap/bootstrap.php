<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Bootstrap extends Zend_Application_Bootstrap_Bootstrap
{
    protected function _initApp()
    {
        // Start the application timer
        $timer = microtime(true);
        
        // FIXUPS
        $_SERVER['SCRIPT_URL'] = preg_replace('/\?.*$/', '', $_SERVER['REQUEST_URI']);
        
        // Get Config
        $config = new Zend_Config_Xml(APPLICATION_PATH . '/config/config.xml', APPLICATION_ENV, true);
        Zend_Registry::set('config', $config);
        
        
        // Autoloader Fallback
        if (isset($config->autoload->FallbackAutoloader)) {
            $this->getApplication()->getAutoloader()->setFallbackAutoloader($config->autoload->FallbackAutoloader);
        }
        
        // Setup the plugin loader cache file
        if (isset($config->autoload->includeFileCache) && file_exists($config->autoload->includeFileCache)) {
            include_once $config->autoload->includeFileCache;
            Zend_Loader_PluginLoader::setIncludeFileCache($config->autoload->includeFileCache);
        }
        
        
        // DB Resource
        $this->bootstrap('db');
        $db = $this->getResource('db');
        $db->setFetchMode(Zend_Db::FETCH_OBJ);
        Zend_Registry::set('db', $db);
        
        
        // Set TimeZone
        date_default_timezone_set($config->timezone);
        
        // Set some helpful variables
        $vars = new StdClass;
        $vars->timer = $timer;
        $vars->APP_PATH = APPLICATION_PATH;
        $vars->ssl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on' ? true : false);
        
        $vars->host = ($vars->ssl ? 'https://' : 'http://') . isset($_SERVER['HTTP_HOST']) ? preg_replace('/:.*$/', '', $_SERVER['HTTP_HOST']) : $_SERVER['SERVER_NAME'];
        //$vars->host = ($vars->ssl ? 'https://' : 'http://') . $vars->host;
        if (!strpos($vars->host, '.')) {
            $vars->host = 'www.' . $config->default_domain;
        }
        
        $vars->static_host = ($vars->ssl ? 'https://' : 'http://') . ((isset($config->static_host) && $config->static_host) ? $config->static_host : $vars->host);
		$vars->static_js_css_host = ($vars->ssl ? 'https://' : 'http://') . ((isset($config->static_host) && $config->static_host) ? str_replace('https','http',$config->static_host) : str_replace('https','http',$vars->host));
        /*
            SSL Notes - Could also do an https mapping for other resources like config->upload->public_server, config->upload->picture_server,
            and ContentLink view helper, but, for now, we want these resources and links to go to http servers.  We want them off of the https
            server as fast/soon as possible.
            
            Also, can use https://openicon.appspot.com instead of http://www.stdicon.com in file module
        */
        $vars->request_uri = $_SERVER['REQUEST_URI'];
        $vars->query_string = $_SERVER['QUERY_STRING'];
        $vars->host_parts = explode('.', $vars->host);
        $vars->domain_name = $vars->host_parts[count($vars->host_parts) - 2] . '.' . $vars->host_parts[count($vars->host_parts) - 1];
        if (isset ($vars->host_parts[count($vars->host_parts) - 3])) {
            $vars->host_name = $vars->host_parts[count($vars->host_parts) - 3];
        }
        else {
            $vars->host_name = 'www';
        }
        if (isset ($vars->host_parts[count($vars->host_parts) - 4])) {
            $parts = explode('_', $vars->host_parts[count($vars->host_parts) - 4]);
            $vars->language = $parts[0] . ((isset($parts[1])) ? '_' . strtoupper($parts[1]) : '');
        }
        if (isset ($vars->host_parts[count($vars->host_parts) - 5])) {
            // Remainder
            $vars->pre_lang = implode('.', array_slice($vars->host_parts, 0, (count($vars->host_parts) - 5 + 1)));
        }
        
        
        Zend_Registry::set('vars', $vars);
        
        // Session Resource
        $this->bootstrap('session');
        // Set Zend_Session Options
        Zend_Session::setOptions(array(
            'cookie_path' => '/',
            'cookie_domain' => '.' . $vars->domain_name
            )
        );
        
        
        // Set Error Reporting
        if      ($config->debug >= 7) { error_reporting(E_ALL | E_STRICT); }
        else if ($config->debug >= 6) { error_reporting(E_ALL ^ E_NOTICE | E_STRICT); }
        else if ($config->debug >= 5) { error_reporting(E_ALL ^ E_NOTICE); }
        else if ($config->debug >= 4) { error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING); }
        else if ($config->debug >= 3) { error_reporting(E_COMPILE_ERROR | E_RECOVERABLE_ERROR | E_ERROR | E_CORE_ERROR); }
        else if ($config->debug >= 2) { error_reporting(E_COMPILE_ERROR | E_ERROR | E_CORE_ERROR); }
        else if ($config->debug >= 1) { error_reporting(E_ERROR | E_CORE_ERROR); }
        else { error_reporting(0); }
        
        // Create the Logger - Production is 5 and lower
        /*
          // EMERG   = 0;  // (TRANSACTION)     Emergency: system is unusable
                System - System messages
          // ALERT   = 1;  // (ADMIN)           Alert: action must be taken immediately
                Administration - Creating groups, creating profiles etc.
          // CRIT    = 2;  // (USER)            Critical: critical conditions
                Personal - Adding contacts and friends
          // ERR     = 3;  // (MAINTENANCE)     Error: error conditions
                Creation - Creating new content
          // WARN    = 4;  // (GENERAL)         Warning: warning conditions
                Interactive - Creating a comment
          // NOTICE  = 5;  // (NOTICE)          Notice: normal but significant condition
                Misc
          //----------------------------------------------------------------------------
          // INFO    = 6;  // (INFO)            Informational: informational messages
          // DEBUG   = 7;  // (DEBUG)           Debug: debug messages
                System debugging messages
        */
        $log = new Zend_Log();
        $log->addWriter(new ViaMe_Log_Writer_Db($db, $config->resources->db->log->table, array_flip($config->resources->db->log->mapping->toArray())));
        $log_filter = new Zend_Log_Filter_Priority((int) $config->loglevel);
        $log->addFilter($log_filter);
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $log->setEventItem('ip_address', $_SERVER['REMOTE_ADDR']);
        }
        Zend_Registry::set('log', $log);
        
        
        // Setup the cache
        if ($config->cache->enable) {
            $cache = new StdClass;
            # APC Backend is single server, while Memcached backend is distributed and shared
            #$cache->core = Zend_Cache::factory('Core', 'File', array('caching' => true, 'cache_id_prefix' => null, 'lifetime' => 3600, 'automatic_serialization' => true, 'automatic_cleaning_factor' => 10, 'ignore_user_abort' => false), array());
            $memcached_options = array(
                'servers' => ($config->debug == 0 ?
                    // Production Memcached
                    array(
                        array(
                            'host' => 'localhost',
                            #'port' => 11211,
                            #'persistent' => true,
                            #'weight' => 1,
                            #'timeout' => 5,
                            #'retry_interval' =>15
                            #'status' => true,
                            #'failure_callback' => ''
                        )
                    )
                    :
                    // Staging Through Development Memcached
                    array(
                        array(
                            'host' => 'localhost',
                            #'port' => 11211,
                            #'persistent' => true,
                            #'weight' => 1,
                            #'timeout' => 5,
                            #'retry_interval' =>15
                            #'status' => true,
                            #'failure_callback' => ''
                        )
                    )
                ),
                #'compression' => true,
                #'compatibility' => true
            );
            $cache->core = Zend_Cache::factory('Core', 'Apc', array('automatic_serialization' => true));
            $cache->memcache = Zend_Cache::factory('Core', 'Memcached', array('automatic_serialization' => true), $memcached_options);
            $cache->output = Zend_Cache::factory('Output', 'Apc');
            
            // Page cache checks in Postdata and Auth plugins
            if ($config->cache->enable_page_cache) {
                $cache->page = Zend_Cache::factory('Page', 'Apc', array(
                    'lifetime' => 900,
                    #'debug_header' => true,
                    'default_options' => array(
                        'cache_with_get_variables' => true,
                        'cache_with_post_variables' => false,
                        'cache_with_session_variables' => true,
                        'cache_with_files_variables' => true,
                        'cache_with_cookie_variables' => true,
                        // setting parameters with setParam will NOT include them into GET or POST
                        'make_id_with_get_variables' => true,
                        'make_id_with_post_variables' => false,
                        'make_id_with_session_variables' => false,
                        'make_id_with_files_variables' => false,
                        'make_id_with_cookie_variables' => false,
                        'cache' => true,
                        #'specific_lifetime' => false,
                        #'tags' => array(),
                        #'priority' => null
                    ),
                    'memorize_headers' => array(
                        'Location',
                        'Content-Type',
                        'Cache-Control',
                        'Expires',
                        'Pragma',
                        'X-Accel-Expires'
                    )
                ), array());
            }
            
            // Zend Cache Hooks
            Zend_Db_Table_Abstract::setDefaultMetadataCache($cache->core);
            Zend_Locale::setCache($cache->core);
            Zend_Translate::setCache($cache->core);
        
            Zend_Registry::set('cache', $cache);
        }
        
        
        // Set the locale compatibility mode to false
        Zend_Locale::$compatibilityMode = false;
        
        
        // Set the viewRenderer view object early so it can be accessed in plugins
        $this->bootstrap('view');
        $view = $this->getResource('view');
        $view->doctype($config->doctype);
        
        
        // Breadcrumbs array
        Zend_Registry::set('breadcrumbs', array());
        
        
        // Register navigation array
        #$this->bootstrap('navigation');
        #$navigation = array(array());
        #Zend_Registry::set('navigation', $navigation);
        #Zend_Registry::set('navigation', Zend_Registry::get('Zend_Navigation'));
        
        
        // Register a VMAH
        $this->bootstrap('layout'); // Bootstrap the layout so it is available to the Helper
        $VMAH = new ViaMe_Controller_Action_Helper_ViaMe();
        $VMAH->init();
        Zend_Registry::set('VMAH', $VMAH);
        
        
        $this->bootstrap('FrontController');
        $frontController = $this->getResource('FrontController');
        
        // Complicated RegEx - See ViaMe_Controller_Router_Route_Regex
        $routeRegExp = '#(?:(?:([^/]*)/s(?:\W))?(?:(com|net|via)/([^/]*)(?:/?))?(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?)))?)?)?\Wp/|(?:([^/]*)/s(?:/|$))?(?:(com|net|via)/([^/]*)(?:/?))?(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?)))?)?)?)(.*)#';
        # 5/5/2010  - RegEx Backup - Second check, force a forward slash or end after slash
        #$routeRegExp = '#(?:(?:([^/]*)/s(?:\W))?(?:(com|net|via)/([^/]*)(?:/?))?(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?)))?)?)?\Wp/|(?:([^/]*)/s(?:/?))?(?:(com|net|via)/([^/]*)(?:/?))?(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?))(?:(?:([^/]+)(?:/?)))?)?)?)(.*)#';
        $route = new ViaMe_Controller_Router_Route_Regex(
            $routeRegExp,
            array(
                'module' => 'default',
                'controller' => 'index',
                'action' => 'index',
                'submodule' => '',
                'subcontroller' => 'index',
                'subaction' => 'index'
            ),
            array(
                1 => 'blah',
                2 => 'route_type',
                3 => 'route_id',
                4 => 'module',
                5 => 'controller',
                6 => 'action',
                7 => 'blah',
                8 => 'route_type',
                9 => 'route_id',
                10 => 'module',
                11 => 'controller',
                12 => 'action',
                13 => 'PARAMS'
            )
        );
        $frontController->getRouter()->addRoute('viame', $route);
        
        #$frontController->setRequest(new ViaMe_Controller_Request_Http);
        #$frontController->setRequest(new Zend_Controller_Request_Apache404);
		 
		/*$cache->core = Zend_Cache::factory('Core', 'Apc', array('automatic_serialization' => true));
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        $cache->memcache = Zend_Cache::factory('Core', 'Memcached', array('automatic_serialization' => true), $memcached_options);
		$cache->clean(Zend_Cache::CLEANING_MODE_ALL);*/
		//$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
    }
}
