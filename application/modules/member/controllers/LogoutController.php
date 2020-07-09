<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_LogoutController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->login);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->password);
    }
    
    
    public function minimumAction()
    {
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->password);
    }
    
    
    public function fullAction()
    {
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->login);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->password);
    }
    
    
    public function completeAction()
    {
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->login);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->password);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->community_referrer_id);
        $this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile_referrer_id);
        
        Zend_Session::destroy();
    }
    
    
    public function postDispatch() {
        if (isset($this->member)) {
            $this->log->ALERT("Successful logout.");
            unset($this->member);
            Zend_Registry::set('member', null);
        }
        
        // Set Just Logged Out so that we don't get another sign in form
        if ($this->_getParam('redirect') && !$this->_getParam('vmpd_jlo') && !preg_match('/vmpd_jlo=1/', $this->_getParam('redirect'))) {
            $this->_setParam('redirect', $this->_getParam('redirect') . (strpos($this->_getParam('redirect'), '?') === false ? '?' : '&') . 'vmpd_jlo=1');
        }
        
        if ($this->_getParam('vmpd_far')) {
            $this->_autoredirect('/');
        }
        elseif ($this->_getParam('vmpd_dar')) {
            $this->_redirect('https://' . (isset($this->vars->language) ? $this->vars->language . '.' : '') . 'www.' . $this->config->default_domain);
        }
        
        // Didn't redirect; Display View
        $redirect = ($this->_getParam('redirect') ? $this->_getParam('redirect') : '/');
        
        $this->view->headMeta()->appendHttpEquiv('refresh', '10; URL=' . $redirect);
        
        echo $this->view->CM(array(
            'class' => 'cm decorated plain successmessage',
            'hd' => 'Sign Out',
            'hd2' => 'Successfully signed out',
            'bd' => '<p class="success">You have been signed out!</p><p><a href="' . $redirect . '">Continue &raquo;</a></p>'
        ));
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}
