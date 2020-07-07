<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_OauthController extends ViaMe_Controller_Action
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
        #// No need to log people in that are already logged in
        #if (isset($this->member)) {
        #    $this->_autoredirect('/');
        #}
        
        // Session
        $sli_session = new Zend_Session_Namespace('SLISession');
        if (!isset($sli_session->redirect) && $this->_getParam('redirect')) { $sli_session->redirect = $this->_getParam('redirect'); }
        
        $this->view->headTitle('Member', 'PREPEND');
        $this->view->headTitle('Sign-In', 'PREPEND');
        $this->view->headTitle('OAuth', 'PREPEND');
        
        require_once $this->vars->APP_PATH . "/library/Other/OAuth2Client.php";
        
        $return_url = $_SERVER['SCRIPT_URI'];
        
        $providers = array(
            'google-oauth2' => array(
                'consumer_id' => '502550519162.apps.googleusercontent.com',
                'consumer_key' => 'O3hpXUccHVjfdSDov0GuxpAK',
                'auth_endpoint' => 'https://accounts.google.com/o/oauth2/auth',
                'token_endpoint' => 'https://accounts.google.com/o/oauth2/token',
                'api_endpoint' => 'https://www.googleapis.com/oauth2/v1/userinfo',
                #'api_params' => array(),
                #'scope' => 'openid email',
                'scope' => 'https://www.googleapis.com/auth/userinfo.email https://www.googleapis.com/auth/userinfo.profile'
                #'access_type' => 'offline',
                #'prompt' => 'consent',
            ),
            'facebook-oauth2' => array(
                'consumer_id' => '540323072676041',
                'consumer_key' => '102045174d246fbb831e74719ebba340',
                'auth_endpoint' => 'https://www.facebook.com/dialog/oauth',
                'token_endpoint' => 'https://graph.facebook.com/oauth/access_token',
                'api_endpoint' => 'https://graph.facebook.com/me',
                'scope' => 'email,user_birthday'
            ),
            'linkedin-oauth2' => array(
                #'consumer_id' => 's2onon5bdt8c',
                'consumer_id' => '75p1cx30x4ai1s',
                #'consumer_key' => 'omiWgOhdeJEILiWg',
                'consumer_key' => 'DDLazx9qKwllBBpm',
                'auth_endpoint' => 'https://www.linkedin.com/uas/oauth2/authorization',
                'token_endpoint' => 'https://www.linkedin.com/uas/oauth2/accessToken',
                'api_endpoint' => 'https://api.linkedin.com/v1/people/~:(id,email-address,first-name,last-name,location,picture-url)',
                'api_params' => array('format' => 'json', 'oauth2_access_token' => '{TOKEN}'),
                #'scope' => 'r_emailaddress r_basicprofile r_fullprofile r_contactinfo'
                'scope' => 'r_emailaddress r_basicprofile'
            ),
            'twitter-oauth1' => array(
                #'consumer_id' => 'aXPXqEZHAuxFzg12l9ww',
                'consumer_id' => 'LzcA4kjDPuy6rKBzHxWcmdONC',
                #'consumer_key' => 'KAszBcmxlMnEXrpE0fzmxB9wws6YhXkuG0ZxeY1sfw',
                'consumer_key' => 'VM5cbjHG5BtQfkCmdbnW0Z3emCB4KpjoVAZrovisYc8zRkUahc',
                'req_endpoint' => 'https://api.twitter.com/oauth/request_token' . '?oauth_callback=' . urlencode($return_url),
                'auth_endpoint' => 'https://api.twitter.com/oauth/authenticate',
                'token_endpoint' => 'https://api.twitter.com/oauth/access_token',
                'api_endpoint' => 'https://api.twitter.com/1.1/account/verify_credentials.json',
            )
        );
        
        $selected_provider = ($this->_getParam('state') ? $this->_getParam('state') : (isset($sli_session->state) && $sli_session->state ? $sli_session->state : $this->_getParam('provider')));
        
        if (isset($selected_provider) && $selected_provider && array_key_exists($selected_provider, $providers)) {
            $provider = $providers[$selected_provider];
            if (preg_match('/1$/', $selected_provider)) {
                // OAuth1 Client
                $client = new OAuth($provider['consumer_id'], $provider['consumer_key']);
            }
            else {
                // OAuth2 Client
                $client = new OAuth2Client($provider['consumer_id'], $provider['consumer_key']);
                $client->setCurlOption(CURLOPT_CONNECTTIMEOUT, 15);
            }
            
            if ($this->_getParam('state') || (isset($sli_session->state) && $sli_session->state)) {
                if (isset($sli_session->state) && $sli_session->state) {
                    $sli_session->state = null;
                }
                
                if ($this->_getParam('code') || (preg_match('/1$/', $selected_provider) && $this->_getParam('oauth_token'))) {
                    if (preg_match('/1$/', $selected_provider)) {
                        // OAuth1 Client
                        $client->setToken($this->_getParam('oauth_token'), $sli_session->secret_key);
                        $response = $client->getAccessToken($provider['token_endpoint']);
                    }
                    else {
                        // OAuth2 Client
                        $response = $client->getAccessToken($provider['token_endpoint'], 'authorization_code', array(
                            'code' => $this->_getParam('code'),
                            'redirect_uri' => $return_url
                        ));
                    }
                    #Zend_Debug::Dump($response);
                    // Facebook Fixup
                    if ($selected_provider == 'facebook-oauth2' && isset($response['code']) && $response['code'] == 200 && isset($response) && isset($response['result']) && !isset($response['result']['access_token']) && preg_match('/access_token/', $response['result'])) {
                        $temp_string = $response['result'];
                        parse_str($temp_string, $response['result']);
                        #Zend_Debug::Dump($response);
                    }
                    #$response = $client->getAccessToken($TOKEN_ENDPOINT, 'refresh_token', array('refresh_token' => '1/Q6lW16Szv-IPMkeHUVSD3hEdxL8-sNUYDhrAiEBeWhA'));
                    #Zend_Debug::Dump($response);
                    
                    if ((isset($response['code']) && $response['code'] == 200 && isset($response['result']['access_token'])) ||
                        (preg_match('/1$/', $selected_provider)&& isset($response['oauth_token']) && isset($response['oauth_token_secret']))) {
                        if (preg_match('/1$/', $selected_provider)) {
                            $client->setToken($response['oauth_token'], $response['oauth_token_secret']);
                        }
                        else {
                            $client->setAccessToken($response['result']['access_token']);
                        }
                        $api_params = array();
                        if (isset($provider['api_params']) && is_array($provider['api_params']) && count($provider['api_params'])) {
                            $api_params = $provider['api_params'];
                            foreach ($api_params as $key => $val) {
                                $api_params[$key] = preg_replace('/{TOKEN}/', $response['result']['access_token'], $val);
                            }
                        }
                        $response = $client->fetch($provider['api_endpoint'], $api_params);
                        #Zend_Debug::Dump($response);
                        
                        if ((isset($response['code']) && $response['code'] == 200 && isset($response['result'])) ||
                            (preg_match('/1$/', $selected_provider) && $response)) {
                            // User Has Authed Successfully - Get the identity from the results
                            
                            if (preg_match('/1$/', $selected_provider)) {
                                $result = (array) json_decode($client->getLastResponse());
                            }
                            else {
                                $result = $response['result'];
                            }
                            #Zend_Debug::Dump($result);
                            
                            // Have to adjust for the different providers
                            // identity - id, email, fullname, first_name, last_name, gender, postal_code, dob_year&dob_month&dob_day / dob, username, timezone, country
                            $identity = array();
                            if ($selected_provider == 'google-oauth2') {
                                $identity['id'] = $result['id'];
                                if (isset($result['email'])) { $identity['email'] = $result['email']; }
                                if (isset($result['name'])) { $identity['fullname'] = $result['name']; }
                                if (isset($result['given_name'])) { $identity['first_name'] = $result['given_name']; }
                                if (isset($result['family_name'])) { $identity['last_name'] = $result['family_name']; }
                                if (isset($result['gender'])) {
                                    if ($result['gender'] == 'male') { $identity['gender'] = 'M'; }
                                    elseif ($result['gender'] == 'female') { $identity['gender'] = 'F'; }
                                }
                            }
                            elseif ($selected_provider == 'facebook-oauth2') {
                                $identity['id'] = $result['id'];
                                if (isset($result['email'])) { $identity['email'] = $result['email']; }
                                if (isset($result['name'])) { $identity['fullname'] = $result['name']; }
                                if (isset($result['first_name'])) { $identity['first_name'] = $result['first_name']; }
                                if (isset($result['last_name'])) { $identity['last_name'] = $result['last_name']; }
                                if (isset($result['birthday'])) { $identity['dob'] = $result['birthday']; }
                                #if (isset($result['timezone'])) { $identity['timezone'] = $result['timezone']; } // Facebook only returning offset : "-7"
                                
                                if (isset($result['locale'])) {
                                    $locale_tokens = explode('_', $result['locale']);
                                    #if (isset($locale_tokens[0])) { $identity['language'] = strtolower($locale_tokens[0]); } // Only accepting en right now
                                    if (isset($locale_tokens[1])) { $identity['country'] = strtoupper($locale_tokens[1]); }
                                }
                            }
                            elseif ($selected_provider == 'linkedin-oauth2') {
                                $identity['id'] = $result['id'];
                                if (isset($result['emailAddress'])) { $identity['email'] = $result['emailAddress']; }
                                if (isset($result['firstName'])) { $identity['first_name'] = $result['firstName']; }
                                if (isset($result['lastName'])) { $identity['last_name'] = $result['lastName']; }
                                if (isset($result['location']['country']['code'])) { $identity['country'] = strtoupper($result['location']['country']['code']); }
                            }
                            elseif ($selected_provider == 'twitter-oauth1') {
                                $identity['id'] = $result['id_str'];
                                if (isset($result['email'])) { $identity['email'] = $result['email']; }
                                if (isset($result['name'])) { $identity['name'] = $result['name']; }
                                if (isset($result['screen_name'])) { $identity['username'] = $result['screen_name']; }
                            }
                            #Zend_Debug::Dump($identity);
                            #return $this->_helper->viewRenderer->setNoRender();
                            
                            
                            
                            
                            
                            $select = $this->db->select()
                                ->from(array('m' => 'member_members'), array('id', 'email', 'password', 'password_salt'))
                                ->join(array('msl' => 'member_social_logins'), 'm.id=msl.member_id', array())
                                ->where('m.active=?', 't')
                                ->where('msl.active=?', 't')
                                ->where('msl.provider=?', $selected_provider)
                                ->where('msl.id=?', $identity['id']);
                            
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
                                            'provider' => $selected_provider,
                                            'id' => $identity['id']
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
                                if (isset($identity['email'])) {
                                    $selectByEmail = $this->db->select()
                                        ->from(array('member_members'), array('id', 'email', 'password', 'password_salt', 'active'))
                                        ->where('active ISNULL OR active=?', 't')
                                        ->where('lower(email)=lower(?)', $identity['email'])
                                        ->limit(1);
                                }
                                
                                if (isset($selectByEmail) && ($memberByEmail = $this->db->fetchRow($selectByEmail))) {
                                    // Second, lets see if we have a matching email address
                                    
                                    // Attach
                                    try {
                                        $this->db->insert('member_social_logins', array(
                                            'member_id' => $memberByEmail->id,
                                            'provider' => $selected_provider,
                                            'id' => $identity['id']
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
                                    
                                    $regurl = '/Register/s/member/register/p/signup_entrance/OAuth/';
                                    $params = array();
                                    $params[] = 'supershortform=1';
                                    $params[] = 'sli=' . $selected_provider;
                                    
                                    // identity - id, email, fullname, first_name, last_name, gender, postal_code, dob_year&dob_month&dob_day / dob, username, timezone, country
                                    if (isset($identity['email']) && $identity['email']) {
                                        $params[] = 'vmpd_fp=1';
                                        $params[] = 'email=' . urlencode($identity['email']);
                                    }
                                    if (isset($identity['fullname']) && $identity['fullname']) {
                                        #$params[] = 'fullname=' . urlencode($identity['fullname']);
                                        $tokens = preg_split("/\s+/", $identity['fullname']);
                                        
                                        if (!(isset($identity['first_name']) && $identity['first_name'])) { $params[] = 'first_name=' . urlencode(array_shift($tokens)); }
                                        else { array_shift($tokens);  }
                                        
                                        if (!(isset($identity['last_name']) && $identity['last_name'])) { $params[] = 'last_name=' . urlencode(array_pop($tokens)); }
                                        else { array_pop($tokens);  }
                                        
                                        if (!(isset($identity['middle_name']) && $identity['middle_name']) && count($tokens)) { $params[] = 'middle_name=' . urlencode(implode(' ', $tokens)); }
                                        
                                        #$params[] = 'first_name=' . urlencode(array_shift($tokens));
                                        #$params[] = 'last_name=' . urlencode(array_pop($tokens));
                                        #$params[] = 'middle_name=' . urlencode(implode(' ', $tokens));
                                    }
                                    if (isset($identity['first_name']) && $identity['first_name']) { $params[] = 'first_name=' . urlencode($identity['first_name']); }
                                    if (isset($identity['middle_name']) && $identity['middle_name']) { $params[] = 'middle_name=' . urlencode($identity['middle_name']); }
                                    if (isset($identity['last_name']) && $identity['last_name']) { $params[] = 'last_name=' . urlencode($identity['last_name']); }
                                    if (isset($identity['gender']) && $identity['gender']) { $params[] = 'gender=' . urlencode($identity['gender']); }
                                    if (isset($identity['postal_code']) && $identity['postal_code']) { $params[] = 'postal_code=' . urlencode($identity['postal_code']); }
                                    if ((isset($identity['dob_year']) && $identity['dob_year']) && (isset($identity['dob_month']) && $identity['dob_month']) && (isset($identity['dob_day']) && $identity['dob_day'])) {
                                        $params[] = 'dob_year=' . urlencode($identity['dob_year']);
                                        $params[] = 'dob_month=' . urlencode($identity['dob_month']);
                                        $params[] = 'dob_day=' . urlencode($identity['dob_day']);
                                    }
                                    elseif (isset($identity['dob']) && $identity['dob']) { $params[] = 'dob=' . urlencode($identity['dob']); }
                                    
                                    if (isset($identity['username']) && $identity['username']) { $params[] = 'username=' . urlencode($identity['username']); }
                                    else {
                                        $tmpUsername = '';
                                        if (isset($identity['fullname']) && $identity['fullname']) { $tmpUsername = $identity['fullname']; }
                                        elseif (isset($identity['first_name']) && $identity['first_name']) {
                                            $tmpUsername = $identity['first_name'];
                                            if (isset($identity['middle_name']) && $identity['middle_name']) { $tmpUsername .= ' ' . $identity['middle_name']; }
                                            if (isset($identity['last_name']) && $identity['last_name']) { $tmpUsername .= ' ' . $identity['last_name']; }
                                        }
                                        elseif (isset($identity['email']) && $identity['email']) { $tmpUsername = 'username=' . urlencode(preg_replace('/\@.*/', '', $identity['email'])); }
                                        
                                        if ($tmpUsername) { $params[] = 'username=' . urlencode($tmpUsername); }
                                    }
                                    
                                    if (isset($identity['timezone']) && $identity['timezone']) { $params[] = 'timezone=' . urlencode($identity['timezone']); }
                                    if (isset($identity['country']) && $identity['country']) { $params[] = 'country=' . urlencode($identity['country']); }
                                    # language - 'pref/language'
                                    
                                    if (isset($sli_session->redirect)) { $params[] = 'redirect=' . urlencode($sli_session->redirect); }
                                    
                                    if (count($params)) { $regurl .= '?' . implode('&', $params); }
                                    
                                    #Zend_Debug::Dump($regurl);
                                    $this->_redirect($regurl);
                                }
                            }
                            
                            
                            
                            
                        }
                        else {
                            self::displayError();
                        }
                    }
                    else {
                        // Probably a refreshing of stale code page
                        self::displayError();
                    }
                }
                else {
                    // User Canceled Authentication
                    if (isset($sli_session->redirect)) { $urltogoto = $sli_session->redirect; }
                    Zend_Session::namespaceUnset('SLISession');
                    $this->_redirect('/member/login/p/vmpd_nar/1/' . (isset($urltogoto) ? '?redirect=' . urlencode($urltogoto) : ''));
                }
            }
            else {
                if (preg_match('/1$/', $selected_provider) && isset($provider['req_endpoint']) && $provider['req_endpoint']) {
                    try {
                        $request_token_info = $client->getRequestToken($provider['req_endpoint']);
                        #Zend_Debug::Dump($request_token_info);
                        $sli_session->secret_key = $request_token_info['oauth_token_secret'];
                        $sli_session->state = $selected_provider;
                        
                        $auth_url = $provider['auth_endpoint'] . '?oauth_token=' . $request_token_info['oauth_token'];
                    } catch (Exception $e) { }
                }
                else {
                    $auth_url = $client->getAuthenticationUrl($provider['auth_endpoint'], $return_url, array(
                        'scope' => $provider['scope'],
                        'state' => $selected_provider
                    ));
                }
                
                if (isset($auth_url) && $auth_url) {
                    try {
                        $this->_redirect($auth_url);
                    } catch(ErrorException $e) {
                        self::displayError();
                    }
                }
            }
        }
        else {
            // No provider specified or returned
            #echo 'No Provider Specified';
            self::displayError();
        }
        
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
            'hd' => 'OAuth Error',
            'bd' => <<<EOM
<p class="error">OAuth Has Failed</p><p>There was a problem with the OAuth authentication.  Please <a href="javascript:history.back();">go back</a> and try your request again or try an <a href="/member/oauth/login/?redirect=$redirect">alternate OAuth method</a>. Or, you can <a href="/member/login/?redirect=$redirect">sign in</a> or <a href="/member/register/?redirect=$redirect">register</a> using our normal methods.</p>
<p><a href="/member/login/?redirect=$redirect">Sign-In &raquo;</a>, <a href="/member/register/?redirect=$redirect">Register &raquo;</a>, or <a href="/member/oauth/login/?redirect=$redirect">Try Another OAuth Method</a></p>
<p><a href="$redirect">Continue &raquo;</a></p>
EOM
        ));
    }
}