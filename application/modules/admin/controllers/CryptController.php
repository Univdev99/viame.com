<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_CryptController extends ViaMe_Controller_Action
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
            $this->view->headTitle('Quotes', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    public function indexAction()
    {
        $this->view->headTitle('Crypt', 'PREPEND');
        
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
                'name' => 'crypt_form',
                'method' => 'post',
                'id' => 'crypt_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.crypt_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'des_act' => array('Select', array(
                    'label' => 'Action',
                    'description' => 'Encrypt or Decrypt.',
                    'required' => true,
                    'multiOptions' => array(
                        '0' => 'Encrypt',
                        '1' => 'Decrypt'
                    ),
                    'value' => 0,
                    'class' => 'regula-validation',
                    'data-constraints' => '@NotBlank(label="action", message="The {label} field cannot be empty.", groups=[crypt_form])',
                    'order' => 5
                )),
                'message' => array('Text', array(
                    'label' => 'Message',
                    'description' => 'Message to encrypt / decrypt.',
                    #'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="message", message="The {label} field cannot be empty.", groups=[crypt_form])',
                    'order' => 10,
                    #'validators' => array(
                    #    array('StringLength', false, array(0, 256))
                    #)
                )),
                'urlencode' => array('Checkbox', array(
                    'label' => 'Use UrlEncode /UrlDecode',
                    'value' => true,
                    'order' => 15
                )),
            )
        ));
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Update', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('crypt_form').vivregval_canceled = true;"));
        
        #$form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        #$form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        #$form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            if ($this->_getParam('des_act')) {
                // Decrypt
                echo '<p><strong>Decrypting';
                if ($this->_getParam('urlencode')) { echo ' (Using UrlDecode)'; }
                echo ' : </strong></p><blockquote><p>Original : ';
                echo $this->_getParam('message');
                echo '</p><p>Decoded : ';
                if (!$this->_getParam('urlencode')) { echo $this->VMAH->simpleDecrypt($this->_getParam('message')); }
                else { echo $this->VMAH->simpleDecrypt(urldecode($this->_getParam('message'))); }
            }
            else {
                // Encrypt
                echo '<p><strong>Encrypting';
                if ($this->_getParam('urlencode')) { echo ' (Using UrlEncode)'; }
                echo ' : </strong></p><blockquote><p>Original : ';
                echo $this->_getParam('message');
                echo '</p><p>Encoded : ';
                if (!$this->_getParam('urlencode')) { echo $this->VMAH->simpleEncrypt($this->_getParam('message')); }
                else { echo urlencode($this->VMAH->simpleEncrypt($this->_getParam('message'))); }
            }
            echo '</p></blockquote><hr /><br /><br />';
        }
        else {
            $form->populate($this->_getAllParams());
        }
        
        echo $form;
        $this->_helper->viewRenderer->setNoRender();
    }
}