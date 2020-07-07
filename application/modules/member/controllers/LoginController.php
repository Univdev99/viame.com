<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_LoginController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch()
    {
        // No need to log people in that are already logged in
        if (isset($this->member)) {
            $this->_autoredirect('/');
        }
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Sign In', 'PREPEND');
        
        // Not going to use the built-in Zend_Auth_Storage_Session - Too limited
        $authNamespace = new Zend_Session_Namespace($this->config->auth->namespace);
        
        // Too Many Failed Login Attempts - No More Tries
        if (($authNamespace->loginAttempts >= $this->config->auth->failedattemptmax) && (time() < ($authNamespace->lastFailedLoginAttempt + $this->config->auth->failedattempttimeout))) {
            $this->view->headTitle('Too Many Failures', 'PREPEND');
            $this->log->EMERG("Too many failed login attempts.");
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Login Failure',
                'hd2' => 'Please try again later',
                'bd' => '<p class="error">An error has occurred. You have too many failed sign in attempts.</p><p>You have attempted to sign in and failed too many times.  You are welcome to try again in a couple of hours.</p><p>If you do not remember your password, <a href="/member/reset/">click here</a> to go to the <a href="/member/reset/">password reset page</a>.</p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/members_form.php';
        // Remove Elements Early to Reduce Iteration
        unset($form_config['elements']['first_name']);
        unset($form_config['elements']['middle_name']);
        unset($form_config['elements']['last_name']);
        unset($form_config['elements']['phone']);
        unset($form_config['elements']['gender']);
        unset($form_config['elements']['dob']);
        unset($form_config['elements']['dob_month']);
        unset($form_config['elements']['dob_day']);
        unset($form_config['elements']['dob_year']);
        unset($form_config['elements']['postal_code']);
        unset($form_config['elements']['username']);
        unset($form_config['elements']['password_confirm']);
        unset($form_config['elements']['timezone']);
        unset($form_config['elements']['country']);
        unset($form_config['elements']['currency']);
        unset($form_config['elements']['language']);
        
        $form_config['attribs']['name'] = 'login_form';
        $form_config['attribs']['id'] = 'login_form';
        $form_config['attribs']['class'] = 'form';
        $form_config['attribs']['onsubmit'] = 'regula.bind(); return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.login_form] }) && YAHOO.viame.dubsub.check(this));';
        unset($form_config['attribs']['data-constraints']);
        unset($form_config['elements']['password'][1]['description']);
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        $form->setMethod('post');
        $form->setAction('/member/login/');
        
        $form->setElementFilters(array('StringTrim'));
        
        if (isset($this->cookie->{$this->config->auth->cookie_name->login}) && !$this->_getParam('email')) {
            $form->getElement('email')
                ->setValue($this->cookie->email)
                ->setDecorators( array('ViewHelper', array('Errors'), array('HtmlTag', array('tag' => 'dd', 'style' => 'font-weight: bold;')), array('Label', array('tag' => 'dt'))) )
                ->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_ISSET_HIDDEN);
            $form->addElement('Text', 'notmemessage', array(
                #'value' => '<a href="/member/logout/full/vmpd_far/1/?redirect=' . urlencode($this->getRequest()->getServer('SCRIPT_URL') . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '')) . '">Not my email address</a>',
                'value' => '<a href="/member/logout/full/vmpd_far/1/?redirect=' . urlencode('/member/login/' . ($this->_getParam('signup_entrance') ? 'p/signup_entrance/' . $this->_getParam('signup_entrance') . '/' : '') . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '')) . '">Not my email address</a>',
                'order' => 41,
                'ignore' => true
            ));
            $form->getElement('notmemessage')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
            
            $this->view->signup_link = '/member/logout/full/vmpd_far/1/?redirect=/member/register/' . ($this->_getParam('signup_entrance') ? 'p/signup_entrance/' . $this->_getParam('signup_entrance') . '/' : '') . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '');
        }
        else {
            $this->view->signup_link = '/member/register/' . ($this->_getParam('signup_entrance') ? 'p/signup_entrance/' . $this->_getParam('signup_entrance') . '/' : '') . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '');
        }
        
        #if (isset($this->cookie->persist) && $this->cookie->persist) {
        #    $form->addElement('hidden', 'persistent', array('value' => 1));
        #}
        #else {
            $form->addElement('Checkbox', 'persistent', array('label' => 'Remember Me', 'description' => 'Keep me signed in until I sign out.<br />Uncheck if on a shared or public computer.', 'value' => (isset($this->cookie->persist) ? $this->cookie->persist : true), 'order' => 995));
            $form->getElement('persistent')->getDecorator('description')->setEscape(false);
        #}
        
        $form->addElement('Submit', 'submit', array('label' => 'Sign In', 'class' => 'big', 'ignore' => true, 'order' => 1000));
        
        
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => (substr_count($this->_getParam('redirect'), '?') > 1 ? str_replace('&', '%26', $this->_getParam('redirect')) : $this->_getParam('redirect'))));
        
        $form->addDisplayGroup(array('email', 'notmemessage', 'password', 'persistent'), 'login');
        $form->addDisplayGroup(array('submit'), 'buttons');
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');
        
        
        /*
        $form->removeDecorator('HtmlTag');
        
        $form->setElementDecorators(array('ViewHelper', 'Errors', array('Label'), 'HtmlTag'));
        #$form->setElementDecorators(array('ViewHelper', array('Label'), 'HtmlTag'));
        
        #$form->addDisplayGroup(array('email', 'password', 'persistent'), 'login_fs', array('legend' => 'Login Information'));
        #$form->addDisplayGroup(array('submit'), 'submit_fs', array('legend' => 'Submit'));
        #$form->setDisplayGroupDecorators(array('FormElements', 'FieldSet'));
        
        // Individual Element Overrides
        $form->getElement('email')->setLabel($form->getElement('email')->getLabel() . ':')->setAttrib('style', 'width: 98%;');
        $form->getElement('password')->setLabel($form->getElement('password')->getLabel() . ':')->setAttrib('style', 'width: 98%;');
        $form->getElement('persistent')->setLabel('Keep me signed in for 2 weeks.')->setDecorators(array('ViewHelper', array('Label', array('placement' => 'append')), array('HtmlTag', array('style' => 'font-size: xx-small;'))));
        $form->getElement('submit')->setDecorators(array('ViewHelper', array('HtmlTag', array('style' => 'text-align: center;'))));
        */
        
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            // Logging In
            $params = (object) $form->getValues();

            $email = $password = null;
            if ($params->email && $params->password) {
                $email = $params->email;
                $password = $params->password;
            }
            
            if ($email && $password) {
                // Load up the member object
                $member_members = new member_models_members();
                #$member = $member_members->fetchRow($member_members->select(array('*', "date('now') - date(updated) AS last_updated"))->where('email=?', $email)->where('password=?', md5($password)));
                $member = $this->db->fetchRow($this->db->select()->from('member_members', array('*', "date('now') - date(updated) AS last_updated"))->where('lower(email)=lower(?)', $email)->where('password=?', md5($password)));
                
                if ($member) {
                    if ($member->active) {
                        // Reset Any Login Attempt Failures
                        Zend_Session::namespaceUnset($this->config->auth->namespace);
                        // Blank the referrers
                        #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->community_referrer_id);
                        #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile_referrer_id);
                        
                        // Can optionally change the password salt and update the db to change the password every 30 days
                        if ($member->last_updated > 30) {
                            try {
                                $new_password_salt = md5(uniqid(rand(), true));
                                $member_members->update(array('password_salt' => $new_password_salt), $member_members->getAdapter()->quoteInto('id = ?', $member->id));
                                $member->password_salt = $new_password_salt;
                            } catch (Exception $e) {}
                        }
                        
                        // 2-Step Registration
                        #if ($member->password == 'NEED_TO_SETUP_VIAME_ACCOUNT') {
                        #    return $this->_forward('setup', 'register', null, null);
                        #}
                        
                        $this->_helper->ViaMe->loginMember($member->password, $member->password_salt, $member->id, $member->email, $params->persistent);
                        
                        // Redirect To Verify
                        $this->_redirect('/member/login/verify/vmpd_nar/1/' . (isset($params->redirect)  ? '?redirect=' . urlencode($params->redirect) : ''));
                    }
                    elseif ($member->active === false) {
                        // Deactivated User
                        $this->view->headTitle('Account Deactivated', 'PREPEND');
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain errormessage',
                            'hd' => 'Account Deactivated',
                            'hd2' => 'Your account has been deactivated',
                            'bd' => '<p class="error">An error has occurred. Your account has been deactivated.</p><p>The account your are trying to access has been deactivated.  Accounts are deactivated due to abuse or prolonged inactivity.</p><p>If you would like this account to be reactivated, please let us know by emailing us at <a href="mailto:' . ($this->community->email ? $this->community->email : $this->config->admin->email) .'">' . ($this->community->email ? $this->community->email : $this->config->admin->email) . '</a>.  Accounts are reactivated at our discretion and on a case by case basis.</p>'
                        ));
                        return $this->_helper->viewRenderer->setNoRender();
                    }
                    else {
                        // Not Activated User
                        $this->view->headTitle('Account Not Verified', 'PREPEND');
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain errormessage',
                            'hd' => 'Verification Required',
                            'hd2' => 'Your account has not been activated yet',
                            'bd' => '<p class="error">An error has occurred. Your account has not been activated yet.</p><p>The account you are trying to access has been found, but it has not yet been activated.  A verification email was sent to the email address you used to create your account.  In the email, you will find instructions for activating your account. Please follow the instructions in the email you received.</p><p>If you did not receive the verification email, <a href="/member/register/verify/vmpd_fp/1/vmpd_nar/1/?email=' . (isset($this->params->email) ? $this->params->email : '') . ($this->_getParam('redirect') ? '&redirect=' . $this->_getParam('redirect') : '') . '">click here to have it resent</a>.</p><p>If you received the email, you can go to the <a href="/member/register/verify/vmpd_nar/1/?email=' . (isset($this->params->email) ? $this->params->email : '') . ($this->_getParam('redirect') ? '&redirect=' . $this->_getParam('redirect') : '') . '">verification page</a> to finish activating your account.</p>'
                        ));
                        return $this->_helper->viewRenderer->setNoRender();
                    }
                }
                else {
                    // Failed Login Attempt
                    $authNamespace->loginAttempts++;
                    $authNamespace->lastFailedLoginAttempt = time();
                    
                    $this->log->EMERG("Failed login attempt ($email : $password).");
                    
                    $this->view->formErrors = array('Invalid Email and/or Password.');
                }
            }
        }
        elseif ($this->_getParam('email')) {
            $form->setDefault('email', $this->_getParam('email'));
        }
        
        if ($this->_getParam('errorcode') == '401') {
            $this->getResponse()->setHttpResponseCode(401);
        }
        $this->view->form = $form;
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        if ($this->_getParam('simple_content')) {
            return $this->render('simple');
        }
    }
    
    public function verifyAction()
    {
        $this->view->headTitle('Cookies Required', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        if (isset($this->member)) {
            $this->log->ALERT("Successful login.");
            
            return $this->_autoredirect('/');
        }
        
        echo $this->view->CM(array(
            'class' => 'cm decorated plain errormessage',
            'hd' => 'Cookies Required',
            'hd2' => 'Please turn on browser cookies',
            'bd' => '<p class="error">An error has occurred. You must have cookies enabled to properly sign in.</p>' .
                '<p>This site utilizes cookies to store user information and preferences. This featured must be enabled to properly sign in and utilize the site. Turn on cookies in your browser\'s preferences or options and try signing in again. If your browser does not support cookies, please try updating to a more current browser.</p><p>After enabling cookies, try signing in again by going to the <a href="/member/login/' . ($this->_getParam('redirect') ? '?redirect=' . $this->_getParam('redirect') : '') . '">sign in page</a>.</p>'
        ));
        return $this->_helper->viewRenderer->setNoRender();
    }
}