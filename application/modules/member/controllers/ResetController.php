<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_ResetController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Reset Password', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/');
        }
        
        // Reset Password
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'reset_password_form',
                'id' => 'reset_password_form',
                'class' => 'form condensed',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.reset_password_form] });'
            ),
            'elements' => array(
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Your registered login email address',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Email(label="email address", message="The {label} was not formatted properly.", groups=[reset_password_form])',
                    'order' => 5
                )),
                'submit' => array('Submit', array(
                    'label' => 'Reset Password',
                    'description' => 'Reset your password',
                    'ignore' => true,
                    'order' => 20
                )),
                'cancel' => array('Submit', array(
                    'label' => 'Cancel',
                    'description' => 'Cancel',
                    'onClick' => "document.getElementById('reset_password_form').vivregval_canceled = true;",
                    'order' => 25
                ))
            )
        ));
        $form->setMethod('post');
        $form->setAction('/member/reset/');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Set Some Validators
        $form->getElement('email')->addValidator('EmailAddress');
        
        if (isset($this->member->email) && !$this->_getParam('email')) {
            $form->getElement('email')->setValue($this->member->email);
        }
        elseif (isset($this->cookie->{$this->config->auth->cookie_name->login}) && !$this->_getParam('email')) {
            $form->getElement('email')->setValue($this->cookie->email);
        }
        
        $form->addDisplayGroup(array('email'), 'email_group', array('legend' => 'Reset Your Password'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // If posted, validate and update db 
        if ($this->getRequest()->isPost()&& $form->isValid($this->_getAllParams())) {
            $values = $form->getValues();

            if ($result = $this->db->fetchRow("SELECT email, first_name || ' ' || COALESCE(middle_name || ' ', '') || last_name AS name, md5(password || id || password_salt) AS key FROM member_members WHERE active AND password <> 'NEED_TO_SETUP_VIAME_ACCOUNT' AND lower(email)=lower(?)", array($values['email']))) {
                // Send email
                self::_sendResetEmail($result->email, $result->name, $result->key);
                
                $this->log->ALERT("Request for password reset ($values[email]).");
                $this->view->headTitle('Reset Email Sent', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain successmessage',
                    'hd' => 'Reset Password',
                    'hd2' => 'Request to reset account password',
                    'bd' => '<p class="success">We are processing your request to reset your password.</p><p>Please check your email address for instructions on how to complete your password reset request.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : '/') . '">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            }
            else {
                $this->log->EMERG("Request for password reset with invalid email ($values[email]).");
                
                $this->view->formErrors = array('That email address could not be found as a valid registered and confirmed member.');
            }
        }
        
        $this->view->form = $form;
    }
    
    public function verifyAction()
    {
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Reset Password', 'PREPEND');
        $this->view->headTitle('Verification', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/');
        }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'reset_password_verify_form',
                'id' => 'reset_password_verify_form',
                'class' => 'form condensed'
            ),
            'elements' => array(
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Your email address',
                    'required' => true,
                    'order' => 5
                )),
                'key' => array('Text', array(
                    'label' => 'Reset Key',
                    'description' => 'The reset key',
                    'required' => true,
                    'order' => 10
                )),
                'new_password' => array('Password', array(
                    'label' => 'New Password',
                    'description' => 'Your new password',
                    'required' => true,
                    'order' => 15
                )),
                'new_password_confirm' => array('Password', array(
                    'label' => 'New Password Confirmation',
                    'description' => 'Your new password confirmation',
                    'required' => true,
                    'order' => 20
                )),
                'submit' => array('Submit', array(
                    'label' => 'Reset Password',
                    'description' => 'Reset your password',
                    'ignore' => true,
                    'order' => 25
                )),
                'cancel' => array('Submit', array(
                    'label' => 'Cancel',
                    'description' => 'Cancel',
                    'order' => 30
                ))
            )
        ));
        
        $form->setMethod('post');
        $form->setAction('/member/reset/verify/');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Set Some Validators
        $form->getElement('email')->addValidator('EmailAddress');
        
        $password_length_validator = new Zend_Validate_StringLength(4, 256);
        $password_confirm_validator = new Zend_Validate_Identical($this->_getParam('new_password'));
          $password_confirm_validator->setMessages(array(
            Zend_Validate_Identical::NOT_SAME => 'New password was not confirmed.', Zend_Validate_Identical::MISSING_TOKEN => 'New password was not confirmed.'
          ));
          
        $form->getElement('new_password')->addValidator($password_length_validator);
        $form->getElement('new_password_confirm')->addValidator($password_length_validator);
        $form->getElement('new_password_confirm')->addValidator($password_confirm_validator);
        
        
        $form->addDisplayGroup(array('email', 'key', 'new_password', 'new_password_confirm'), 'email_group', array('legend' => 'Reset Your Password'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // If posted, validate and update db 
        if ($this->getRequest()->isPost()&& $form->isValid($this->_getAllParams())) {
            $values = $form->getValues();
            
            $member_members = new member_models_members();
            
            try {
                $new_salt = md5(uniqid(rand(), true));
                $result = $member_members->update(
                    array(
                        'password' => md5($values['new_password']),
                        'password_salt' => $new_salt
                    ),
                    array(
                        $member_members->getAdapter()->quoteInto('email = ?', $values['email']), 
                        $member_members->getAdapter()->quoteInto('md5(password || id || password_salt) = ?', $values['key'])
                    )
                );
                
                if (!isset($this->member)) {
                    $this->member = $member_members->fetchRow($member_members->select()->where('email=?', $values['email'])->where('password=?', md5($values['new_password']))->where('password_salt=?', $new_salt));
                }
            } catch (Exception $e) {}
            
            if ($result) {
                $this->_helper->ViaMe->loginMember(md5($values['new_password']), $new_salt, $this->member->id, $this->member->email);
                
                $this->log->ALERT("Password reset successful.");
                $this->view->headTitle('Reset Successful', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain successmessage',
                    'hd' => 'Reset Password',
                    'hd2' => 'Request to reset password',
                    'bd' => '<p class="success">Your password has been successfully reset.</p><p><a href="/">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            } else {
                $this->log->ALERT("Password reset failed!");
                $this->view->formErrors = array('Password reset failed!');
            }
        }
        else {
            $form->populate($this->_getAllParams());
        }
        
        $this->view->form = $form;
    }
    
    private function _sendResetEmail ($email, $name, $key)
    {
        if ($email && $key) {
            $internal = new StdClass;
            $internal->vars = $this->vars;
            $internal->config = $this->config;
            $internal->community = $this->community;
            
            $partial_array = array('email' => $email, 'name' => $name, 'key' => $key, 'internal' => $internal);
            
            $this->_helper->ViaMe->sendEmail($email, null, $this->community->display . ' Password Reset Request', $this->view->partial('reset/emails/reset-html.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
}
