<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_MetaInfo extends Zend_Controller_Plugin_Abstract
{
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Set default for googlebot-news
        if ($viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')) {
            // Google's News Bot should only pick up items explicitly set (by removing the following meta) in the default view controller
            $viewRenderer->view->headMeta()->setName('Googlebot-News', 'noindex, noarchive, nofollow');
        }
    }

    
    /*
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>routeShutdown() called</p>\n");
    }

    

    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>dispatchLoopStartup() called</p>\n");
    }

    

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>preDispatch() called</p>\n");
    }
    */
    
    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Layouts plugin is rendered in the postdispatch!
        // Do this only if there is no other actions in the actionstack helper
        
        $fc = Zend_Controller_Front::getInstance();
        if ($request->isDispatched() && (!$fc->getPlugin('Zend_Controller_Plugin_ActionStack') || !count($fc->getPlugin('Zend_Controller_Plugin_ActionStack')->getStack()))) {
            // No further actions in actionstack
            
            // If we have a canonical header and it is not the url we are on, redirect to canonical with 301
            if ($viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')) {
            
                $headMetaContainer = $viewRenderer->view->headMeta()->getContainer();
                $metaRobotsExists = 0;
                if (count($headMetaContainer)) {
                    foreach ($headMetaContainer as $item) {
                        if (isset($item->name) && strtolower($item->name) == 'robots' && $item->content) {
                            $metaRobotsExists = 1;
                            if (strtolower($item->content) == 'all') {
                                $metaRobotsExists = 2;
                            }
                        }
                    }
                }
                
                if (!$metaRobotsExists) {
                    $viewRenderer->view->headMeta()->setName('robots', 'noindex, noarchive, nofollow');
                }
                if ((!$metaRobotsExists || $metaRobotsExists == 2) && (!$request->getParam('vmcd_ncr'))) {
                    // Should we still redirect?  Has to have robots meta set to all or not set for canonical to be eligible
                    $headLinkContainer = $viewRenderer->view->headLink()->getContainer();
                    
                    if (count($headLinkContainer)) {
                        foreach ($headLinkContainer as $item) {
                            if (isset($item->rel) && strtolower($item->rel) == 'canonical') {
                                $canUrl = $item->href;
                                $goUrl = $item->href;
                                if ((strpos($goUrl, '?') === false) && (isset($this->vars->query_string) && $this->vars->query_string)) {
                                    if (isset($this->vars->new_query_string)) {
                                        if ($this->vars->new_query_string) {
                                            $goUrl .= '?' . $this->vars->new_query_string;
                                        }
                                    }
                                    else {
                                        $goUrl .= '?' . $this->vars->query_string;
                                    }
                                }
                                
                                $reqUrl = null;
                                if (preg_match('/^http/i', $canUrl)) {
                                    $reqUrl = ($_SERVER['HTTP_HOST'] ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
                                    
                                    $canUrl = preg_replace('/https?:\/\//i', '', $canUrl);
                                }
                                $reqUrl .= $_SERVER['SCRIPT_URL'];
                                
                                // Check the canonical link against self url
                                if (!preg_match('#^' . preg_replace(array('/\^/', '/\$/', '/\./'), array('\^', '\\\$', '\.'), $canUrl) . '$#', $reqUrl)) {
                                    #Zend_Debug::Dump($goUrl);
                                    
                                    //
                                    $this->_response->setRedirect($goUrl, (isset($this->internal->canonical_redirect_code) && $this->internal->canonical_redirect_code ? $this->internal->canonical_redirect_code : 301))->sendHeaders();
                                    exit;
                                }
                            }
                        }
                    }
                }
            }
        }
        
        
        #Zend_Debug::Dump($headLinkContainer);
        #$viewRenderer->view->headLink()->appendStylesheet($this->vars->static_host . '/css/community/default/print.css');
        #$viewRenderer->view->inlineScript('SCRIPT', "window.print();");
    }
    
    
    /*
    public function dispatchLoopShutdown()
    {
        // Set the translations for the layouts to use here?
        //$this->getResponse()->appendBody("<p>dispatchLoopShutdown() called</p>\n");
    }
    */
}
