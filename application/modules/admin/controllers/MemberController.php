<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_MemberController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch()
    {
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) { return $this->_denied(); }
        else {
            $this->view->headTitle('Admin', 'PREPEND');
            $this->view->headTitle('Member', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    /*
    public function indexAction()
    {
    
    }
    */
    
    public function lookupbyemailAction()
    {
        $this->view->headTitle('Lookup By Email', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            #$this->_autoredirect('/admin/');
            $this->_redirect('/admin/');
        }
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'member_form',
                'method' => 'post',
                'id' => 'member_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'email_search' => array('Text', array(
                    'label' => 'Email Search String',
                    'description' => 'Enter a part of an email address to lookup in the member table (ILIKE search).',
                    #'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="message", message="The {label} field cannot be empty.", groups=[member_form])',
                    'order' => 10,
                    #'validators' => array(
                    #    array('StringLength', false, array(0, 256))
                    #)
                )),
            )
        ));
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Lookup', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('member_form').vivregval_canceled = true;"));
        
        #$form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        #$form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        #$form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        $form->populate($this->_getAllParams());
        echo $form;
        
        // If posted, validate, display
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            if ($member = $this->db->fetchAll('SELECT *, pp.id AS profile_id FROM profile_profiles pp, member_members mm WHERE pp.member_id=mm.id AND pp.active AND email ILIKE ? ORDER BY pp.id', $this->_getParam('email_search'))) {
                Zend_Debug::Dump($member);
            }
        }
                
        $this->_helper->viewRenderer->setNoRender();
    }
}