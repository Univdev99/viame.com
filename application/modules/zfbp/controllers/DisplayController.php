<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 *
 * Display Controller - Display A Specific View
 */

class Zfbp_DisplayController extends ViaMe_Controller_Action
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
        
        // Load up the registry variables - Only need if NOT going through preDispatch
        #$this->registryLoader($this);
        
        $this->_helper->viewRenderer->setNoRender();
        
        if ($this->_getParam('member_defined')) {
            $this->_memberDefined = true;
        }
        
        #if ($this->_getParam('disable_layout')) {
        #    $this->_helper->layout->disableLayout();
        #    $this->getResponse()->clearBody();
        #    $this->getResponse()->clearHeaders();
        #}
    }
    
    #public function preDispatch() { }
    
    public function indexAction()
    {
        if ($this->_getParam('id') == 'scn_member_verify_success') {
            $this->view->member_profile_id = $this->member->profile->id;
            return $this->renderScript('../../../../modules/member/views/scripts/register/verifysuccess/smallcapnetwork.phtml');
        }
        else {
            return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
        }
        
        
        /*
        Zend_Debug::Dump(APPLICATION_PATH);
        Zend_Debug::Dump($this->vars);
        */
        
        
        /*
        if ($this->_getParam('debug')) {
            // Send an email
            $this->_helper->ViaMe->sendEmail('akang@levelogic.com', null, 'XML Debug', '<pre>'.Zend_Debug::Dump($this->_getAllParams(), 'Params', false).'</pre>', null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
        
        $xml = null;
        $xml_array = array(
            'default_message' => '<Response><Message src="{To}" dst="{From}">This is an automated reply.  For assistance, please contact our customer service department either by phone or email.  Thank you.</Message><Message src="{To}" dst="16192465264">Incoming SMS From {From}: {Text}</Message></Response>'
        );
        if ($this->_getParam('xmlid') && isset($xml_array[$this->_getParam('xmlid')])) { $xml = $xml_array[$this->_getParam('xmlid')]; }
        else { $xml = $this->_getParam('xml'); }
        
        if ($xml) {
            $xml = preg_replace_callback('/\{.*?\}/', function($match) { $match = preg_replace('/\{(.*?)\}/', '$1', $match); return $this->_getParam($match[0]); }, $xml);
            
            $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo $xml;
        }
        else {
            return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
        }
        */
    }
}