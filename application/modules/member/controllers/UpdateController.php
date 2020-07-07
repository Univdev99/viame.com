<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_UpdateController extends ViaMe_Controller_Action
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
            $this->target->id = $this->via->member_id;
        }
        elseif (isset($this->member)) {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
        
        $this->view->headTitle('Member', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
    }
    
    
    public function indexAction()
    {
        // Update Member
        // Menu Displayed In View Script
        $this->view->headTitle('Account Information', 'PREPEND');
    }
    
    public function accountAction()
    {
        $this->view->headTitle('Update Settings', 'PREPEND');
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/member/update/');
        }
        
        // Load the Form
        require_once dirname(__FILE__) . '/includes/members_form.php';
        
        $form->setAttrib('class', 'form');
        #$form->removeAttrib('data-constraints');
        
        $form->setMethod('post');
        #$form->setAction($this->target->pre . '/member/update/account/');
        
        // Remove Some Unneeded Elements
        $form->removeElement('password');
        $form->removeElement('password_confirm');
        $form->removeElement('username');
        
        $form->removeElement('email');
        #$form->getElement('email')->setRequired(false)->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN);
        #unset($form->getElement('email')->class);
        
        $form->addElement('Submit', 'update', array('label' => 'Update', 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('member_form').vivregval_canceled = true;"));
        
        $form->addElement('Hidden', 'id', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('id', 'vmpd_nar', 'redirect'), 'hidden');
        
        $form->addDisplayGroup(array('first_name', 'middle_name', 'last_name', 'phone', 'gender', 'dob', 'dob_month', 'dob_day', 'dob_year', 'postal_code'), 'personal', array('legend' => 'Personal Information'));
        #$form->addDisplayGroup(array('email'), 'login', array('legend' => 'Login Information'));
        $form->addDisplayGroup(array('timezone', 'country', 'currency', 'language'), 'locale', array('legend' => 'Locale Settings'));
        
        $form->addDisplayGroup(array('cancel', 'update'), 'buttons');
        
        // If posted, validate and update db 
        if (($this->getRequest()->isPost()) && ($form->isValid($this->_getAllParams()))) {
            $fields = array('first_name', 'middle_name', 'last_name', 'phone', 'gender', 'postal_code', 'timezone', 'country', 'currency', 'language');
            $values = $form->getValues();
            foreach ($fields as $field) {
                if (isset($values[$field]) && $values[$field] !== '') {
                    $data[$field] = $values[$field];
                }
                else {
                    $data[$field] = null;
                }
            }
            
            // Activation Field
            if ($this->_getParam('dob_month') && $this->_getParam('dob_day') && $this->_getParam('dob_year')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('dob_year') . '-' . $this->_getParam('dob_month') . '-' . $this->_getParam('dob_day'), 'YYYY-MM-DD');
                $data['dob'] = $date->get(Zend_Date::ISO_8601);
            }
            elseif ($this->_getParam('dob')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('dob'));
                $data['dob'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['dob'] = null;
            }
            
            #$this->db->beginTransaction();
            try {
                $member_members = new member_models_members();
                $member_members->update($data, array(
                    #$member_members->getAdapter()->quoteInto('member_id = ?', $this->target->id),
                    $member_members->getAdapter()->quoteInto('id = ?', $this->_getParam('id'))
                ));
                #$this->db->commit();
                
                // Successful Update
                $this->log->ALERT("Member information successfully updated.");
                $this->view->headTitle('Update Success', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain successmessage',
                    'hd' => 'Update Successful',
                    'hd2' => 'Thank you for updating your member settings',
                    'bd' => '<p class="success">You have successfully updated your member settings.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            } catch (Exception $e) {
                #$this->db->rollBack();
                
                // Update Failure
                $this->log->ALERT("Member information update failed!");
                $this->view->headTitle('Update Failed', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Update Failed',
                    'hd2' => 'Your account was not updated',
                    'bd' => '<p class="error">An error has occurred. Your update did not succeed.</p><p>An unexpected error has occurred and has caused your update to fail. If it possible that it was a data input error, or an invalid value was inputted.  If you think this was the case, you can hit the <a href="javascript:history.back();">back button</a> on your browser and try your update again.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            }
            
        } elseif (!$this->getRequest()->isPost()) {
            $member = $this->db->fetchRow('SELECT * FROM member_members WHERE id=?', $this->target->id);
            $form->populate((array) $member);
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($member->dob)) {
                $date->set($member->dob, Zend_Date::ISO_8601);
                $form->getElement('dob')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'))));
                
                // New format
                $form->getElement('dob')->setValue(null);
                $form->getElement('dob_month')->setValue($date->toString('M'));
                $form->getElement('dob_day')->setValue($date->toString('d'));
                $form->getElement('dob_year')->setValue($date->toString('YYYY'));
            }
        }
        
        $this->view->form = $form;
    }
    
    public function passwordAction()
    {
        $this->view->headTitle('Change Password', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/member/update/');
        }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'password_change_form',
                'id' => 'password_change_form',
                'class' => 'form condensed regula-validation',
                'data-constraints' => '@PasswordsMatch(field1="new_password", field2="new_password_confirm", message="Your passwords do not match.", groups=[password_change_form])',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.password_change_form] });'
            ),
            'elements' => array(
                'old_password' => array('Password', array(
                    'label' => 'Old Password',
                    'description' => 'Your old password',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="old password", message="The {label} field cannot be empty.", groups=[password_change_form])',
                    'order' => 5
                )),
                'new_password' => array('Password', array(
                    'label' => 'New Password',
                    'description' => 'Your new password',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="new password", message="The {label} field cannot be empty.", groups=[password_change_form])',
                    'order' => 10
                )),
                'new_password_confirm' => array('Password', array(
                    'label' => 'New Password Confirmation',
                    'description' => 'Your new password confirmation',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="new password confirmation", message="The {label} field cannot be empty.", groups=[password_change_form])',
                    'order' => 15
                )),
                'submit' => array('Submit', array(
                    'label' => 'Change Password',
                    'description' => 'Change password',
                    'ignore' => true,
                    'order' => 20
                )),
                'cancel' => array('Submit', array(
                    'label' => 'Cancel',
                    'description' => 'Cancel',
                    'onClick' => "document.getElementById('password_change_form').vivregval_canceled = true;",
                    'order' => 25
                ))
            )
        ));
        
        $form->setMethod('post');
        #$form->setAction('/member/update/password/');
        
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Set Some Validators
        $password_length_validator = new Zend_Validate_StringLength(4, 256);
        $password_confirm_validator = new Zend_Validate_Identical($this->_getParam('new_password'));
          $password_confirm_validator->setMessages(array(
            Zend_Validate_Identical::NOT_SAME => 'New password was not confirmed.', Zend_Validate_Identical::MISSING_TOKEN => 'New password was not confirmed.'
          ));
          
        $form->getElement('new_password')->addValidator($password_length_validator);
        $form->getElement('new_password_confirm')->addValidator($password_length_validator);
        $form->getElement('new_password_confirm')->addValidator($password_confirm_validator);
        
        $form->addDisplayGroup(array('old_password', 'new_password', 'new_password_confirm'), 'password', array('legend' => 'Passwords'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // If posted, validate and update db 
        if ($this->getRequest()->isPost() && ($form->isValid($this->_getAllParams()))) {
            $values = $form->getValues();
            
            $member_members = new member_models_members();
            
            try {
                $new_salt = md5(uniqid(rand(), true));
                $result = $member_members->update(
                    array('password' => md5($values['new_password']), 'password_salt' => $new_salt),
                    array($member_members->getAdapter()->quoteInto('id = ?', $this->target->id, 'INTEGER'), $member_members->getAdapter()->quoteInto('password = ?', md5($values['old_password'])))
                );
            } catch (Exception $e) {}
            
            if ($result) {
                // Successful Update
                $this->_helper->ViaMe->loginMember(md5($values['new_password']), $new_salt);
                $this->log->ALERT("Password successfully changed.");
                $this->view->headTitle('Success', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain successmessage',
                    'hd' => 'Password Change',
                    'hd2' => 'Your password has been successfully changed',
                    'bd' => '<p class="success">You have successfully updated your password.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            } else {
                $this->log->ALERT("Password change failed!");
                $this->view->headTitle('Failed', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Password Change',
                    'hd2' => 'Your password has not been changed',
                    'bd' => '<p class="error">An error has occurred. Your password change request did not succeed.</p><p>Your request to change your password did not succeed.  The most likely reason is that you did not enter your original password correctly.  If you think this was the case, you can hit the <a href="javascript:history.back();">back button</a> on your browser and try your update again.</p><p>If you have forgotten your password, you can optionally <a href="/member/reset/">reset it</>.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            }
        }
        
        $this->view->form = $form;
    }
    
    public function emailAction()
    {
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/member/update/');
        }
        
        $this->view->headTitle('Change Email', 'PREPEND');
        
        if (!isset($this->view->formError)) { $this->view->formErrors = array(); }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'change_email_form',
                'id' => 'change_email_form',
                'class' => 'form condensed',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.change_email_form] });'
            ),
            'elements' => array(
                'email' => array('Text', array(
                    'label' => 'Current Email Address',
                    'description' => 'Your current email address',
                    'order' => 5
                )),
                'new_email' => array('Text', array(
                    'label' => 'New Email Address',
                    'description' => 'Your new email address',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Email(label="new email address", message="The {label} was not formatted properly.", groups=[change_email_form])',
                    'order' => 15
                )),
                'submit' => array('Submit', array(
                    'label' => 'Change Email Address',
                    'description' => 'Change email address',
                    'ignore' => true,
                    'order' => 20
                )),
                'cancel' => array('Submit', array(
                    'label' => 'Cancel',
                    'description' => 'Cancel',
                    'onClick' => "document.getElementById('change_email_form').vivregval_canceled = true;",
                    'order' => 25
                ))
            )
        ));
        
        $form->setMethod('post');
        #$form->setAction($this->target->pre . '/member/update/email/');
        
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        $form->getElement('email')->setValue($this->member->email)->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN);
        
        
        // Set Some Validators
        $form->getElement('new_email')->addValidator('EmailAddress');
        
        $form->addDisplayGroup(array('email', 'new_email'), 'email_group', array('legend' => 'Email Addresses'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // Do LIVE email check
        if ($this->_getParam('new_email') && !$this->_getParam('key')) {
            $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
            #$SMTP_Validator->debug = true;
            $results = $SMTP_Validator->validate(array($this->_getParam('new_email')), $this->community->email);
            
            if (isset($results[$this->_getParam('new_email')]) && ($results[$this->_getParam('new_email')][0] === false)) {
                // Came back false
                if (isset($results[$this->_getParam('new_email')][1]) && $results[$this->_getParam('new_email')][1]) {
                    $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('new_email') . ' (' . $results[$this->_getParam('new_email')][1] . ') - Please correct or select an alternate email address.';
                }
                else {
                    $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('new_email');
                }
                $this->_setParam('new_email', '');
            }
        }
        
        // If posted, validate and update db 
        if (($this->getRequest()->isPost() || $this->_getParam('vmpd_bpip'))&& ($form->isValid($this->_getAllParams()))) {
            // We use _getAllParams so we can include key
            $values = $this->_getAllParams();

            if (isset($values['key'])) {
                try {
                    $member_members = new member_models_members();
                    
                    $where = 'md5(md5(email || id) || md5('.$this->db->quote($values['new_email']).' || creation))='.$this->db->quote($values['key']).' AND id='.$this->db->quote($this->target->id, 'INTEGER');
                    
                    // Fetch Old Email and Update New
                    $info_select = $member_members->select()
                        ->setIntegrityCheck(false)
                        ->from(array('mm' => 'member_members'), array('email'))
                        ->joinLeft(array('sc' => 'system_communities'), 'mm.community_id=sc.id', array('mailchimp_list_id'))
                        ->where(new Zend_Db_Expr('mm.id=(SELECT id FROM member_members WHERE ' . $where . ')'))
                    ;
                        
                    if ($information = $this->db->fetchRow($info_select)) {
                        $result = $member_members->update(array('email' => $values['new_email']), array($where));
                    }
                } catch (Exception $e) {}
                
                if (isset($result) && $result) {
                    if ($this->target->type != 'VIA') {
                        $this->_helper->ViaMe->loginMember(null, null, null, $values['new_email']);
                    }
                    
                    // Change Email in Email List if there is one
                    if (isset($information->mailchimp_list_id) && $information->mailchimp_list_id) {
                        try {
                            require_once $this->vars->APP_PATH . "/library/Other/MailChimp.php";
                            $mc = new MailChimp('d020fc990cfdf20759aba4e1729b80fc-us16'); // SCN MailChimp Account
                            
                            $result = $mc->patch('lists/' . $information->mailchimp_list_id . '/members/' . md5(strtolower($information->email)), [
                                'email_address' => $values['new_email'],
                                'status' => 'subscribed'
                            ]);
                        } catch (Exception $e) { }
                    }
                    
                    $this->log->ALERT("Email successfully changed.");
                    $this->view->headTitle('Success', 'PREPEND');
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain successmessage',
                        'hd' => 'Email Change',
                        'hd2' => 'Your email has been successfully changed',
                        'bd' => '<p class="success">You have successfully updated your email.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                    ));
                    return $this->_helper->viewRenderer->setNoRender();
                }
                else {
                    $form->getElement('email')->setValue($this->member->email);
                    $form->addElement('Text', 'key', array('label' => 'Verification Key', 'order' => 18));
                    $form->getElement('key')->setValue($values['key']);
                    $form->getElement('new_email')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN);
                    $form->clearDisplayGroups();
                    $form->addDisplayGroup(array('email', 'new_email', 'key'), 'email_group', array('legend' => 'Change Your Email Address'));
                    $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
                    $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
                    $form->getElement('submit')->setLabel('Verify Key to Change Email');
                    
                    $this->view->formErrors[] = 'Key verification failed.';
                }
            }
            else {
                if ($this->db->fetchOne('SELECT 1 FROM member_members WHERE lower(email)=lower(?)', $values['new_email'])) {
                    $this->view->formErrors[] = 'That email address is already registered.';
                    $form->getElement('new_email')->setValue('');
                }
                else {
                    $row = $this->db->fetchRow("SELECT md5(md5(email || id) || md5(? || creation)) AS key, email FROM member_members WHERE id=?", array($values['new_email'], $this->target->id));

                    // Send Change Email
                    self::_sendChangeEmail($row->email, $values['new_email'], $row->key);
                    
                    $this->view->headTitle('Verification Email Sent', 'PREPEND');
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain successmessage',
                        'hd' => 'Change Email',
                        'hd2' => 'Request to Change Email Address',
                        'bd' => '<p class="success">Your request to change your email address has been submitted.</p><p>Please check your new email address for a confirmation email and instructions on how to complete your request.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/member/update/') . '">Continue &raquo;</a></p>'
                    ));
                    return $this->_helper->viewRenderer->setNoRender();
                }
            }
        }
        
        $this->view->form = $form;
    }
    
    private function _sendChangeEmail ($old_email, $new_email, $key)
    {
        if ($old_email && $new_email && $key) {
            $internal = new StdClass;
            $internal->vars = $this->vars;
            $internal->config = $this->config;
            $internal->community = $this->community;
            
            $partial_array = array('old_email' => $old_email, 'new_email' => $new_email, 'key' => $key, 'internal' => $internal);
            
            $this->_helper->ViaMe->sendEmail($old_email, null, $this->community->display . ' Email Change Request', $this->view->partial('update/emails/change-old-html.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
            
            $this->_helper->ViaMe->sendEmail($new_email, null, $this->community->display . ' Email Change Request', $this->view->partial('update/emails/change-new-html.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
}
