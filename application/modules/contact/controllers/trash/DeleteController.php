<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_DeleteController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Delete Contact', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Delete Contact
        if ($this->_getParam('id')) {
            $contact_contacts = new contact_models_contacts();
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->_getParam('id'));
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->target->id);            
            
            if ($this->_getParam('f') == 'h') {
                $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->_getParam('id'));
                $where[] = $contact_contacts->getAdapter()->quoteInto('status = ? AND active = ?', 'f');
                $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->target->id);            
                
                $contact_contacts->update(array('active' => null), $where);
            } else {
                $where[] = $contact_contacts->getAdapter()->quoteInto('status ISNULL OR status <> ?', 'f');        
                
                $contact_contacts->delete($where);
            }
        }
        
        // Force redirect (not autoredirect) as we will more than like be coming from the view controller
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}