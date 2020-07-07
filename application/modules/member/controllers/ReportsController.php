<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_ReportsController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    //public function preDispatch() { }
    
    protected $reports = array(
        'trump' => '/usr/local/www/websites/viame.com/files/private/2016/12/21/0e19720b75d570bdf672b00b543de892',
        'spyr' => '/usr/local/www/websites/viame.com/files/private/2017/03/16/0e24841c91dc7c0935ec21bc7c87a2c3',
        'commodities' => '/usr/local/www/websites/viame.com/files/private/2017/03/22/604c38407209b7df73b8bb013bca1359',
        'iot' => '/usr/local/www/websites/viame.com/files/private/2017/03/22/1ce1554fdd9076a16a74ce8c2a64427a',
        'robotics' => '/usr/local/www/websites/viame.com/files/private/2017/03/22/373d3d6b6776f47de8505d5af56e990a'
    );
    
    public function indexAction()
    {
        if ($this->_getParam('ajax')) {
            $this->_setParam('no_layout', '1');
            ob_clean();
        }
        
        #$this->_sendVerifyEmail('akang@levelogic.com', 'Arthur', 19);
        #return $this->_helper->viewRenderer->setNoRender();
            
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        #$this->_helper->ViaMe->setLayout('plain');
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SmallCap Network', 'PREPEND');
        $this->view->headTitle('Register', 'PREPEND');
        
        if (!isset($this->view->formErrors)) { $this->view->formErrors = array(); }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'report_form',
                'id' => 'report_form',
                'class' => 'form condensed regula-validation',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.report_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elements' => array(
                'first_name' => array('Text', array(
                    'label' => 'First Name',
                    #'description' => 'Your first name',
                    #'required' => true,
                    'maxlength' => 32,
                    #'class' => 'regula-validation',
                    #'data-constraints' => '@Required(label="first name", message="The {label} field cannot be empty.", groups=[report_form])',
                    'order' => 5
                )),
                'last_name' => array('Text', array(
                    'label' => 'Last Name',
                    #'description' => 'Your last name',
                    #'required' => true,
                    'maxlength' => 32,
                    #'class' => 'regula-validation',
                    #'data-constraints' => '@Required(label="last name", message="The {label} field cannot be empty.", groups=[report_form])',
                    'order' => 15
                )),
                'phone' => array('Text', array(
                    'label' => 'Phone #',
                    'description' => 'Please be available at this number, as we will be sending you a text or voice confirmation code.',
                    #'required' => true,
                    'maxlength' => 32,
                    #'class' => 'regula-validation vmfh_phone',
                    'class' => 'regula-validation vmfh_phone',
                    'data-constraints' => '@Pattern(regex=/^[\d\s\(\)\-\+\.]*$/, label="phone number", message="Use digits only in the {label}.", groups=[report_form], ignoreEmpty=true)',
                    'order' => 20
                )),
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    #'description' => 'Your login email',
                    #'required' => true,
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
                    'data-constraints' => '@Email(label="email address", message="The {label} was not formatted properly.", groups=[report_form, login_form], ignoreEmpty=true)',
                    'placeholder' => 'email@domain.com',
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
        #$form->setAction($this->target->pre . '/member/reports/');
        #$form->setAction('?');
        
        $form->addElement('Submit', 'submit', array('label' => 'Get Your Free Report Now!', 'class' => 'big green', 'ignore' => true, 'order' => 999));
        #$form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'class' => 'big', 'ignore' => true, 'order' => 1000, 'onClick' => "if (confirm('Are you sure you want to cancel registration?')) { document.getElementById('report_form').vivregval_canceled = true; return true; } else { return false; }"));
        
        $form->addDisplayGroup(array('first_name', 'last_name', 'phone', 'email'), 'information', array('legend' => 'Information'));
            

        // Things that need to be done outside of the cache due to parameters
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
            #$form->getElement('vmpd_npr')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            #$form->getElement('vmpd_nar')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
            #$form->getElement('redirect')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'report', array('value' => $this->_getParam('report')));
        
        $form->addElement('Hidden', 'signup_entrance', array('value' => $this->_getParam('signup_entrance')));
        $form->addElement('Hidden', 'click_track', array('value' => $this->_getParam('click_track')));
        
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect', 'report', 'signup_entrance', 'click_track'), 'hidden');
        $form->addDisplayGroup(array('submit'), 'buttons');
        
        
        // Do LIVE email check
        if ($this->getRequest()->isPost() && $this->_getParam('email') && !($this->_getParam('noemailvalidation') || $this->_getParam('novalidation'))) {
            $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
            #$SMTP_Validator->debug = true;
            $results = $SMTP_Validator->validate(array($this->_getParam('email')), $this->community->email);
            
            if (isset($results[$this->_getParam('email')]) && ($results[$this->_getParam('email')][0] === false)) {
                // Came back false
                if (isset($results[$this->_getParam('email')][1]) && $results[$this->_getParam('email')][1]) {
                    $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('email') . ' (' . $results[$this->_getParam('email')][1] . ') - Please correct or select an alternate email address.';
                }
                else {
                    $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('email');
                }
                $this->_setParam('email', '');
            }
        }
        
        // Do LIVE phone check
        $pValidate = null;
        if ($this->getRequest()->isPost() && $this->_getParam('phone') && !($this->_getParam('nophonevalidation') || $this->_getParam('novalidation'))) {
            /* This is because sometimes the intl-tel-input doesn't work... */
            /* Wasn't working because viame_form_helper wasn't fetching from static.viame.com and after subsequent requests too fast, you get blocked */
            if (!preg_match('/^[\+1]/', $this->_getParam('phone'))) {
                $this->_setParam('phone', '1' . $this->_getParam('phone'));
            }
            
            $fPhone = preg_replace('/[^\+\d]/', '', $this->_getParam('phone'));
            $pValidate = $this->_phoneValidate($fPhone);
            
            if (!($pValidate && $pValidate->valid)) {
                $this->view->formErrors[] = 'Invalid Phone Number: ' . $this->_getParam('phone');
                $this->_setParam('phone', '');
            }
            
        }
        
        if ($this->_getParam('id') && $this->_getParam('code')) {
            $results = $this->db->update('member_leads', array('phone_confirmed' => true), array('id=?' => $this->_getParam('id'), 'phone_confirm_code=?' => $this->_getParam('code')));
            if ($results) {
                if ($this->_getParam('showreport')) {
                    $this->_setParam('no_layout', '1');
                    // Confirmed - Give them the Report
                    if ($this->_getParam('report') && isset($this->reports[$this->_getParam('report')])) {
                        $file = $this->reports[$this->_getParam('report')];
                    }
                    else {
                        $file = reset($this->reports);
                    }
                    
                    header('Content-Type: application/pdf');
                    header('Content-Length: ' . filesize($file));
                    ob_clean();
                    flush();
                    readfile($file);
                    exit;
                }
                else {
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain successmessage',
                        'hd' => 'Phone Number Confirmed',
                        'hd2' => 'Confirmation was successful.',
                        'bd' => '<p class="success">Thank you for confirming your phone number, and welcome to the community.</p>'
                    ));
                }
            }
            else {
                $this->view->formErrors[] = 'There was a problem confirming that code.  Please try your request again.';
            }
        }
        elseif ($this->_getParam('id') && $this->_getParam('redial')) {
            $this->_setParam('no_layout', '1');
            $redial = $this->db->fetchRow("SELECT * FROM member_leads WHERE id=?", array($this->_getParam('id')));
            
            if (isset($redial->phone) && $redial->phone) {
                $plivo = new ViaMe_Vm_Im_Plivo();
                
                $return = $plivo->make_call(array($redial->phone), array(
                    'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak language="en-GB" loop="2">Your confirmation code is: ' . implode(',', str_split($redial->phone_confirm_code)) . '<%2FSpeak><%2FResponse>',
                    'answer_method' => 'GET',
                    'hangup_url' => 'http://www.viame.com/zfbp/xml/?xmlid=default_hangup',
                    'hangup_method' => 'GET',
                    'time_limit' => '60'
                ));
                
                echo 'PASS';
            }
            else {
                echo 'FAIL';
            }
            
            exit;
            #return $this->_helper->viewRenderer->setNoRender();
        }
        elseif ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams()) && !count($this->view->formErrors)) {
            $fields = array('first_name', 'last_name', 'phone', 'email');
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
            
            if ($this->_getParam('leads_pre_verified', 0)) {
                $data['active'] = 't';
            }
            
                        
            $this->db->beginTransaction();
            try {
                $rcode = sprintf("%06s", mt_rand(0,999999));
                $data['phone_confirm_code'] = $rcode;
                
                if ($this->_getParam('phone') && !($this->_getParam('nophonevalidation') || $this->_getParam('novalidation'))) {
                    if ($pValidate->line_type == 'mobile') {
                        $data['phone_type'] = 'mobile';
                    }
                    else {
                        $data['phone_type'] = 'landline';
                    }
                }
                
                $this->db->insert('member_leads', $data);
                $id = $this->db->lastInsertId('member_leads', 'id');
                $this->db->commit();
                
                if ($this->_getParam('phone')) {
                    // Send Confirm Code
                    $plivo = new ViaMe_Vm_Im_Plivo();
                    
                    // Already validated before we came in here
                    if (!($this->_getParam('nophonevalidation') || $this->_getParam('novalidation'))) {
                        if ($pValidate->line_type == 'mobile') {
                            $return = $plivo->send_message(array($fPhone), $rcode);
                        }
                        else {
                            $return = $plivo->make_call(array($fPhone), array(
                                'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak language="en-GB" loop="2">Your confirmation code is: ' . implode(',', str_split($rcode)) . '<%2FSpeak><%2FResponse>',
                                'answer_method' => 'GET',
                                'hangup_url' => 'http://www.viame.com/zfbp/xml/?xmlid=default_hangup',
                                'hangup_method' => 'GET',
                                'time_limit' => '60'
                            ));
                        }
                    }
                    
                    if (!($this->_getParam('noemailvalidation') || $this->_getParam('novalidation'))) {
                        if ($this->_getParam('email')) {
                            // Send Verify Email
                            $this->_sendVerifyEmail($data['email'], (isset($data['first_name']) ? $data['first_name'] : ''), $id, $this->_getParam('showreport'));
                        }
                    }
                    
                    $form = new Zend_Form();
                    $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
                    $form->setOptions(array(
                        'attribs' => array(
                            'name' => 'report_confirm_form',
                            'id' => 'report_confirm_form',
                            'class' => 'form condensed regula-validation',
                            'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.report_confirm_form] }) && YAHOO.viame.dubsub.check(this));'
                        ),
                        'elements' => array(
                            'code' => array('Text', array(
                                'label' => 'Confirmation Code',
                                'required' => true,
                                'maxlength' => 6,
                                'class' => 'regula-validation',
                                'class' => 'regula-validation',
                                'data-constraints' => '@Required(label="confirmation code", message="The {label} field cannot be empty.", groups=[report_confirm_form]) @Pattern(regex=/^[\d]*$/, label="confirmation code", message="Use digits only in the {label}.", groups=[report_confirm_form])',
                                'order' => 5
                            ))
                        )
                    ));
                    
                    $form->addElement('Submit', 'submit', array('label' => 'Confirm Code', 'ignore' => true, 'order' => 999));
                    
                    $form->addDisplayGroup(array('code'), 'confirm', array('legend' => 'Confirm'));
                    
                    // Things that need to be done outside of the cache due to parameters
                    $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
                        #$form->getElement('vmpd_npr')->setDecorators( array('ViewHelper') );
                    $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
                        #$form->getElement('vmpd_nar')->setDecorators( array('ViewHelper') );
                    $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
                        #$form->getElement('redirect')->setDecorators( array('ViewHelper') );
                    $form->addElement('Hidden', 'report', array('value' => $this->_getParam('report')));
                    $form->addElement('Hidden', 'id', array('value' => $id));
                    
                    $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect', 'report', 'id'), 'hidden');
                    $form->addDisplayGroup(array('submit'), 'buttons');
                    
                    if (!$this->_getParam('ajax')) {
                        $this->view->form = $form;
                        return $this->render('index');
                    }
                }
                elseif ($this->_getParam('email')) {
                    // Send Verify Email With Param to Show Report
                    if (!($this->_getParam('noemailvalidation') || $this->_getParam('novalidation'))) {
                        $this->_sendVerifyEmail($data['email'], (isset($data['first_name']) ? $data['first_name'] : ''), $id, $this->_getParam('showreport'));
                    }
                    
                    if (!$this->_getParam('ajax')) {
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain successmessage',
                            'hd' => 'Confirm Email',
                            'hd2' => 'Email confirmation required.',
                            'bd' => '<p class="success">Thank you for your submission.  Please check your email and click on the confirmation link.</p>'
                        ));
                        return $this->_helper->viewRenderer->setNoRender();
                    }
                }
                
                if ($this->_getParam('ajax')) {
                    echo $id;
                    exit;
                }
            } catch (Exception $e) {
                $this->db->rollBack();
            }
            
            if ($this->_getParam('ajax')) {
                echo 'FAIL';
                exit;
            }
            
            # Will automatically reshow main form...
        }
        elseif ($this->getRequest()->isPost() && $this->_getParam('ajax')) {
            echo 'FAIL';
            exit;
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
    }
    
    public function verifyAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('SmallCap Network', 'PREPEND');
        $this->view->headTitle('Verify', 'PREPEND');
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'member_verify_form',
                'id' => 'member_verify_form',
                'class' => 'form condensed',
                'method' => 'post',
                'action' => $this->target->pre . '/member/reports/verify/',
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
                $isActive = $this->db->fetchOne("SELECT active FROM member_leads WHERE id=? AND email=?", array($params->id, $params->email));
                $result = $this->db->query("UPDATE member_leads SET active='t' WHERE id=? AND email=?", array($params->id, $params->email));
                $member = $this->db->fetchRow("SELECT * FROM member_leads WHERE id=? AND email=?", array($params->id, $params->email));
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
                	
                	#$GA_data['customfields[ID]'] = $member->id;
                	#if ($member->first_name) { $GA_data['customfields[First Name]'] = $member->first_name; }
                	#if ($member->last_name) { $GA_data['customfields[Last Name]'] = $member->last_name; }
                	#if ($member->click_track) { $GA_data['customfields[Click_Track]'] = $member->click_track; }
                	#if ($member->signup_entrance) { $GA_data['customfields[Signup_Entrance]'] = $member->signup_entrance; }
                	
                    $result_add = $GA_API->call_method('subscriberAdd', $GA_data);
                    
                    $GA_data['listid'] = 5;
                    $result_add = $GA_API->call_method('subscriberAdd', $GA_data);
                } catch (Exception $e) { }
                
                /*
                // Send them a welcome email
                if (!$isActive) {
                    $partial_array = array('email' => $member->email, 'id' => $member->id, 'internal' => $this->internal);
                    
                    $temp = $this->view->partial('leads/emails/welcome.phtml', $partial_array);
                    
                    $this->_helper->ViaMe->sendEmail($member->email, 'SCN Member', 'Welcome!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                }
                
                // Display Form For Mobile Number
                $this->view->member = $member;
                return; // Automatically render the verify page that asks for mobile #
                */
                
                if ($this->_getParam('showreport')) {
                    $this->_setParam('no_layout', '1');
                    // Confirmed - Give them the Report
                    if ($this->_getParam('report') && isset($this->reports[$this->_getParam('report')])) {
                        $file = $this->reports[$this->_getParam('report')];
                    }
                    else {
                        $file = reset($this->reports);
                    }
                    
                    header('Content-Type: application/pdf');
                    header('Content-Length: ' . filesize($file));
                    ob_clean();
                    flush();
                    readfile($file);
                    exit;
                }
                else {
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain successmessage',
                        'hd' => 'Email Address Confirmed',
                        'hd2' => 'Confirmation was successful.',
                        'bd' => '<p class="success">Thank you for confirming your email address, and welcome to the community.</p>'
                    ));
                }
                
                return $this->_helper->viewRenderer->setNoRender();
            } else {
                // Failure
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Confirmation Failed',
                    'hd2' => 'Email confirmation error.',
                    'bd' => '<p class="error">An error has occurred while confirming your email address.</p><p>We could not verify that information.  Please click the confirm link in the email you received or try your request again by <a href="/member/reports/">clicking here</a>.</p>'
                ));
                
                return $this->_helper->viewRenderer->setNoRender();
            }
        
        }
        
        return $this->_redirect('/member/reports/');
    }
    
    
    private function _sendVerifyEmail ($email, $name, $id, $showreport = false)
    {
        if ($email) {
            if (!$name) { $name = 'Investor'; }
            
            $partial_array = array('email' => $email, 'name' => $name, 'id' => $id, 'showreport' => $showreport, 'report' => $this->_getParam('report'), 'internal' => $this->internal);
            
            $temp = $this->view->partial('reports/emails/verify.phtml', $partial_array);
            
            $this->_helper->ViaMe->sendEmail($email, $name, 'Please Confirm Your Email Address', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
    
    
    private function _phoneValidate($number = null)
    {
        if ($number) {
            $response = file_get_contents("http://apilayer.net/api/validate?access_key=fa2a40dcd142342bc946a86d0bb3c815&number=$number");
            
            if ($response !== false) {
                $data = json_decode( $response );
                if ($data) {
                    return $data;
                }
            }
        }
    }
    
    
    public function adminAction()
    {
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) {
            return $this->_denied();
        } else {
            #$this->view->headTitle('Admin', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
        
        
        $rows = $this->db->fetchAll("SELECT * FROM member_leads ORDER BY creation");
            
        echo '<table width="100%">';
        foreach ($rows as $row) {
            echo "<tr><td>$row->id</td><td>$row->creation</td><td>$row->first_name</td><td>$row->last_name</td><td>$row->email</td><td>$row->phone</td><td>$row->phone_type</td><td>$row->phone_confirmed</td><td>$row->signup_entrance</td><td>$row->click_track</td><td>$row->active</td></tr>";
        }
        echo '</table>';
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}