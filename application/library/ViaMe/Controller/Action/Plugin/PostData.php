<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_PostData extends Zend_Controller_Plugin_Abstract
{
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        /*
        $this->getResponse()->appendBody("<p>routeStartup() called</p>\n");
        
        REQUEST_URI
        PHP_SELF
        QUERY_STRING
        var->query_string
        
        This is to setup some _SERVER variables and delete some query parameters.  Mainly used for pages that are called within
        popups, iframes, server side includes, etc where the calling page is different
        */
        
        #Zend_Debug::Dump($_SERVER);
        if ($request->getParam('vmpd_self') || $request->getParam('vmpd_rqsv')) {
            ViaMe_Controller_Action::registryLoader($this);
            
            if ($request->getParam('vmpd_self')) {
                #$_SERVER['PHP_SELF'] = $request->getParam('vmpd_self');
                $this->vars->new_php_self = $request->getParam('vmpd_self');
            }
            
            // Remake the QUERY STRING
            parse_str($_SERVER['QUERY_STRING'], $qsa);
            #Zend_Debug::Dump($qsa);
            $rqsv = array();
            if ($request->getParam('vmpd_rqsv')) {
                $rqsv = preg_split("/[\s,:]+/", $request->getParam('vmpd_rqsv'));
            }
            array_push($rqsv, 'vmpd_self', 'vmpd_rqsv');
            $qsa = array_diff_key($qsa, array_fill_keys($rqsv, 1));
            $new_qs = http_build_query($qsa);
            
            #$_SERVER['QUERY_STRING'] = $new_qs;
            #$this->vars->query_string = $new_qs;
            $this->vars->new_query_string = $new_qs;
            #$_SERVER['REQUEST_URI'] = $_SERVER['SCRIPT_NAME'] . ($new_qs ? '?' . $new_qs : '');
            $this->vars->new_request_uri = $_SERVER['SCRIPT_NAME'] . ($new_qs ? '?' . $new_qs : '');
        }
    }
    
    
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        // Cookie Setter - Do Earlier than SSL
        $cookie_life_override = array(
            $this->config->auth->cookie_name->profile_referrer_id => array(
                '31686' => 60*60*24*365 // Luke Hoppel - 1 Year
            ),
            'click_track' => array(
                'L-H-SCNEO_Referral' =>  60*60*24*365 // Luke Hoppel - 1 Year
            )
        );
        if ($request->getParam('vmpd_ckstr') && is_array($request->getParam('vmpd_ckstr'))) {
        	foreach ($request->getParam('vmpd_ckstr') as $name => $val) {
        		if (!$request->getCookie($name)) {
        		    $this->VMAH->setVMCookie($name, $val, (isset($cookie_life_override[$name][$val]) ? time()+$cookie_life_override[$name][$val] : time()+60*60*24*30)); // Default 30 Days
        		}
        		
        		// Track Referrer Profile ID Click For All Clicks
    		    if ($name == $this->config->auth->cookie_name->profile_referrer_id) {
    		        $this->db->insert('member_referrer_clicks_counts', array('profile_id' => $val, 'ip_address' => $_SERVER['REMOTE_ADDR']));
    		    }
        	}
        	
        	if ($request->getParam('vmpd_ckstr_redirect')) {
        	    $vmpd_ckstr_redirect = preg_replace('/^https+:\/\/[^\/]*/', '', $request->getParam('vmpd_ckstr_redirect'));
        	    if (!$vmpd_ckstr_redirect) { $vmpd_ckstr_redirect = '/'; }
        	    if ($request->getParam('vmpd_ckstr_add_param')) {
        	        $vmpd_ckstr_redirect .= (preg_match('/\?/', $vmpd_ckstr_redirect) ? '&' : '?');
        	        $counter = 0;
        	        foreach (preg_split('/[,\s]+/', $request->getParam('vmpd_ckstr_add_param')) as $param) {
        	            if ($request->getParam($param)) {
            	            $vmpd_ckstr_redirect .= ($counter ? '&' : '');
            	            $vmpd_ckstr_redirect .= $param . '=' . urlencode($request->getParam($param));
            	            $counter++;
            	        }
        	        }
        	    }
        	    
        	    $this->_response->setRedirect(
                    #'http://' . $this->vars->host . $vmpd_ckstr_redirect
                    $vmpd_ckstr_redirect
                , 301)->sendHeaders();
                exit;
        	}
        }
        
        // Check SSL and Redirect Out - Do Early
        if (isset($this->vars->ssl) && $this->vars->ssl && !$request->getParam('vmpd_nsslr')) {
            $redirect_out = false; //true;
            $ssl_allowed = array(
                array('member', '*', '*'),
                array('acl', '*', '*')
            );
            
            foreach ($ssl_allowed as $temp) {
                if (
                    (($temp[0] == '*') || ($temp[0] == $request->getModuleName())) &&
                    (($temp[1] == '*') || ($temp[1] == $request->getControllerName())) &&
                    (($temp[2] == '*') || ($temp[2] == $request->getActionName()))
                ) {
                    $redirect_out = false;
                    break;
                }
            }
            
            if ($redirect_out || !$this->config->use_ssl) {
                #Zend_Debug::Dump('http://' . $this->vars->host . $this->vars->request_uri);
                
                $this->_response->setRedirect(
                    'http://' . $this->vars->host . $this->vars->request_uri
                , 301)->sendHeaders();
                exit;
            }
        }
        elseif ($this->config->use_ssl && !$request->getParam('vmpd_nsslr')) {
            /* DO WE WANT TO CREATE A FORCE INTO SSL?? */
            $redirect_in = false;
            $ssl_required = array(
                array('member', 'register', '*'),
                array('acl', 'access', '*')
            );
            
            foreach ($ssl_required as $temp) {
                if (
                    (($temp[0] == '*') || ($temp[0] == $request->getModuleName())) &&
                    (($temp[1] == '*') || ($temp[1] == $request->getControllerName())) &&
                    (($temp[2] == '*') || ($temp[2] == $request->getActionName()))
                ) {
                    $redirect_in = true;
                    break;
                }
            }
            
            if ($redirect_in) {
                #Zend_Debug::Dump('https://' . $this->vars->host . $this->vars->request_uri);
                
                $this->_response->setRedirect(
                    'https://' . $this->vars->host . $this->vars->request_uri
                , 301)->sendHeaders();
                exit;
            }
        }
        
        // Set Redirect to Referring Page if Same Host            
        if (
            !$request->getParam('redirect') &&
            !$request->getParam('vmpd_nar') &&
            $request->getServer('HTTP_REFERER') &&
            ($request->getServer('HTTP_REFERER') != $request->getServer('SCRIPT_URI') . ($request->getServer('QUERY_STRING') ? '?' . $request->getServer('QUERY_STRING') : '')) &&
            preg_match('/^(https*:\/\/)*(' . $request->getServer('HTTP_HOST') . '|' . $request->getServer('SERVER_NAME') . ')/i', $request->getServer('HTTP_REFERER'))
        ) {
            $request->setParam('redirect', $request->getServer('HTTP_REFERER'));
        }
        
        // Fix Up the Post Data
        $PostData = new Zend_Controller_Action_Helper_FlashMessenger('PostData');
        
        if ($request->isPost() && !($request->getParam('vmpd_npr'))) {
            $PostData->addMessage($request->getParams());
            // Use SERVER variable for request URI, otherwise the re-writes are displayed in location
            #Zend_Controller_Front::getInstance()->getResponse()->setRedirect($request->getRequestUri())->sendResponse();
            $this->_response->setRedirect($request->getServer('REQUEST_URI'))->sendHeaders();
            exit;
        }
        elseif ($PostData->hasMessages()) {
            $messages = $PostData->getMessages();
            $request->setParams($messages[0]);
            $_SERVER['REQUEST_METHOD'] = 'POST';
            
            // This is to cache bypasses this redirected request - Turns off page cacheing by having a post variable
            // but does NOT reset the cache headers to turn off nginx or other front cache
            $_POST['POSTED'] = 1;
        }
        elseif ($request->getParam('vmpd_fp')) {
            $_SERVER['REQUEST_METHOD'] = 'POST';
            $_POST = $_GET;
        }
        
        // Encrypted Parameters
        foreach (preg_grep('/^vmep_/', array_keys($request->getParams())) as $temp_param) {
            $matches = array();
            if (preg_match('/^(vmep_)(.*)$/', $temp_param, $matches)) {
                if ($this->VMAH->simpleDecrypt($request->getParam($temp_param))) {
                    $new_value = $this->VMAH->simpleDecrypt($request->getParam($temp_param));
                    $request->setParam($matches[2], $new_value);
                    
                    // Set the GET - setParam does not do this
                    if (isset($_GET[$temp_param])) {
                        $_GET[$matches[2]] = $new_value;
                    }
                }
            }
            // Unset
            $request->setParam($temp_param, null);
              unset($_GET[$temp_param]);
              unset($_POST[$temp_param]);
        }
    }

    /*
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>dispatchLoopStartup() called</p>\n");
    }

    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>preDispatch() called</p>\n");
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>postDispatch() called</p>\n");
    }

    public function dispatchLoopShutdown()
    {
        $this->getResponse()->appendBody("<p>dispatchLoopShutdown() called</p>\n");
    }
    */
}
