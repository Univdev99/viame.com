<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_ScneolController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    //public function preDispatch() { }
    
    
    public function indexAction()
    {
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SCN Elite Opportunity Free Alerts', 'PREPEND');
        $this->view->headTitle('Register', 'PREPEND');
        
        if (!isset($this->view->formError)) { $this->view->formErrors = array(); }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'member_form',
                'id' => 'member_form',
                'class' => 'form regula-validation',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elements' => array(
                'first_name' => array('Text', array(
                    'label' => 'First Name',
                    #'description' => 'Your first name',
                    #'required' => true,
                    'maxlength' => 32,
                    #'class' => 'regula-validation',
                    #'data-constraints' => '@Required(label="first name", message="The {label} field cannot be empty.", groups=[member_form])',
                    'order' => 5
                )),
                'last_name' => array('Text', array(
                    'label' => 'Last Name',
                    #'description' => 'Your last name',
                    #'required' => true,
                    'maxlength' => 32,
                    #'class' => 'regula-validation',
                    #'data-constraints' => '@Required(label="last name", message="The {label} field cannot be empty.", groups=[member_form])',
                    'order' => 15
                )),
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    #'description' => 'Your login email',
                    'required' => true,
                    'maxlength' => 256,
                    'validators' => array(
                        array('EmailAddress', false, array(
                            'messages' => array(
                                Zend_Validate_EmailAddress::INVALID             => 'Invalid type given. String expected.',
                                Zend_Validate_EmailAddress::INVALID_FORMAT      => 'Invalidly formatted email address.',
                                Zend_Validate_EmailAddress::INVALID_HOSTNAME    => 'Invalid hostname.',
                                Zend_Validate_EmailAddress::INVALID_MX_RECORD   => 'No valid MX record for %hostname%.',
                                Zend_Validate_EmailAddress::INVALID_SEGMENT     => 'Not in a routable segment.',
                                Zend_Validate_EmailAddress::DOT_ATOM            => 'Cannot be matched against dot-atom format.',
                                Zend_Validate_EmailAddress::QUOTED_STRING       => 'Cannot be matched against quoted string format.',
                                Zend_Validate_EmailAddress::INVALID_LOCAL_PART  => 'Invalid local part.',
                                Zend_Validate_EmailAddress::LENGTH_EXCEEDED     => 'Exceeds the allowed length.',
                                Zend_Validate_Hostname::UNDECIPHERABLE_TLD      => 'Cannot extract valid TLD.',
                                Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED  => 'Local network names are not allowed.'
                            )
                        ))
                    ),
                    'class' => 'regula-validation',
                    'data-constraints' => '@Email(label="email address", message="The {label} was not formatted properly.", groups=[member_form, login_form])',
                    'order' => 40
                ))
            )
        ));
        
        
        // Set Some Validators
        $name_validator = new Zend_Validate_Regex("/^[\p{L}-',.][\p{L}-',. ]*[\p{L}-',.]*$/u");
          $name_validator->setMessage('Remove invalid characters and whitespace.', Zend_Validate_Regex::NOT_MATCH);
          
        $form->getElement('first_name')->addValidator($name_validator);
        $form->getElement('last_name')->addValidator($name_validator);

            
        $form->setMethod('post');
        $form->setAction($this->target->pre . '/member/scneol/');
        
        $form->addElement('Submit', 'submit', array('label' => 'FREE Sign Up!', 'class' => 'big', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'class' => 'big', 'ignore' => true, 'order' => 1000, 'onClick' => "if (confirm('Are you sure you want to cancel registration?')) { document.getElementById('member_form').vivregval_canceled = true; return true; } else { return false; }"));
        
        $form->addDisplayGroup(array('first_name', 'last_name', 'email'), 'personal', array('legend' => 'Who Are You?'));
            

        // Things that need to be done outside of the cache due to parameters
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
            #$form->getElement('vmpd_npr')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            #$form->getElement('vmpd_nar')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
            #$form->getElement('redirect')->setDecorators( array('ViewHelper') );
        
        $form->addElement('Hidden', 'signup_entrance', array('value' => $this->_getParam('signup_entrance')));
        $form->addElement('Hidden', 'click_track', array('value' => $this->_getParam('click_track')));
        
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect', 'signup_entrance', 'click_track', 'sli'), 'hidden');
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('first_name', 'last_name', 'email');
            $params = (object) $form->getValues();
            foreach ($fields as $field) {
                if (isset($params->$field) && $params->$field !== '') {
                    $data[$field] = trim($params->$field);
                }
            }
            
            
            // Signup Entrance
            if ($this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'))) {
                $data['signup_entrance'] = $this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'));
            }
            // Click Track
            if ($this->getRequest()->getCookie('click_track', $this->_getParam('click_track'))) {
                $data['click_track'] = $this->getRequest()->getCookie('click_track', $this->_getParam('click_track'));
            }
            
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            if ($this->_getParam('scneol_pre_verified', 0)) {
                $data['active'] = 't';
            }
            
                $this->db->beginTransaction();
                try {
                    $this->db->insert('scneol_members', $data);
                    $id = $this->db->lastInsertId('scneol_members', 'id');
                    
                    // Check for duplicate profile name
                    // Checks - duplicate email or squashed name
                    
                    if (isset($data['first_name'])) {
                        $name = $data['first_name'];
                        
                        if (isset($data['last_name'])) {
                            $name .= ' ' . $data['last_name'];
                        }
                    }
                    else {
                        $name = 'SCNEO Free Alerts Member';
                    }
                    
                    // SCNEO Free Alerts Member Created
                    $this->db->commit();
                    
                    
                    // Mail the email with confirmation
                    $this->view->temp_email = $data['email'];
                    if ($this->_getParam('scneol_pre_verified', 0)) {
                        $this->_setParam('vmpd_bpip', 1);
                        $this->setParam('id', $id);
                        return $this->_forward('verify');
                    }
                    else {
                        $this->_sendVerifyEmail($data['email'], $name, $id);
                    }
                    
                    
                    return $this->render('registersuccess');
                    
                } catch (Exception $e) {
                    $this->db->rollBack();
                    
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain errormessage',
                        'hd' => 'Registration Failed',
                        'hd2' => 'Please try registering again',
                        'bd' => '<p class="error">An error has occurred. Your SCN Elite Opportunity Free Alerts account registration has failed.</p><p>An unexpected error has occurred and has caused your registration process to not complete.  Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p>'
                    ));
                    
                    return $this->_helper->viewRenderer->setNoRender();
                }

                return $this->_helper->viewRenderer->setNoRender();
            
        }
        else {
            if ($this->_getParam('fullname') && $form->getElement('first_name')) {
                $tokens = preg_split("/\s+/", $this->_getParam('fullname'));
                $form->getElement('first_name')->setValue(array_shift($tokens));
                $form->getElement('last_name')->setValue(array_pop($tokens));
            }
            elseif (isset($_COOKIE['signup_fullname']) && $form->getElement('first_name')) {
                $tokens = preg_split("/\s+/", $_COOKIE['signup_fullname']);
                $form->getElement('first_name')->setValue(array_shift($tokens));
                $form->getElement('last_name')->setValue(array_pop($tokens));
            }
            
            if (isset($_COOKIE['signup_email'])) {
                $form->getElement('email')->setValue($_COOKIE['signup_email']);
            }
            
            $form->populate($this->_getAllParams());
        }
        
        $this->view->form = $form;
        
        if ($form->getElement('first_name')) { $this->view->fnwelcome = $form->getElement('first_name')->getValue(); }
    }
    
    public function verifyAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SCN Elite Opportunity Free Alerts', 'PREPEND');
        $this->view->headTitle('Verify', 'PREPEND');
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'member_verify_form',
                'id' => 'member_verify_form',
                'class' => 'form condensed',
                'method' => 'post',
                'action' => $this->target->pre . '/member/scneol/verify/',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_verify_form] });'
            ),
            'elements' => array(
                'email' => array('Hidden', array(
                    'required' => true,
                    'Decorators' => array('ViewHelper')
                )),
                'id' => array('Hidden', array(
                    'required' => true,
                    'Decorators' => array('ViewHelper')
                ))
            )
        ));
        
        
        $this->view->formErrors = array();
        
        if (($this->getRequest()->isPost() || $this->_getParam('vmpd_bpip')) && $form->isValid($this->_getAllParams())) {
            $params = (object) $form->getValues();
                
            try {
                $isActive = $this->db->fetchOne("SELECT active FROM scneol_members WHERE id=? AND email=?", array($params->id, $params->email));
                $result = $this->db->query("UPDATE scneol_members SET active='t' WHERE id=? AND email=?", array($params->id, $params->email));
                $member = $this->db->fetchRow("SELECT * FROM scneol_members WHERE id=? AND email=?", array($params->id, $params->email));
            } catch (Exception $e) {}
                
            if ($result->rowCount()) {
                
                // Success - Add them to the GreenArrow List
                try {
                    require_once $this->vars->APP_PATH . "/library/Other/GreenArrowStudioAPI.php";
                    $GA_API = new GreenArrowStudioAPI();
                    $GA_data = array(
                		'email'         => $member->email,
                		'listid'        => 31,
                		'reactivate'    => 1,
                		'requestip'     => $_SERVER['REMOTE_ADDR']
                	);
                	
                	$GA_data['customfields[ID]'] = $member->id;
                	if ($member->first_name) { $GA_data['customfields[First Name]'] = $member->first_name; }
                	if ($member->last_name) { $GA_data['customfields[Last Name]'] = $member->last_name; }
                	if ($member->click_track) { $GA_data['customfields[Click_Track]'] = $member->click_track; }
                	if ($member->signup_entrance) { $GA_data['customfields[Signup_Entrance]'] = $member->signup_entrance; }
                	
                    $result_add = $GA_API->call_method('subscriberAdd', $GA_data);
                } catch (Exception $e) { }
                
                // Send them a welcome email
                if (!$isActive) {
                    $partial_array = array('email' => $member->email, 'id' => $member->id, 'internal' => $this->internal);
                    
                    $temp = $this->view->partial('scneol/emails/welcome.phtml', $partial_array);
                    
                    $this->_helper->ViaMe->sendEmail($member->email, 'SCNEO Free Alerts Member', 'Welcome To SmallCap Network Elite Opportunity Free Alerts!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                }
                
                // Display Form For Mobile Number
                $this->view->member = $member;
                return; // Automatically render the verify page that asks for mobile #
                
            } else {
                // Failure
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Confirmation Failed',
                    'hd2' => 'Email confirmation error.',
                    'bd' => '<p class="error">An error has occurred while confirming your email address.</p><p>We could not verify that information.  Please click the confirm link in the email you received or try your request again by <a href="/member/scneol/">clicking here</a>.</p>'
                ));
                
                return $this->_helper->viewRenderer->setNoRender();
            }
        
        }
        
        return $this->_redirect('/member/scneol/');
    }
    
    
    public function mobileAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SCN Elite Opportunity Free Alerts', 'PREPEND');
        $this->view->headTitle('Mobile Alerts', 'PREPEND');
        
        if (($this->getRequest()->isPost() || $this->_getParam('vmpd_bpip')) && $this->_getParam('id') && $this->_getParam('email') && preg_replace(array('/\D/', '/^0+1*/'), '', $this->_getParam('mobile'))) {
            $params = (object) $this->_getAllParams();
            
            if ($this->_getParam('confirm')) {
                // Confirm the code
                try {
                    $result = $this->db->query("UPDATE scneol_members SET confirmed='t' WHERE id=? AND email=? AND mobile_number=? AND confirm_code=?", array($params->id, $params->email, preg_replace(array('/\D/', '/^0+1*/'), '', $params->mobile), $params->confirm));
                } catch (Exception $e) {}
                    
                if ($result->rowCount()) {
                    
                    // Success - All done
                    // Show welcome message
                    return $this->render('confirmed');
                    
                }
                else {
                    // Didn't confirm - Allow retry
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain errormessage',
                        'hd' => 'Confirmation Failed',
                        'hd2' => 'Email confirmation error.',
                        'bd' => '<p class="error">An error has occurred while confirming the code.</p><p>That code was incorrect.  Please try your request again.</p>'
                    ));
                    
                    return $this->_helper->viewRenderer->setNoRender();
                }
            
            }
            else {
                // Save the mobile number and Send Confirmation Code
                $new_code = sprintf("%06s", mt_rand(0,999999));
                
                try {
                    $result = $this->db->query("UPDATE scneol_members SET confirmed='f', mobile_number=?, confirm_code=? WHERE id=? AND email=?", array(preg_replace(array('/\D/', '/^0+1*/'), '', $params->mobile), $new_code, $params->id, $params->email));
                } catch (Exception $e) {}
                
                if ($result->rowCount()) {
                    // Send the new code
                    $sms = new ViaMe_Vm_Im_Plivo();
                    $response = $sms->send_message(array(array('key' => $params->id, 'id' => preg_replace(array('/\D/', '/^0+1*/'), '', $params->mobile))), $new_code);
                    
                    if (isset($response[0]['response']['error'])) {
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain errormessage',
                            'hd' => 'SMS Error',
                            'hd2' => 'Sending confirmation code error.',
                            'bd' => '<p class="error">An error has occurred while attempting to send you a confirmation code.</p><p>There was a problem in sending a confirmation code to your mobile number.  Please click the confirm link in the email you received or try your request again by <a href="javascript:history.back();">going back</a>.</p>'
                        ));
                        return $this->_helper->viewRenderer->setNoRender();
                    }
                    else {
                        // Display Confirm Form
                        $this->view->params = $params;
                        return;
                    }
                }
            }
        }
        
        return $this->_redirect('/member/scneol/');
    }
    
    
    public function unsubscribeAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SCN Elite Opportunity Free Alerts', 'PREPEND');
        $this->view->headTitle('Unsubscribe', 'PREPEND');
        
        
        if ($this->_getParam('id') && $this->_getParam('email')) {
            $params = (object) $this->_getAllParams();
            
            if ($this->_getParam('unsub_confirmed')) {
                // Confirm the code
                try {
                    if (isset($params->sms) && $params->sms) {
                        $result = $this->db->query("UPDATE scneol_members SET confirmed='f' WHERE id=? AND email=?", array($params->id, $params->email));
                    }
                    else {
                        $result = $this->db->query("UPDATE scneol_members SET active='f' WHERE id=? AND email=?", array($params->id, $params->email));
                    }
                } catch (Exception $e) {}
                
                if ($result->rowCount() && !(isset($params->sms) && $params->sms)) {
                    require_once $this->vars->APP_PATH . "/library/Other/GreenArrowStudioAPI.php";
                    $GA_API = new GreenArrowStudioAPI();
                    $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                		'email'         => $params->email,
                		'listid'        => 31,
                		'requestip'     => $_SERVER['REMOTE_ADDR']
                	));
                }
                
                
                // Display success
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain successmessage',
                    'hd' => 'Unsubscribe Complete',
                    'hd2' => 'You Have Successfully Unsubscribed',
                    'bd' => '<p class="success">Your unsubscribe request has been completed.</p><p>Your unsubscribe request has been processed. Thank you.</p>'
                ));
                
                return $this->_helper->viewRenderer->setNoRender();
            }
            else {
                echo $this->view->CM(array(
                    'class' => 'cm decorated',
                    'hd' => 'Unsubscribe Confirm',
                    'hd2' => 'Confirm Your Unsubscribe Request',
                    'bd' => '<p style="font-weight: bold;">Please confirm that you would like to cancel your subscription to: SCN Elite Opportunity Free Alerts'.(isset($params->sms) && $params->sms ? ' (Text Alerts Only)' : '').'</p><p align="center" style="margin: 2em 0; text-align: center;"><a href="?unsub_confirmed=1&id='.$params->id.'&email='.$params->email.(isset($params->sms) && $params->sms ? '&sms=1' : '').'" class="fakebutton">Confirmed - I Would Like To Unsubscribe</a></p>'
                ));
                
                return $this->_helper->viewRenderer->setNoRender();
            }
        }
        
        return $this->_redirect('/member/scneol/');
    }
    
    
    public function tmpAction()
    {
        
    }
    
    
    private function _sendVerifyEmail ($email, $name, $id)
    {
        if ($email) {
            if (!$name) { $name = 'SCNEO Free Alerts Member'; }
            
            $partial_array = array('email' => $email, 'name' => $name, 'id' => $id, 'internal' => $this->internal);
            
            $temp = $this->view->partial('scneol/emails/verify.phtml', $partial_array);
            
            $this->_helper->ViaMe->sendEmail($email, $name, 'Your New SmallCap Network Elite Opportunity Free Alerts Account Requires Verification!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
}