<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_RegisterController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    public function init()
    {
        parent::init();
        
        // Check to see if there is a member
        if (isset($this->member)) {
            $this->_autoredirect($this->target->pre . '/');
        }
    }
    
    public function preDispatch() {
        // Check to see if there is a member cookie
        if (isset($this->cookie->{$this->config->auth->cookie_name->login})) {
            #return $this->_forward('index', 'login');
            return $this->_redirect('/member/login/?redirect=' . urlencode($this->_getParam('redirect')));
        }
    }
    
    public function indexAction()
    {
    	
    	/*$cache = Zend_Cache::factory(
                        'Core', 'File', array(
                    'lifetime' => 3600 * 24 * 7, //cache is cleaned once a day                            
                    'automatic_serialization' => true
                        ), array('cache_dir' => APPLICATION_PATH . '/cache')
        );*/
    	
    	//$cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Register', 'PREPEND');
        
        if (!isset($this->view->formError)) { $this->view->formErrors = array(); }
        
        // Cached - Max
        // BIG NOTE - Turned off the cache because we were only saving the form.
        //  If the form was cached, the members_form was not loaded and the other check variables
        //  are never loaded -> causing errors.  Need to cache all variables too, or cache as class
        #if (!($form = $this->cache->core->load('test2'))) {
            // Load the Form
            require_once dirname(__FILE__) . '/includes/members_form.php';
            
            $form->setMethod('post');
            $form->setAction($this->target->pre . '/member/register/');
            
            // Set Some Defaults Based on Language and Locale
            # Done in the form
            #$form->getElement('country')->setValue('US');
            #$form->getElement('currency')->setValue('USD');
            #$form->getElement('language')->setValue('en');
            
            $form->addElement('Submit', 'submit', array('label' => 'Create Account', 'class' => 'big', 'ignore' => true, 'order' => 999));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'class' => 'big', 'ignore' => true, 'order' => 1000, 'onClick' => "if (confirm('Are you sure you want to cancel registration?')) { document.getElementById('member_form').vivregval_canceled = true; return true; } else { return false; }"));
            
            $form->addDisplayGroup(array('first_name', 'middle_name', 'last_name', 'phone', 'gender', 'dob', 'dob_month', 'dob_day', 'dob_year', 'postal_code'), 'personal', array('legend' => 'Personal Information'));
            $form->addDisplayGroup(array('username', 'email', 'password', 'password_confirm'), 'login', array('legend' => 'Login Information'));
            $form->addDisplayGroup(array('timezone', 'country', 'currency', 'language'), 'locale', array('legend' => 'Locale Settings'));
            
            
            #$form->getElement('dob')->setDecorators(array('ViewHelper', array('Description', array('tag'=>'div')), 'Errors', array('HtmlTag', array('tag'=>'dd')), array('Label', array('tag'=>'dt'))));
            #$form->getElement('dob')->setDecorators(array('ViewHelper', 'Errors', 'Description', 'HtmlTag', 'Label'));
            
            #$this->cache->core->save($form);
        #}

        
        // Things that need to be done outside of the cache due to parameters
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
            #$form->getElement('vmpd_npr')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            #$form->getElement('vmpd_nar')->setDecorators( array('ViewHelper') );
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
            #$form->getElement('redirect')->setDecorators( array('ViewHelper') );
        
        $form->addElement('Hidden', 'signup_entrance', array('value' => $this->_getParam('signup_entrance')));
        $form->addElement('Hidden', 'click_track', array('value' => $this->_getParam('click_track')));
        $form->addElement('Hidden', 'sli', array('value' => $this->_getParam('sli')));
        
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect', 'signup_entrance', 'click_track', 'sli'), 'hidden');
        
        
        $password_confirm_validator = new Zend_Validate_Identical($this->_getParam('password'));
        $password_confirm_validator->setMessages(array(
            Zend_Validate_Identical::NOT_SAME => 'Password was not confirmed.', Zend_Validate_Identical::MISSING_TOKEN => 'Password was not confirmed.'
        ));
        $form->getElement('password_confirm')->addValidator($password_confirm_validator);
        
        if (isset($this->locale) && is_string($this->locale->getRegion()) && $this->locale->getRegion() != 'US') {
            $form->getElement('country')->setValue($this->locale->getRegion());
            if (array_key_exists($this->locale->getRegion(), $timezone_to_country)) {
                $form->getElement('timezone')->setValue($timezone_to_country[$this->locale->getRegion()]);
            }
            #if (array_key_exists($temp, $timezone_to_country)) {
            #    $form->getElement('timezone')->setValue($timezone_to_country[$this->locale->getRegion()]);
            #}
            if (isset($multioptions_nt[$this->locale->getRegion()]) && array_key_exists($multioptions_nt[$this->locale->getRegion()], $tz_multioptions)) {
                $form->getElement('timezone')->setValue(key($tz_multioptions[$multioptions_nt[$this->locale->getRegion()]]));
            }
        }
        $form->getElement('language')->setValue(isset($this->vars->language) ? $this->vars->language : $this->locale->getLanguage());
        
        //$form->addElement('Textarea', 'captcha', array('label' => 'Captcha', 'ignore' => true, 'order' => 900, 'value' => 'Captcha Me'));
        //$form->getElement('captcha')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
       
        #$form->addElement('Textarea', 'tos', array('label' => 'Terms of Service', 'ignore' => true, 'order' => 905, 'value' => '&Blah blah blah'));
        #$form->getElement('tos')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
        
        $form->addElement('Checkbox', 'tos', array(
            'label' => 'I Agree',
            'order' => 905,
            'required' => true,
            'description' => "I have read and agree to the <a href=\"/page/view/p/mid/1/title/Terms+of+Use/\" target=\"_blank\" onclick=\"return YAHOO.viame.shadowbox.shadowto('/page/view/p/mid/1/title/Terms+of+Use/?vmpd_rqsv=no_layout,vmpd_nsslr,vmcd_ncr&no_layout=1&vmpd_nsslr=1&vmcd_ncr=1', {width: '600px', height: '500px', fixedcenter: true, close: true, draggable: false, modal: true, visible: false});\" title=\"Terms of Service\">Terms of Service</a> and <a href=\"/page/view/p/mid/1/title/Privacy+Policy/\" target=\"_blank\" onclick=\"return YAHOO.viame.shadowbox.shadowto('/page/view/p/mid/1/title/Privacy+Policy/?vmpd_rqsv=no_layout,vmpd_nsslr,vmcd_ncr&no_layout=1&vmpd_nsslr=1&vmcd_ncr=1', {width: '600px', height: '500px', fixedcenter: true, close: true, draggable: false, modal: true, visible: false});\" title=\"Privacy Policy\">Privacy Policy</a>.",
            'class' => 'regula-validation',
            'data-constraints' => '@Required(message="You must agree to the Terms of Use.", groups=[member_form])',
            #'value' => true,
            'validators' => array(
                array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'member_form_tos')))
                #array('GreaterThan', false, array('min' => 0))
            )
        ));
        $form->getElement('tos')->getDecorator('description')->setEscape(false);
            
        //$form->addElement('Captcha', 'captcha', array('label'=>'Verification', 'order' => 910, 'captcha' => array('captcha' => 'ReCaptcha', 'ssl' => true, 'pubkey' => $this->config->recaptcha->pubkey, 'privkey' => $this->config->recaptcha->privkey)));
        // $form->getElement('captcha')->getCaptcha()->getService()->setOption('theme', 'clean');
        /*
        $form->addElement('Checkbox', 'iamhuman', array(
            'label'=>'Security Verification',
            'order' => 910,
            'required' => true,
            'description' => "I am a human (click the checkbox)",
            'class' => 'regula-validation',
            'data-constraints' => '@Required(message="You must verify that you are human.", groups=[member_form])',
            'validators' => array(
                array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'member_form_verification')))
            )
        ));
        */
        
        $form->addDisplayGroup(array('captcha', 'tos', ), 'verify', array('legend' => 'Agreement & Verification'));
        #$form->addDisplayGroup(array('tos'), 'verify', array('legend' => 'Agreement & Verification'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // ShortForm
        if ($this->_getParam('shortform')) {
            $form->addElement('Hidden', 'shortform', array('value' => $this->_getParam('shortform')));
            $form->getDisplayGroup('hidden')->addElement($form->getElement('shortform'));
            
            /*
            foreach (array('personal', 'locale') as $group) {
                foreach ($form->getDisplayGroup($group)->getElements() as $element => $edata) {
                    $form->removeElement($element);
                }
                
                $form->removeDisplayGroup($group);
            }
            
            #$form->getElement('tos')->getDecorator('Description')->setOption('placement', 'PREPEND');
            #$form->getElement('iamhuman')->getDecorator('Description')->setOption('placement', 'PREPEND');
            */
            
            $form->setAttrib('class', (($form->getAttrib('class') ? $form->getAttrib('class') . ' ' : '') . 'shortform'));
        }
        
        // For Social Logins
        $authed_sli = array('openid', 'google-oauth2', 'facebook-oauth2', 'linkedin-oauth2', 'twitter-oauth1');
        $sli_session = null;
        if (in_array($this->_getParam('sli'), $authed_sli)) {
            $sli_session = new Zend_Session_Namespace('SLISession');
        }
        
        // Do LIVE email check
        if ($this->_getParam('email')) {
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
        
        // SuperShortForm
        if ($this->_getParam('supershortform')) {
            $form->addElement('Hidden', 'supershortform', array('value' => 1));
            $form->getDisplayGroup('hidden')->addElement($form->getElement('supershortform'));
            
            if (in_array($this->_getParam('sli'), $authed_sli) && isset($sli_session->identity)) {
                $form->removeElement('captcha');
            }
            
            $form->getElement('tos')->setValue(true);
            $this->_setParam('tos', 1);
            
            $form->removeElement('password_confirm');
            foreach (array('username', 'password', 'tos') as $element) {
                if ($form->getElement($element)) {
                    $form->getElement($element)->setAttrib('data-constraints', null);
                    $form->getElement($element)->setAttrib('class', (($form->getElement($element)->getAttrib('class') ? preg_replace('/regula-validation\s?/i', '', $form->getElement($element)->getAttrib('class')) : '')));
                }
            }
            
            // Add classes but remove 'regula-validation' class
            $form->setAttrib('class', (($form->getAttrib('class') ? preg_replace('/regula-validation\s?/i', '', $form->getAttrib('class')) . ' ' : '') . 'shortform supershortform'));
            $form->removeAttrib('data-constraints');
            
            // Remove Elements
            #foreach (array('personal', 'locale', 'verify') as $group) {
            #    foreach ($form->getDisplayGroup($group)->getElements() as $element => $edata) {
            #        $form->removeElement($element);
            #    }
            #    $form->removeDisplayGroup($group);
            #}
            if ($this->_getParam('supershortform') == 'noverify') {
                foreach (array('verify') as $group) {
                    foreach ($form->getDisplayGroup($group)->getElements() as $element => $edata) {
                        $form->removeElement($element);
                    }
                    $form->removeDisplayGroup($group);
                }
            }
            
            /* SET A DEFAULT PROFILE NAME AND PASSWORD SO WE CAN CONTINUE THE SETUP */ 
            if ($this->getRequest()->isPost() && $form->isValidPartial(array('email' => $this->_getParam('email')))) {
                // If POSTED and valid, set the defaults - SET USERNAME EVERYTIME
                if (!$this->_getParam('username')) {
                    // Need to match this regexp up with the members_form so username validates
                    $sf_username = preg_replace(array('/@.*/', '/[^\p{L}\p{M}\p{N}\p{P}\p{Zs}]+/u', '/\s+/'), array('', ' ', ' '), $this->_getParam('email'));
                    if (strlen($sf_username) < 4) {
                        $sf_username .= str_repeat('1', (4 - strlen($sf_username)));
                    }
                    $this->_setParam('username', $sf_username);
                }
                
                $temp_password = null;
                $char_string = '1234567890ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#%&()';
                srand();
                for ($i = 0; $i < 10; $i++) {
                    $temp_password .= substr($char_string, mt_rand(0, strlen($char_string)), 1);
                }
                $form->getElement('password')->setValue($temp_password);
                $this->_setParam('password', $temp_password);
            }
        }
        
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('first_name', 'middle_name', 'last_name', 'phone', 'gender', 'postal_code', 'email', 'timezone', 'country', 'currency', 'language');
            $params = (object) $form->getValues();
            foreach ($fields as $field) {
                if (isset($params->$field) && $params->$field !== '') {
                    $data[$field] = $params->$field;
                }
            }
            if ($this->_getParam('supershortform') && !$this->_getParam('sli')) {
                $this->_setParam('username', 'NTSVA');
                $data['password'] = 'NEED_TO_SETUP_VIAME_ACCOUNT';
            }
            else {
                $data['password'] = md5($params->password);
            }
            $data['password_salt'] = md5(uniqid(rand(), true));
            
            
            // DOB Field
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
            
            // Community ID
            $data['community_id'] = $this->community->id;
            // Signup Entrance
            if ($this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'))) {
                $data['signup_entrance'] = $this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'));
            }
            // Click Track
            if ($this->getRequest()->getCookie('click_track', $this->_getParam('click_track'))) {
                $data['click_track'] = $this->getRequest()->getCookie('click_track', $this->_getParam('click_track'));
            }
            
            // Update Any Referrers
            if (isset($_COOKIE[$this->config->auth->cookie_name->community_referrer_id]) && $_COOKIE[$this->config->auth->cookie_name->community_referrer_id] > 1) {
                $data['referrer_community_id'] = $_COOKIE[$this->config->auth->cookie_name->community_referrer_id];
            }
            if (isset($_COOKIE[$this->config->auth->cookie_name->profile_referrer_id]) && $_COOKIE[$this->config->auth->cookie_name->profile_referrer_id] > 1) {
                $data['referrer_profile_id'] = $_COOKIE[$this->config->auth->cookie_name->profile_referrer_id];
            }
            
            // Check for duplicate Email
            if ($this->db->fetchOne('SELECT 1 FROM member_members WHERE lower(email)=lower(?)', $data['email'])) {
                // That email is already registered
                // Remove Email Value and Display Error Message
                $form->getElement('email')->setValue('');
                $this->view->formErrors[] = 'That email address is already registered.';
            }
            // Check for duplicate Email
            elseif ($this->db->fetchOne("SELECT 1 FROM member_members WHERE (creation > now() - '1 Week'::interval) AND ip_address=? GROUP BY ip_address HAVING COUNT(*) > 1", $_SERVER['REMOTE_ADDR'])) {
                // Already 2 or more registrations from that IP in the last week
                // Display Error Message
                #$form->getElement('email')->setValue('');
                $this->view->formErrors[] = 'There was a registration error.  Please try again in 24 hours.';
            }
            else {
                $this->db->beginTransaction();
                try {
                    $member_members = new member_models_members();
                    $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
                    $member_members->insert($data);
                    $id = $this->db->lastInsertId('member_members', 'id');
                    
                    // Check for duplicate profile name
                    // Checks - duplicate email or squashed name
                    
                    if (isset($data['first_name'])) {
                        $name = $data['first_name'];
                        
                        if (isset($data['middle_name'])) {
                            $name .= ' ' . $data['middle_name'];
                        }
                        if (isset($data['last_name'])) {
                            $name .= ' ' . $data['last_name'];
                        }
                    }
                    else {
                        $name = $this->_getParam('username');
                    }
                    
                    $profile_profiles = new profile_models_profiles();
                    $profile_profiles->insert(array('member_id' => $id, 'name' => $this->_getParam('username'), 'base' => 't', 'default_profile' => 't', 'community_id' => $this->community->id));
                    
                    $pid = $this->db->lastInsertId('profile_profiles', 'id');
                    $this->log->setEventItem('profile_id', $pid);
                    $this->log->ALERT("New member ($id) and profile (" . $this->_getParam('username') . ") successfully created.");
                    
                    // Member Created
                    $this->db->commit();
                    
                    // Blank the referrers
                    #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->community_referrer_id);
                    #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile_referrer_id);
                    
                    // Social Login
                    if (in_array($this->_getParam('sli'), $authed_sli) && isset($sli_session->identity)) {
                        try {
                            if ($this->_getParam('sli') == 'openid') {
                                $this->db->insert('member_social_logins', array(
                                    'member_id' => $id,
                                    'provider' => 'openid',
                                    'id' => $sli_session->identity
                                ));
                            }
                            elseif (preg_match('/oauth\d+$/', $this->_getParam('sli'))) {
                                $this->db->insert('member_social_logins', array(
                                    'member_id' => $id,
                                    'provider' => $this->_getParam('sli'),
                                    'id' => $sli_session->identity['id']
                                ));
                            }
                        } catch (Exception $e) { }
                        
                        Zend_Session::namespaceUnset('SLISession');
                        
                        $this->_helper->actionStack('verify', 'register', 'member',
                            array(
                                'vmpd_bpip' => 1,
                                'autogo' => 1,
                                'id' => $id,
                                'key' => md5($data['password_salt'] . $data['password'])
                            )
                        );
                        
                        return $this->_helper->viewRenderer->setNoRender();
                    }
                    else {
                        // Mail the email with confirmation
                        $secretkey = md5($data['password_salt'] . $data['password']);
                        $this->_sendVerifyEmail($data['email'], $name, $id, $secretkey);
                        
                        /*
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain successmessage',
                            'hd' => 'Registration Successful',
                            'hd2' => 'Thank you for signing up',
                            'bd' => '<p class="success">Your registration was successful and your account has been created.</p><p>Please check your email and verify your account by clicking on the link.</p><p>If you fail to receive the email, <a href="' . $this->internal->target->pre . '/member/register/verify/vmpd_nar/1/vmpd_fp/1/?email=' . $data['email'] . '">click here</a> to have the verification email resent.</p><p>If you have any problems registering or confirming this registration, please contact us at <a href="mailto:' . ($this->internal->community->email ? $this->internal->community->email : $this->internal->config->admin->email) . '">' . ($this->internal->community->email ? $this->internal->community->email : $this->internal->config->admin->email) . '</a>.</p>'
                        ));
                        */
                        $this->view->temp_email = $data['email'];
                        // Variable Output
                        if (is_file($this->view->getScriptPath('register/registersuccess/' . $this->community->name . '.phtml'))) {
                            return $this->render('registersuccess/' . $this->community->name);
                        }
                        else {
                            return $this->render('registersuccess');
                        }
                    }
                } catch (Exception $e) {
                    $this->db->rollBack();
                    
                    $this->log->EMERG('New member creation failed! ' . var_export($this->_getAllParams(), true) . ' : ' . var_export($data, true));
                    
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain errormessage',
                        'hd' => 'Registration Failed',
                        'hd2' => 'Please try registering again',
                        'bd' => '<p class="error">An error has occurred. Your account registration has failed.</p><p>An unexpected error has occurred and has caused your registration process to not complete.  Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p>'
                    ));
                    
                    return $this->_helper->viewRenderer->setNoRender();
                }

                return $this->_helper->viewRenderer->setNoRender();
            }
        }
        else {
            if ($this->_getParam('fullname') && $form->getElement('first_name')) {
                $tokens = preg_split("/\s+/", $this->_getParam('fullname'));
                $form->getElement('first_name')->setValue(array_shift($tokens));
                $form->getElement('last_name')->setValue(array_pop($tokens));
                $form->getElement('middle_name')->setValue(implode(' ', $tokens));
            }
            elseif (isset($_COOKIE['signup_fullname']) && $form->getElement('first_name')) {
                $tokens = preg_split("/\s+/", $_COOKIE['signup_fullname']);
                $form->getElement('first_name')->setValue(array_shift($tokens));
                $form->getElement('last_name')->setValue(array_pop($tokens));
                $form->getElement('middle_name')->setValue(implode(' ', $tokens));
            }
            
            if (isset($_COOKIE['signup_email'])) {
                $form->getElement('email')->setValue($_COOKIE['signup_email']);
            }
            
            $form->populate($this->_getAllParams());
        }
        
        $this->view->form = $form;
        
        if ($form->getElement('first_name')) { $this->view->fnwelcome = $form->getElement('first_name')->getValue(); }
        
        // Variable Output
        if (is_file($this->view->getScriptPath('register/index/' . $this->community->name . '.phtml'))) {
            return $this->render('index/' . $this->community->name);
        }
        else {
            // Without a return, default will automatically render
        }
    }
    
    public function verifyAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Verify', 'PREPEND');
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'member_verify_form',
                'id' => 'member_verify_form',
                'class' => 'form condensed',
                'method' => 'post',
                'action' => $this->target->pre . '/member/register/verify/',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_verify_form] });'
            ),
            'elements' => array(
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Your email address',
                    'class' => 'regula-validation',
                    'data-constraints' => '@Email(label="email address", message="The {label} was not formatted properly.", groups=[member_verify_form])',
                    'validators' => array(
                        'EmailAddress'
                    ),
                    'filters' => array(
                        'StringTrim'
                    ),
                    'order' => 5
                )),
                'key' => array('Text', array(
                    'label' => 'Verification Key',
                    'description' => 'Your verification key',
                    'filters' => array(
                        'StringTrim'
                    ),
                    'order' => 10
                )),
                'submit' => array('Submit', array(
                    'label' => 'Verify or Resend Key',
                    'description' => 'Verify or resend key',
                    'ignore' => true,
                    'order' => 15
                )),
                'id' => array('Hidden', array(
                    'Decorators' => array('ViewHelper')
                ))
            )
        ));
        
        $form->addDisplayGroup(array('email', 'key'), 'email_group');
        $form->addDisplayGroup(array('submit'), 'buttons');
        
        $this->view->formErrors = array();
        
        if (($this->getRequest()->isPost() || $this->_getParam('vmpd_bpip')) && $form->isValid($this->_getAllParams())) {
            $params = (object) $form->getValues();
            if (($params->id || $params->email) && $params->key) {
                if ($params->email) {
                    $whichID = 'lower(email)=lower(?)';
                    $who = $params->email;
                } else {
                    $whichID = 'id=?';
                    $who = $params->id;
                }
                
                // Get the existing active value
                $active_value = $this->db->fetchOne("SELECT active FROM member_members WHERE (active ISNULL OR active <> 'f') AND $whichID AND md5(password_salt || password)=?", array($who, $params->key));
                
                try {
                    $result = $this->db->query("UPDATE member_members SET active='t' WHERE (active ISNULL OR active <> 'f') AND $whichID AND md5(password_salt || password)=?", array($who, $params->key));
                    $member = $this->db->fetchRow("SELECT * FROM member_members WHERE $whichID AND md5(password_salt || password)=?", array($who, $params->key));
                } catch (Exception $e) {}
                    
                if ($result->rowCount()) {
                    // Success
                    /*
                    if (!$params->email && $params->id) {
                        $this->view->email = $this->db->fetchOne("SELECT email FROM member_members WHERE id=?", $params->id);
                    } elseif ($params->email) {
                        $this->view->email = $params->email;
                    }
                    */
                    
                    $this->view->member_profile_id = $this->db->fetchOne("SELECT MIN(id) FROM profile_profiles WHERE active='t' AND member_id=?", $member->id);
                    
                    // Setup the profile space - SYNC up with module/setup
                    if (!$this->db->fetchOne("SELECT SUM(count) - 1 FROM (SELECT COUNT(*) AS count FROM module_matrix WHERE via_id=? UNION SELECT COUNT (*) AS count FROM widget_matrix WHERE via_id=?) AS tempselect", array($this->view->member_profile_id, $this->view->member_profile_id))) {
                        $this->db->beginTransaction();
                        try {
                            // Insert the default modules
                            $this->db->query("INSERT INTO module_matrix (via_id, profile_id, module_id) (SELECT ?, ?, id FROM module_modules WHERE status='t' AND active='t' AND name IN (SELECT list_array(default_modules) FROM system_communities WHERE active='t' AND id=?))", array($this->view->member_profile_id, $this->view->member_profile_id, $this->community->id));
                            
                            // Add some generic widgets
                            $this->db->query("INSERT INTO widget_matrix (via_id, profile_id, widget_id) (SELECT ?, ?, id FROM widget_widgets WHERE status='t' AND active='t' AND name IN (SELECT list_array(ARRAY['breadcrumb', 'vmodulemenu', 'addthis'])))", array($this->view->member_profile_id, $this->view->member_profile_id));
                            
                            $num_columns = 2; // Not including fixed column
                            $counter = 0;
                            $col_mods = array();
                            #foreach ($this->db->fetchAll("SELECT module_id, counter FROM module_matrix WHERE module_id > 0 AND active='t' AND via_id=?", array($this->view->member_profile_id)) as $temp) {
                            foreach ($this->db->fetchAll('SELECT x.module_id, x.counter FROM "module_matrix" AS "x" INNER JOIN "module_modules" AS "m" ON x.module_id=m.id LEFT JOIN "system_communities" AS "sc" ON sc.id=? WHERE (m.id > 0) AND (x.active=?) AND (x.via_id=?) ORDER BY idx(sc.default_modules, m.name::text)', array($this->community->id, 't', $this->view->member_profile_id)) as $temp) {
                                $col_mods[$counter % $num_columns][] = 'm-' . $temp->module_id . '-' . $temp->counter;
                                $counter++;
                            }
                            
                            // Setup the space layout - Organize the layout a little
                            try {
                                $this->db->query("UPDATE profile_profiles SET page_layout=?, page_sublayout=?, grid_hd=ARRAY['w-8-1'], grid_ft='{}', grid_cx=ARRAY['w-2-1', 'w-9-1'], grid_c1=?, grid_c2=? WHERE id=?", array('t2', 'g', 
                                    '{"' . (isset($col_mods[0]) ? implode('", "', $col_mods[0]) : '') . '"}',
                                    '{"' . (isset($col_mods[1]) ? implode('", "', $col_mods[1]) : '') . '"}',
                                    $this->view->member_profile_id));
                            } catch (Exception $e) {}
                            
                            $this->db->commit();
                        } catch (Exception $e) {
                            $this->db->rollBack();
                            
                            $this->log->ALERT('Auto-Setup of space modules failed!');
                        }
                    }
                    
                    /* Only add them to any lists and send welcome letter if we haven't already activated their account */
                    if (!$active_value) {
                        // Add them to Email List if there is one
                        if (!$this->_getParam('no_email_add') && isset($this->community->mailchimp_list_id) && $this->community->mailchimp_list_id) {
                            try {
                                require_once $this->vars->APP_PATH . "/library/Other/MailChimp.php";
                                $mc = new MailChimp('d020fc990cfdf20759aba4e1729b80fc-us16'); // SCN MailChimp Account
                                
                                $result = $mc->post('lists/' . $this->community->mailchimp_list_id . '/members/', [
                                    'email_address' => $member->email,
                                    'status' => 'subscribed',
                                    'ip_signup' => $_SERVER['REMOTE_ADDR']
                                ]);
                            } catch (Exception $e) { }
                        }
                        
                        // Welcome email
                        if (isset($member)) {
                            $result = $this->db->fetchRow('SELECT m.id, m.email, m.first_name, m.middle_name, m.last_name, p.name AS username, p.id AS p_id FROM member_members m, profile_profiles p WHERE p.member_id=m.id AND p.base AND m.active AND m.id=?', $member->id);
                            if ($result) {
                                $name = null;
                                if (isset($result->first_name)) {
                                    $name = $result->first_name;
                                    
                                    if (isset($result->middle_name)) {
                                        $name .= ' '.$result->middle_name;
                                    }
                                    if (isset($result->last_name)) {
                                        $name .= ' '.$result->last_name;
                                    }
                                }
                                else {
                                    $name = $result->username;
                                }
                                
                                // Do we need to check for success on this?  Security risk?
                                $this->_sendWelcomeEmail($result->email, $name);
                            }
                        }
                    }
                    
                    // 2-Step Registration
                    if ($member->password == 'NEED_TO_SETUP_VIAME_ACCOUNT') {
                        $result = $this->db->query("UPDATE profile_profiles SET active='f' WHERE id=? AND member_id=?", Array($this->view->member_profile_id, $member->id));
                        return $this->_forward('setup');
                    }
                    
                    // Autologin
                    $this->_helper->ViaMe->loginMember($member->password, $member->password_salt, $member->id, $member->email);
                    
                    #echo $this->view->CM(array(
                    #    'class' => 'cm decorated plain successmessage',
                    #    'hd' => 'Verification Successful',
                    #    'hd2' => 'Thank you for verifying your account',
                    #    'bd' => '<p class="success">You have successfully verified your account and it is now active.</p><p>Welcome to the ' . $this->community->display . ' community!</p><p><a href="' . $this->internal->target->pre . '/member/login/verify/vmpd_nar/1/' . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '') . '">Continue &raquo;</a></p>'
                    #));
                    #return $this->_helper->viewRenderer->setNoRender();
                    
                    $this->view->member = $member;
                    
                    if ($this->_getParam('autogo') && $this->_getParam('redirect')) {
                        return $this->_redirect(
                            $this->_getParam('redirect') . 
                            (
                                ($this->_getParam('vmpd_jli') || preg_match('/vmpd_jli=1/', $this->_getParam('redirect')))
                                ?
                                ''
                                :
                                (preg_match('/\?/', $this->_getParam('redirect')) ? '&vmpd_jli=1' : '?vmpd_jli=1')
                            )
                            
                        
                        );
                        
                        #echo $this->_getParam('redirect') . (preg_match('/\?/', $this->_getParam('redirect')) ? '&vmpd_jli=1' : '?vmpd_jli=1');
                        #return $this->render('verifysuccess');
                    }
                    else {
                        // Variable Output
                        if (is_file($this->view->getScriptPath('register/verifysuccess/' . $this->community->name . '.phtml'))) {
                            return $this->render('verifysuccess/' . $this->community->name);
                        }
                        else {
                            return $this->render('verifysuccess');
                        }
                    }
                } else {
                    // Failure
                    $form->getElement('key')->setValue('');
                    $this->view->formErrors[] = 'We could not verify that information or your account may already have been verified.  Please try signing in or check the information carefully and try again.';
                }
            }
            elseif ($params->email) {
                // Reset the salt before resending the verifcation email?
                
                // Resend verification email
                $result = $this->db->fetchRow("SELECT m.id, m.email, m.first_name, m.middle_name, m.last_name, md5(m.password_salt || m.password) AS key, p.name AS username FROM member_members m, profile_profiles p WHERE p.member_id=m.id AND p.base AND (m.active ISNULL OR password='NEED_TO_SETUP_VIAME_ACCOUNT') AND lower(m.email)=lower(?)", $params->email);
                if ($result) {
                    $name = null;
                    if (isset($result->first_name)) {
                        $name = $result->first_name;
                        
                        if (isset($result->middle_name)) {
                            $name .= ' '.$result->middle_name;
                        }
                        if (isset($result->last_name)) {
                            $name .= ' '.$result->last_name;
                        }
                    }
                    else {
                        $name = $result->username;
                    }
                    
                    // Do we need to check for success on this?  Security risk?
                    $this->_sendVerifyEmail($result->email, $name, $result->id, $result->key);
                    $this->view->formErrors[] = 'A verification email has been sent.  Please check your email.';
                }
                else {
                    $this->view->formErrors[] = 'No account found requiring verification.';
                }
            }
        }
        else {
            foreach (array('email', 'key', 'id') as $key) {
                if ($this->_getParam($key)) {
                    $form->getElement($key)->setValue($this->_getParam($key));
                }
            }
        }
        
        $this->view->form = $form;
    }
    
    
    public function setupAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Setup', 'PREPEND');
        
        // Load the Form
        require_once dirname(__FILE__) . '/includes/members_form.php';
        
        // Have to change the id to vid because vivin breaks
        if ($this->_getParam('id')) {
            $this->_setParam('vid', $this->_getParam('id'));
            $this->_setParam('id', null);
        }
        
        $form->setMethod('post');
        $form->setAction($this->target->pre . '/member/register/setup/');
        
        // Remove unneccessary elements
        foreach (array('first_name', 'middle_name', 'last_name', 'phone', 'gender', 'dob', 'dob_month', 'dob_day', 'dob_year', 'postal_code', 'email', 'timezone', 'country', 'currency', 'language') as $element) {
            $form->removeElement($element);
        }
        $form->addDisplayGroup(array('username', 'password', 'password_confirm'), 'login', array('legend' => 'Login Information'));
        
        // Things that need to be done outside of the cache due to parameters
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => $this->_getParam('vmpd_nar')));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addElement('Hidden', 'autogo', array('value' => $this->_getParam('autogo')));
        
        $form->addElement('Hidden', 'vid', array('value' => $this->_getParam('vid')));
        $form->addElement('Hidden', 'email', array('value' => $this->_getParam('email')));
        $form->addElement('Hidden', 'key', array('value' => $this->_getParam('key')));
        
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect', 'autogo', 'vid', 'email', 'key'), 'hidden');
        
        
        $password_confirm_validator = new Zend_Validate_Identical($this->_getParam('password'));
        $password_confirm_validator->setMessages(array(
            Zend_Validate_Identical::NOT_SAME => 'Password was not confirmed.', Zend_Validate_Identical::MISSING_TOKEN => 'Password was not confirmed.'
        ));
        $form->getElement('password_confirm')->addValidator($password_confirm_validator);
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Complete Account Setup', 'class' => 'big', 'ignore' => true, 'order' => 999));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        $this->view->formErrors = array();
        
        if (($this->_getParam('email') || $this->_getParam('vid')) && $this->_getParam('key')) {
            if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
                $params = (object) $form->getValues();
                if (($params->vid || $params->email) && $params->key) {
                    if ($params->email) {
                        $whichID = $this->db->quoteInto('lower(email)=lower(?)', $params->email);
                        #$who = $params->email;
                    } else {
                        $whichID = $this->db->quoteInto('id=?', $params->vid);
                        #$who = $params->id;
                    }
                    
                    try {
                        $result = $this->db->query("UPDATE member_members SET password=? WHERE $whichID AND md5(password_salt || password)=?", Array(md5($params->password), $params->key));
                        $member = $this->db->fetchRow("SELECT * FROM member_members WHERE $whichID AND password=?", Array(md5($params->password)));
                    } catch (Exception $e) {}
                    
                    if ($result->rowCount()) {
                        if ($this->view->member_profile_id = $this->db->fetchOne("SELECT MIN(id) FROM profile_profiles WHERE member_id=?", $member->id)) {
                            // Update Profile
                            try {
                                $result = $this->db->query("UPDATE profile_profiles SET active='t', name=? WHERE id=?", Array($params->username, $this->view->member_profile_id));
                            } catch (Exception $e) {}
                                
                            if ($result->rowCount()) {
                                // Autologin
                                $this->_helper->ViaMe->loginMember($member->password, $member->password_salt, $member->id, $member->email);
                                
                                $this->view->member = $member;
                                
                                if ($this->_getParam('autogo') && $this->_getParam('redirect')) {
                                    return $this->_redirect(
                                        $this->_getParam('redirect') . 
                                        (
                                            ($this->_getParam('vmpd_jli') || preg_match('/vmpd_jli=1/', $this->_getParam('redirect')))
                                            ?
                                            ''
                                            :
                                            (preg_match('/\?/', $this->_getParam('redirect')) ? '&vmpd_jli=1' : '?vmpd_jli=1')
                                        )
                                        
                                    
                                    );
                                }
                                else {
                                    // Variable Output
                                    if (is_file($this->view->getScriptPath('register/verifysuccess/' . $this->community->name . '.phtml'))) {
                                        return $this->render('verifysuccess/' . $this->community->name);
                                    }
                                    else {
                                        return $this->render('verifysuccess');
                                    }
                                }
                            }
                            else {
                                echo $this->view->CM(array(
                                    'class' => 'cm decorated plain errormessage',
                                    'hd' => 'Setup Failed',
                                    'hd2' => 'Profile update error.',
                                    'bd' => '<p class="error">An error has occurred setting up your account.</p><p>An unexpected error has occurred updating your profile and has caused your setup process to not complete.  Your account profile not be updated.  Please try your request again.</p>'
                                ));
                            
                                return $this->_helper->viewRenderer->setNoRender();
                            }
                        }
                        else {
                            $this->view->formErrors[] = 'An error occurred trying to setup your profile.  Please try again.';
                        }
                    } else {
                        // Failure
                        $this->view->formErrors[] = 'We could not properly setup that account with that information.  Please try again.';
                    }
                }
            }
        }
        else {
            // Redirect To Verify
            $this->_redirect('/member/login/p/vmpd_nar/1/' . (isset($params->redirect)  ? '?redirect=' . urlencode($params->redirect) : ''));
        }
        
        $this->view->form = $form;
    }
    
    
    private function _sendVerifyEmail ($email, $name, $id, $key)
    {
        if ($email) {
            #$internal = new StdClass;
            #$internal->vars = $this->vars;
            #$internal->config = $this->config;
            #$internal->community = $this->community;
            
            if ($name == 'NTSVA') {
                $name = '';
                if (isset($this->community->display) && $this->community->display) {
                    $name = $this->community->display . ' ';
                }
                $name .= 'Member';
            }
            
            $partial_array = array('email' => $email, 'name' => $name, 'id' => $id, 'key' => $key, 'internal' => $this->internal);
            
            // Variable Output
            if (is_file($this->view->getScriptPath('register/emails/verify/' . $this->community->name . '.phtml'))) {
                $temp = $this->view->partial('register/emails/verify/' . $this->community->name . '.phtml', $partial_array);
            }
            else {
                $temp = $this->view->partial('register/emails/verify.phtml', $partial_array);
            }
            
            #$this->_helper->ViaMe->sendEmail($email, $name, 'Please Verify Your ' . $this->community->display . ' Member Account!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
            $this->_helper->ViaMe->sendEmail($email, $name, 'Your New ' . $this->community->display . ' Account Requires Verification!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
    
    private function _sendWelcomeEmail ($email, $name)
    {
        if ($email) {
            #$internal = new StdClass;
            #$internal->vars = $this->vars;
            #$internal->config = $this->config;
            #$internal->community = $this->community;
            
            if ($name == 'NTSVA') {
                $name = '';
                if (isset($this->community->display) && $this->community->display) {
                    $name = $this->community->display . ' ';
                }
                $name .= 'Member';
            }
            
            $partial_array = array('email' => $email, 'name' => $name, 'internal' => $this->internal);
            
            // Variable Output
            if (is_file($this->view->getScriptPath('register/emails/welcome/' . $this->community->name . '.phtml'))) {
                $temp = $this->view->partial('register/emails/welcome/' . $this->community->name . '.phtml', $partial_array);
            }
            else {
                $temp = $this->view->partial('register/emails/welcome.phtml', $partial_array);
            }
            
            $this->_helper->ViaMe->sendEmail($email, $name, 'Welcome to the ' . $this->community->display . ' Community!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
    }
}