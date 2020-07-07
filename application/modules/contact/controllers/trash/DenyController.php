<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_DenyController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction() {
        $this->view->headTitle('Manage Your Contacts', 'PREPEND');
        $this->view->headTitle('Deny Contact Request', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Deny Contact Request
        if ($this->_getParam('id')) {
            $contact_contacts = new contact_models_contacts();
            
            $data = array(
                'active' => 'f',
                'status' => ($this->_getParam('f') ? 'f' : 't'),
                'message' => ($this->_getParam('m', null) ? $this->_getParam('m') : null)
            );
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->target->id);
            $where[] = $contact_contacts->getAdapter()->quoteInto("status ISNULL");
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->_getParam('id'));
            
            $contact_contacts->update($data, $where);
        }
        
        // Force redirect (not autoredirect) as we will more than like be coming from the view controller
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}