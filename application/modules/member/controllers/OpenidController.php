<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_OpenidController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch()
    {
        if ($this->getRequest()->getActionName() == 'login') {
            $this->_memberDefined = false;
        }
        
        parent::preDispatch();
    }
    
    
    public function loginAction()
    {
        // No need to log people in that are already logged in
        #if (isset($this->member)) {
        #    $this->_autoredirect('/');
        #}
        
        // Session
        $sli_session = new Zend_Session_Namespace('SLISession');
        if (!isset($sli_session->redirect) && $this->_getParam('redirect')) { $sli_session->redirect = $this->_getParam('redirect'); }
        
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Sign-In', 'PREPEND');
        $this->view->headTitle('OpenID', 'PREPEND');
        
        require_once $this->vars->APP_PATH . "/library/Other/LightOpenID.php";
        $openid = new LightOpenID($this->vars->host_name . '.' . $this->vars->domain_name);
        $openid->required = array('contact/email', 'namePerson', 'namePerson/first', 'namePerson/middle', 'namePerson/last', 'namePerson/friendly', );
        $openid->optional = array('person/gender', 'birthDate', 'birthDate/birthYear', 'birthDate/birthMonth', 'birthDate/birthDay', 'contact/postalCode/home', 'pref/timezone', 'contact/country/home', 'pref/language');
        
        $providers = array(
            'google' => 'https://www.google.com/accounts/o8/id',
            'yahoo' => 'https://me.yahoo.com',
            'aol' => 'https://openid.aol.com'
        );
        
        if(!$openid->mode) {
            if ($this->_getParam('openid_url') || ($this->_getParam('provider') && (array_key_exists($this->_getParam('provider'), $providers)))) {
                // Send them away to get authenticated
                if ($this->_getParam('openid_url')) {
                    $openid->identity = $this->_getParam('openid_url');
                }
                elseif (array_key_exists($this->_getParam('provider'), $providers)) {
                    $openid->identity = $providers[$this->_getParam('provider')];
                }
                
                try {
                    $this->_redirect($openid->authUrl());
                } catch(ErrorException $e) {
                    self::displayError();
                }
            }
            else {
                // Won't ever get here (redirected away) unless error or no auth
                
                // Display OpenID Form
                $form = new Zend_Form();
                $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
                $form->setOptions(array(
                    'attribs' => array(
                        'name' => 'openid_form',
                        'id' => 'openid_form',
                        'method' => 'post',
                        'action' => '/member/openid/login/',
                        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.openid_form] }) && YAHOO.viame.dubsub.check(this));'
                    ),
                    'elements' => array(
                        'openid_url' => array('Text', array(
                            'label' => 'Your OpenID URL',
                            'description' => '(e.g. http://username.myopenid.com)',
                            'required' => true,
                            'maxlength' => 256,
                            'class' => 'regula-validation',
                            'data-constraints' => '@Required(label="OpenID URL", message="The {label} field cannot be empty.", groups=[openid_form])',
                            'order' => 5
                        ))
                    )
                ));
                
                $form->addElement('Submit', 'submit', array('label' => 'Sign In', 'class' => 'big', 'ignore' => true, 'order' => 1000));
        
        
                $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
                $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
                $form->addElement('Hidden', 'redirect', array('value' => (substr_count($this->_getParam('redirect'), '?') > 1 ? preg_replace('/&/', '%26', $this->_getParam('redirect')) : $this->_getParam('redirect'))));
                
                $form->addDisplayGroup(array('openid_url'), 'login');
                $form->addDisplayGroup(array('submit'), 'buttons');
                $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');
                
                $this->view->form = $form;
                
                $this->_helper->ViaMe->setSubLayout('default');
        
                if ($this->_getParam('simple_content')) {
                    return $this->render('simple');
                } else {
                    $this->render('login');
                }
            }
        }
        elseif($openid->mode == 'cancel') {
            // User Canceled Authentication
            if (isset($sli_session->redirect)) { $urltogoto = $sli_session->redirect; }
            Zend_Session::namespaceUnset('SLISession');
            $this->_redirect('/member/login/p/vmpd_nar/1/' . (isset($urltogoto) ? '?redirect=' . urlencode($urltogoto) : ''));
        } else {
            if ($openid->validate()) {
                // User Has Authed Successfully - Get the identity from the results
                $identity = preg_replace('/\#.*/', '', $openid->identity);
                
                $select = $this->db->select()
                    ->from(array('m' => 'member_members'), array('id', 'email', 'password', 'password_salt'))
                    ->join(array('msl' => 'member_social_logins'), 'm.id=msl.member_id', array())
                    ->where('m.active=?', 't')
                    ->where('msl.active=?', 't')
                    ->where('msl.provider=?', 'openid')
                    ->where('msl.id=?', $identity);
                
                if ($results = $this->db->fetchRow($select)) {
                    // Found User - Log'em in...
                    $this->VMAH->loginMember ($results->password, $results->password_salt, $results->id, $results->email);
                    
                    if (isset($sli_session->redirect)) { $urltogoto = $sli_session->redirect; }
                    Zend_Session::namespaceUnset('SLISession');
                    if (isset($urltogoto)) { $this->_redirect($urltogoto); }
                    else { $this->_autoredirect('/'); }
                } else {
                    // Logged In Already - Just Associate
                    if (isset($this->member)) {
                        // Attach
                        try {
                            $this->db->insert('member_social_logins', array(
                                'member_id' => $this->member->id,
                                'provider' => 'openid',
                                'id' => $identity
                            ));
                        } catch (Exception $e) {
                            self::displayError();
                            return $this->_helper->viewRenderer->setNoRender();
                        }
                            
                        if (isset($sli_session->redirect)) { $urltogoto = $sli_session->redirect; }
                        Zend_Session::namespaceUnset('SLISession');
                        if (isset($urltogoto)) { $this->_redirect($urltogoto); }
                        else { $this->_autoredirect('/'); }
                    }
                    
                    
                    // Not yet associated identity
                    $attributes = $openid->getAttributes();
                    if (isset($attributes['contact/email'])) {
                        $selectByEmail = $this->db->select()
                            ->from(array('member_members'), array('id', 'email', 'password', 'password_salt', 'active'))
                            ->where('active ISNULL OR active=?', 't')
                            ->where('lower(email)=lower(?)', $attributes['contact/email'])
                            ->limit(1);
                    }
                    
                    if (isset($selectByEmail) && ($memberByEmail = $this->db->fetchRow($selectByEmail))) {
                        // Second, lets see if we have a matching email address
                        
                        // Attach
                        try {
                            $this->db->insert('member_social_logins', array(
                                'member_id' => $memberByEmail->id,
                                'provider' => 'openid',
                                'id' => $identity
                            ));
                        } catch (Exception $e) { }
                        
                        if (!isset($memberByEmail->active) || is_null($memberByEmail->active)) {
                            // Not confirmed user - Confirm and do any confirm functions
                            $this->db->update('member_members', array('active' => 't'), $this->db->quoteInto('id=?', $memberByEmail->id));
                        }
                        
                        // Login In
                        $this->VMAH->loginMember ($memberByEmail->password, $memberByEmail->password_salt, $memberByEmail->id, $memberByEmail->email);
                        
                        if (isset($sli_session->redirect)) { $urltogoto = $sli_session->redirect; }
                        Zend_Session::namespaceUnset('SLISession');
                        if (isset($urltogoto)) { $this->_redirect($urltogoto); }
                        else { $this->_autoredirect('/'); }
                    }
                    else {
                        // Third, let's prepare them for a registration
                        $sli_session->identity = $identity;
                        #Zend_Debug::Dump($identity);
                        
                        $regurl = '/Register/s/member/register/p/signup_entrance/OpenID/';
                        $params = array();
                        $params[] = 'supershortform=1';
                        $params[] = 'sli=openid';
                        
                        if (isset($attributes['contact/email']) && $attributes['contact/email']) {
                            $params[] = 'vmpd_fp=1';
                            $params[] = 'email=' . urlencode($attributes['contact/email']);
                        }
                        if (isset($attributes['namePerson']) && $attributes['namePerson']) {
                            #$params[] = 'fullname=' . urlencode($attributes['namePerson']);
                            $tokens = preg_split("/\s+/", $attributes['namePerson']);
                            if (!(isset($attributes['namePerson/first']) && $attributes['namePerson/first'])) { $params[] = 'first_name=' . urlencode(array_shift($tokens)); }
                            else { array_shift($tokens);  }
                            
                            if (!(isset($attributes['namePerson/last']) && $attributes['namePerson/last'])) { $params[] = 'last_name=' . urlencode(array_pop($tokens)); }
                            else { array_pop($tokens);  }
                            
                            if (!(isset($attributes['namePerson/middle']) && $attributes['namePerson/middle']) && count($tokens)) { $params[] = 'middle_name=' . urlencode(implode(' ', $tokens)); }
                                
                            #$params[] = 'first_name=' . urlencode(array_shift($tokens));
                            #$params[] = 'last_name=' . urlencode(array_pop($tokens));
                            #$params[] = 'middle_name=' . urlencode(implode(' ', $tokens));
                        }
                        if (isset($attributes['namePerson/first']) && $attributes['namePerson/first']) { $params[] = 'first_name=' . urlencode($attributes['namePerson/first']); }
                        if (isset($attributes['namePerson/middle']) && $attributes['namePerson/middle']) { $params[] = 'middle_name=' . urlencode($attributes['namePerson/middle']); }
                        if (isset($attributes['namePerson/last']) && $attributes['namePerson/last']) { $params[] = 'last_name=' . urlencode($attributes['namePerson/last']); }
                        if (isset($attributes['person/gender']) && $attributes['person/gender']) { $params[] = 'gender=' . urlencode($attributes['person/gender']); }
                        if (isset($attributes['contact/postalCode/home']) && $attributes['contact/postalCode/home']) { $params[] = 'postal_code=' . urlencode($attributes['contact/postalCode/home']); }
                        if ((isset($attributes['birthDate/birthYear']) && $attributes['birthDate/birthYear']) && (isset($attributes['birthDate/birthMonth']) && $attributes['birthDate/birthMonth']) && (isset($attributes['birthDate/birthDay']) && $attributes['birthDate/birthDay'])) {
                            $params[] = 'dob_year=' . urlencode($attributes['birthDate/birthYear']);
                            $params[] = 'dob_month=' . urlencode($attributes['birthDate/birthMonth']);
                            $params[] = 'dob_day=' . urlencode($attributes['birthDate/birthDay']);
                        }
                        elseif (isset($attributes['birthDate']) && $attributes['birthDate']) { $params[] = 'dob=' . urlencode($attributes['birthDate']); }
                        
                        if (isset($attributes['namePerson/friendly']) && $attributes['namePerson/friendly']) { $params[] = 'username=' . urlencode($attributes['namePerson/friendly']); }
                        else {
                            $tmpUsername = '';
                            if (isset($attributes['namePerson']) && $attributes['namePerson']) { $tmpUsername = $attributes['namePerson']; }
                            elseif (isset($attributes['namePerson/first']) && $attributes['namePerson/first']) {
                                $tmpUsername = $attributes['namePerson/first'];
                                if (isset($attributes['namePerson/middle']) && $attributes['namePerson/middle']) { $tmpUsername .= ' ' . $attributes['namePerson/middle']; }
                                if (isset($attributes['namePerson/last']) && $attributes['namePerson/last']) { $tmpUsername .= ' ' . $attributes['namePerson/last']; }
                            }
                            elseif (isset($attributes['contact/email']) && $attributes['contact/email']) { $tmpUsername = 'username=' . urlencode(preg_replace('/\@.*/', '', $attributes['contact/email'])); }
                            
                            if ($tmpUsername) { $params[] = 'username=' . urlencode($tmpUsername); }
                        }
                        
                        if (isset($attributes['pref/timezone']) && $attributes['pref/timezone']) { $params[] = 'timezone=' . urlencode($attributes['pref/timezone']); }
                        if (isset($attributes['contact/country/home']) && $attributes['contact/country/home']) { $params[] = 'country=' . urlencode($attributes['contact/country/home']); }
                        # language - 'pref/language'
                        
                        if (isset($sli_session->redirect)) { $params[] = 'redirect=' . urlencode($sli_session->redirect); }
                        
                        if (count($params)) { $regurl .= '?' . implode('&', $params); }
                        
                        #Zend_Debug::Dump($regurl);
                        $this->_redirect($regurl);
                    }
                }
            }
            else {
                // Not validated or forged
                Zend_Session::namespaceUnset('SLISession');
                self::displayError();
            }
            
            /*
            echo 'User ' . ($openid->validate() ? $openid->identity . ' has ' : 'has not ') . 'logged in.';
            Zend_Debug::Dump($openid);
            Zend_Debug::Dump($openid->getAttributes());
            Zend_Debug::Dump($sli_session);
            */
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function deleteAction()
    {
        echo 'delete an openid';
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    private function displayError() {
        $sli_session = new Zend_Session_Namespace('SLISession');
        $redirect = '/';
        if (isset($sli_session->redirect)) {
            $redirect = $sli_session->redirect;
        }
        elseif($this->_getParam('redirect')) {
            $redirect = $this->_getParam('redirect');
        }
        
        echo $this->view->CM(array(
            'class' => 'cm decorated plain errormessage',
            'hd' => 'OpenID Error',
            'bd' => <<<EOM
<p class="error">OpenID Has Failed</p><p>There was a problem with the OpenID authentication.  Please <a href="javascript:history.back();">go back</a> and try your request again or try an <a href="/member/openid/login/?redirect=$redirect">alternate OpenID method</a>. Or, you can <a href="/member/login/?redirect=$redirect">sign in</a> or <a href="/member/register/?redirect=$redirect">register</a> using our normal methods.</p>
<p><a href="/member/login/?redirect=$redirect">Sign-In</a>, <a href="/member/register/?redirect=$redirect">Register</a>, or <a href="/member/openid/login/?redirect=$redirect">Try Another OpenID Method</a></p>
<p><a href="$redirect">Continue &raquo;</a></p>
EOM
        ));
    }
}


/*
array('contact/email', 'namePerson', 'namePerson/first', 'namePerson/middle', 'namePerson/last', 'namePerson/friendly', );
array('person/gender', 'birthDate', 'birthDate/birthYear', 'birthDate/birthMonth', 'birthDate/birthDay', 'contact/postalCode/home', 'pref/timezone', 'contact/country/home', 'pref/language');
                
redirect
openid_url


first_name
last_name
username
email
password
password_confirm

tos
captcha



middle_name
gender
dob_month
dob_day
dob_year
postal_code
timezone
country
currency
language
*/