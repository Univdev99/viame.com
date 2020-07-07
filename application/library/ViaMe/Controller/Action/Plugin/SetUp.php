<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_SetUp extends Zend_Controller_Plugin_Abstract
{
    
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        // Bad Bot Honey Pot - Sync Names, Codes and Timeouts with /modules/zfbp/controllers/BbhpController.php
        ViaMe_Controller_Action::registryLoader($this);
        
        $lifetime = 3600 * 24 * 30; // 30 Days
        $the_time = time();
        $resave = false;
        
        if (isset($this->cache) && ($bbhp = $this->cache->memcache->load('bad_bot_honey_pot'))) {
            # Reset Array
            #$this->cache->memcache->save(array(), 'bad_bot_honey_pot', array(), 1);
        
            # Unset My IP
            #unset($bbhp['72.199.156.14']);
            #$resave = true;
            
            // Clean Out Older Entries
            foreach ($bbhp as $key => $val) {
                if ($the_time > ($val + $lifetime)) {
                    unset($bbhp[$key]);
                    $resave = true;
                }
            }
            
            if ($resave) { $this->cache->memcache->save($bbhp, null, array(), $lifetime); }
            
            if (array_key_exists($_SERVER['REMOTE_ADDR'], $bbhp)) {
                $this->getResponse()
                    ->setHeader('X-Accel-Redirect', '/nginx-internal/403/')
                    ->sendHeaders();
                exit;
            }
        }
    }
    
    
    
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Register a new locale object
        Zend_Locale::setDefault('en_US');
        $locale = new Zend_Locale(isset($this->vars->language) ? $this->vars->language : null);
        Zend_Registry::set('locale', $locale);
        Zend_Registry::set('Zend_Locale', $locale);
        
        // OBJECT GONE
        if (preg_match('#^/(scb|search|fcr|about_us|archive\/listserv)/.*$#', $_SERVER['SCRIPT_URL']) ||
            preg_match('#^.*?/af/archives?/.*$#', $_SERVER['SCRIPT_URL'])) {
            $request->setModuleName('default')->setActionName('error')->setControllerName('error')->setParam('errorcode', 410);
        }
    }
    
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Load Up the System Modules
        # CACHE - Max
        foreach ($this->db->fetchAll("SELECT *, array_to_string(parameters, '" . $this->config->delimiter . "') as parameters_delimited FROM module_modules WHERE active='t'") as $temp) {
            $module_modules[$temp->name] = $temp;
            $module_modules[$temp->id] = $temp;
        }
        Zend_Registry::set('module_modules', $module_modules);
        
        // Load Up the System Widgets
        # CACHE - Max
        foreach ($this->db->fetchAll("SELECT *, array_to_string(parameters, '" . $this->config->delimiter . "') as parameters_delimited FROM widget_widgets WHERE active='t'") as $temp) {
            $widget_widgets[$temp->name] = $temp;
            $widget_widgets[$temp->id] = $temp;
        }
        Zend_Registry::set('widget_widgets', $widget_widgets);
        
        // Save the parameters so they can be retrieved in later helpers
        Zend_Registry::set('params', (object) $request->getParams());
        Zend_Registry::set('cookie', (object) $request->getCookie());
        
        if ($request->getParam('to_print')) {
            // META - Defaults
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
                $viewRenderer->view->headLink()->appendStylesheet($this->vars->static_host . '/css/community/default/print.css');
                $viewRenderer->view->inlineScript('SCRIPT', "window.print();");
        }
        elseif ($request->getParam('to_email')) {
            $request->setModuleName('system')
                ->setControllerName('email')
                ->setActionName('index')
                ->setDispatched(false);
        }
    }
    
    /*
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>preDispatch() called</p>\n");
    }
    */
    
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Layouts
        if ($request->getParam('layout')) {
            // Change Sub Layout
            $this->VMAH->setLayout($request->getParam('layout'), $this->community);
        }
        if ($request->getParam('sublayout')) {
            // Change Sub Layout
            $this->VMAH->setSubLayout($request->getParam('sublayout'), $this->community);
        }
        if (($request->getParam('no_layout') && !$request->getParam('to_print')) || !$this->config->layouts) {
            $layout = Zend_Layout::getMvcInstance();
            $layout->disableLayout();
            
            $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            $this->getResponse()->appendBody($viewRenderer->view->inlineScript());
        }
        
        /*
        if ($request->isDispatched()) {
            if (!isset($this->toprintflag)) { $this->toprintflag = false; }
            if ($request->getParam('to_print')) {
                // META - Defaults
                $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
                    $viewRenderer->view->headLink()->appendStylesheet($this->vars->static_host . '/css/community/default/print.css');
                    if (!$this->toprintflag) {
                        $viewRenderer->view->inlineScript('SCRIPT', "window.print();");
                        $this->toprintflag = true;
                    }
            }
        }
        */
    }
    
    
    /*
    public function dispatchLoopShutdown()
    {
        // Set the translations for the layouts to use here?
        //$this->getResponse()->appendBody("<p>dispatchLoopShutdown() called</p>\n");
    }
    */
}
