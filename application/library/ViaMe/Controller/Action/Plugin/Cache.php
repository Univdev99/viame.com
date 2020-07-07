<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_Cache extends Zend_Controller_Plugin_Abstract
{
    /*
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>routeStartup() called</p>\n");
    }
    
    
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>routeShutdown() called</p>\n");
    }
    */
    
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // No-Cache Param
        if (!$this->config->cache->enable || !(isset($this->cache)) || ($request->getParam('no_cache') && !$request->getParam('enable_backend_cache'))) {
            Zend_Registry::set('cache', null);
        }
        
        if (isset($this->member) ||
            $request->getParam('no_cache') ||
            #($request->getModuleName() == 'member' && (count($_GET) || count($_POST) || $request->has($this->config->auth->cookie_name->login))) ||
            ($request->getModuleName() == 'member') ||
            ($request->getModuleName() == 'acl') ||
            ($request->getModuleName() == 'zfbp') ||
            !$this->config->cache->enable) {
            $this->getResponse()
                ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, post-check=0, pre-check=0', true)
                ->setHeader('Expires', 'Wed, 01 Jan 1997 00:00:00 GMT', true)
                ->setHeader('Pragma', 'no-cache', true)
                ->setHeader('X-Accel-Expires', 0, true);
        }
        else {
            // Have to make sure the locale is part of the hostname so redirect locales to hostname based locales
            //  Only applies to cached pages
            $temp_locale = strtolower($this->locale->toString());
            
            #$this->cache->core->remove('system_languages');
            if ( (!$request->getParam('no_cache') && $this->config->cache->enable && $system_languages = $this->cache->core->load('system_languages')) === false ) {
                $system_languages = $this->db->fetchAll($this->db->select()->from(array('obj' => 'system_languages'))->where("obj.active=?", 't')->order('orderby ASC'));
                if (!$request->getParam('no_cache') && $this->config->cache->enable) {
                    $this->cache->core->save($system_languages, 'system_languages', array(), 3600);
                }
            }
            foreach ($system_languages as $sl) {
                $sl_hash[strtolower($sl->code)] = true;
            }
            /*
            if (
              (isset($this->vars->language) && (preg_match('/^(root|en|en_us)$/i', $this->vars->language))) ||
              (!isset($this->vars->language) || $temp_locale != $this->vars->language) &&
                $temp_locale != 'en_us' &&
                $temp_locale != 'en' &&
                (!isset($this->vars->language) || !is_numeric($this->vars->language))
            ) {
                $this->_response->setRedirect(
                    'http://' .
                    #((isset($this->vars->pre_lang) && $this->vars->pre_lang) ? $this->vars->pre_lang . '.' : '') .
                    (!preg_match('/^(root|en|en_us)$/i', $temp_locale)  ? $temp_locale . '.' : '') .
                    $this->vars->host_name . '.' . $this->vars->domain_name . $this->vars->request_uri
                )->sendHeaders();
                exit;
            }
            */
            
            if (
              // Language is set and it is the default system language, redirect with no locale
              (isset($this->vars->language) && (preg_match('/^(root|en|en_us)$/i', $this->vars->language))) ||
              
              // Language is not set but at least language portion of locale is active and it is not en, redirect to locale based url
              ( !isset($this->vars->language) && (!preg_match("/^en/i", $temp_locale)) && 
                (array_key_exists($temp_locale, $sl_hash) || array_key_exists(preg_replace("/\_.*/", '', $temp_locale), $sl_hash)) ) ||
              
              // Language is set and it is not yet active, redirect to default with no locale
              (isset($this->vars->language) && !is_numeric($this->vars->language) && 
                (!(array_key_exists(strtolower($this->vars->language), $sl_hash) || ((!preg_match("/^en/i", $temp_locale)) && array_key_exists(preg_replace("/\_.*/", '', $temp_locale), $sl_hash)))) )
              
            ) {
                $this->_response->setRedirect(
                    'http://' .
                    #((isset($this->vars->pre_lang) && $this->vars->pre_lang) ? $this->vars->pre_lang . '.' : '') .
                    ((!isset($this->vars->language) && ($temp_locale != 'en_us') && ($temp_locale != 'en') && 
                      (array_key_exists($temp_locale, $sl_hash) || ((!preg_match("/^en/i", $temp_locale)) && array_key_exists(preg_replace("/\_.*/", '', $temp_locale), $sl_hash))) ) ? $temp_locale . '.' : '') .
                    $this->vars->host_name . '.' . $this->vars->domain_name . $this->vars->request_uri
                , 301)->sendHeaders();
                exit;
            }
            elseif (!isset($this->vars->language) && $temp_locale != 'en' && $temp_locale != 'en_us') {
            #elseif (!isset($this->vars->language) && (!preg_match("/^en/i", $temp_locale))) {
                $this->locale = new Zend_Locale('en_US');
                Zend_Registry::set('locale', $this->locale);
                Zend_Registry::set('Zend_Locale', $this->locale);
            }
            
            $this->getResponse()
                ->setHeader('Cache-Control', 'public, max-age=' . $this->config->cache->lifetime, true)
                ->setHeader('Expires', gmdate('D, d M Y H:i:s e', time() + $this->config->cache->lifetime), true)
                ->setHeader('Pragma', 'cache', true)
                ->setHeader('X-Accel-Expires', $this->config->cache->lifetime, true);
            
            // Page Caching
            if ($this->config->cache->enable_page_cache && isset($this->cache) && isset($this->cache->page)) {
                // CACHE Entire Page - Not Posted or redirect posted pages (see postdata plugin)
                $this->cookie = null;
                #$this->internal->cookie = null;
                
                $_GET['cache_host'] = $this->vars->host;
                $_GET['cache_locale'] = $this->locale->toString();
                
                $this->cache->page->start();
            }
        }
    }

    /*
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>preDispatch() called</p>\n");
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>postDispatch() called</p>\n");
    }
    */
    
    public function dispatchLoopShutdown()
    {
        #$this->getResponse()->appendBody("<p>dispatchLoopShutdown() called</p>\n");
        
        // For error pages, delete all cache control headers and let it be handled on the frontent
        if (!isset($this->member) && $this->getRequest()->getModuleName() == 'default' && $this->getRequest()->getControllerName() == 'error' && $this->config->cache->enable) {
            $this->getResponse()
                ->clearHeader('Cache-Control')
                ->clearHeader('Expires')
                ->clearHeader('Pragma')
                ->clearHeader('X-Accel-Expires');
             
            if ($this->config->cache->enable_page_cache && isset($this->cache) && isset($this->cache->page)) {
                $this->cache->page->cancel();
            }
        }
    }
    
}
