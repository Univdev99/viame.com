<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_JoinController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        if (($this->target->type == 'NET') && isset($this->network) && $this->network->open && !(isset($this->network->nm_active) && $this->network->nm_active)) {
            $network_members = new network_models_members();
            
            $network_members->insert(array(
                'network_id' => $this->target->id,
                'member_profile_id' => $this->member->profile->id,
                'status' => 't')
            );
        }
        
        $this->_autoredirect($this->target->pre . '/');
    }
    
    
    public function passwordAction()
    {
        if (($this->target->type == 'NET') && isset($this->network) && $this->network->password && !(isset($this->internal->network->nm_active) && $this->internal->network->nm_active)) {
            /* Moved this to the beginning to bypass loading of the form and other ancillary
               functions.  Also, didn't use cancel->isChecked(), as that requires form load.
             */
            if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
                $this->_autoredirect($this->target->pre . '/');
            }
            
            // Load the Form
            $form_config = array(
                'attribs' => array(
                    'name' => 'network_password_form',
                    'id' => 'network_password_form',
                    'class' => 'form'
                ),
                'elements' => array(
                    'network_password' => array('Password', array(
                        'label' => 'Password',
                        'description' => 'Secret Password',
                        'required' => true,
                        'order' => 5
                    ))
                )
            );
            $form = new Zend_Form();
            $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
            $form->setOptions($form_config);
            
            $form->setMethod('post');
            
            $form->addElement('Submit', 'submit', array('label' => 'Submit', 'ignore' => true, 'order' => 999));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
            
            // Redirects
            $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect'))); 
            
            // If posted, validate, write to db
            if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
                $query = "INSERT INTO network_members (network_id, member_profile_id, status) SELECT id, ?, 't' FROM network_networks WHERE id=? AND password=?";
                
                $this->db->query($query, array($this->member->profile->id, $this->network->id, $this->_getParam('network_password')));
                
                $this->_autoredirect($this->target->pre . '/');
            }
            
            $this->view->form = $form;
            
            $this->renderScript('form.phtml');
        }
        else {
            $this->_autoredirect($this->target->pre . '/');
        }
    }
    
    
    public function requestAction()
    {
        if (($this->target->type == 'NET') && isset($this->network) && $this->network->allow_requests && !(isset($this->internal->network->nm_active) && $this->internal->network->nm_active)) {
            /* Moved this to the beginning to bypass loading of the form and other ancillary
               functions.  Also, didn't use cancel->isChecked(), as that requires form load.
             */
            if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
                $this->_autoredirect($this->target->pre . '/');
            }
            
            // Load the Form
            $form_config = array(
                'attribs' => array(
                    'name' => 'network_request_form',
                    'id' => 'network_request_form',
                    'class' => 'form'
                ),
                'elements' => array(
                    'request_message' => array('textarea', array(
                        'label' => 'Request Message',
                        'description' => 'Request Message',
                        'order' => 5
                    ))
                )
            );
            $form = new Zend_Form();
            $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
            $form->setOptions($form_config);
            
            $form->setMethod('post');
            
            $form->addElement('Submit', 'submit', array('label' => 'Submit', 'ignore' => true, 'order' => 999));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
            
            // Redirects
            $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect'))); 
            
            // If posted, validate, write to db
            if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
                $query = "INSERT INTO network_members (network_id, member_profile_id, request_message) SELECT id, ?, ? FROM network_networks WHERE id=? AND allow_requests='t'";
                
                $this->db->query($query, array($this->member->profile->id, $this->_getParam('request_message'), $this->network->id));
                
                $this->_autoredirect($this->target->pre . '/');
            }
            
            $this->view->form = $form;
            
            $this->renderScript('form.phtml');
        }
        else {
            $this->_autoredirect($this->target->pre . '/');
        }
    }
    
    
    public function requestsAction()
    {
        if (($this->target->type == 'NET') && isset($this->network) && (isset($this->internal->network->nm_active) && $this->internal->network->nm_active)) {
            $network_members = new network_models_members();
            
            $network_members->update(array('status' => $this->_getParam('status')), $network_members->getAdapter()->quoteInto('member_profile_id=?', $this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/');
    }
}