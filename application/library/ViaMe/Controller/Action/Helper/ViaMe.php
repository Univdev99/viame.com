<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Helper_ViaMe extends Zend_Controller_Action_Helper_Abstract
{
    public function init()
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        $this->layout = Zend_Layout::getMvcInstance();
    }
    
    public function setLayout ($new_layout = null, $community = null)
    {
        if (isset($this->layout) && $this->layout->isEnabled() && $new_layout) {
            // Each Time, the Layouts Must Be Reset
            if ($community === null) {
                $community = $this->community;
            }
            
            $tryorder = $this->getLayoutTryOrder($community);
            $start = $this->resetLayoutPath($tryorder);
            
            if ($start >= 0) {
                for ($i = $start; $i < count($tryorder); $i++) {
                    $try = dirname($this->layout->getLayoutPath()) . '/' . $tryorder[$i] . "/$new_layout." . $this->layout->getViewSuffix();
                    if (is_file($try)) {
                        $this->setLayoutPath($tryorder[$i]);
                        $this->layout->setLayout($new_layout);
                        return true;
                    }
                }
            }
        }
        
        return false;
    }
    
    public function setSubLayout ($new_sublayout = null, $community = null)
    {
        if (isset($this->layout) && $this->layout->isEnabled() && $new_sublayout) {
            if ($community === null) {
                $community = $this->community;
            }
            
            $tryorder = $this->getLayoutTryOrder($community);
            
            for ($i = 0; $i < count($tryorder); $i++) {
                $try = dirname($this->layout->getLayoutPath()) . '/' . $tryorder[$i] . "/sublayouts/$new_sublayout." . $this->layout->getViewSuffix();
                if (is_file($try)) {
                    #$this->getActionController()->view->placeholder('content')->subform = "../$tryorder[$i]/sublayouts/$new_sublayout." . $this->layout->getViewSuffix();
                    $this->vars->sublayout = "../$tryorder[$i]/sublayouts/$new_sublayout." . $this->layout->getViewSuffix();
                    return true;
                }
            }
        }
        else {
            unset($this->vars->sublayout);
        }
    
        return false;
    }
    
    public function resetLayout ($community = null)
    {
        if (isset($this->layout) && $this->layout->isEnabled()) {
            return $this->setLayout('layout', $community);
        }
        
        return false;
    }
    
    
    public function resetLayoutPath ($tryorder = null)
    {
        if ($tryorder) {
            for ($i = 0; $i < count($tryorder); $i++) {
                if ($this->setLayoutPath($tryorder[$i])) {
                    return $i;
                }
            }
        }
        
        $this->setLayoutPath('default');
        
        return false;
    }
    
    public function getLayoutTryOrder ($community = null)
    {
        $tryorder = array();
        
        if (isset($community)) {
            if (isset($community->layout)) {
                $tryorder[] = $community->layout;
            }
            if (isset($community->name)) {
                $tryorder[] = $community->name;
            }
            if (isset($community->parent_tree)) {
                foreach ($community->parent_tree as $parent) {
                    if (isset($parent->layout)) {
                        $tryorder[] = $parent->layout;
                    }
                    if (isset($parent->name)) {
                        $tryorder[] = $parent->name;
                    }
                }
            }
        }

        $tryorder[] = 'default';
        
        return $tryorder;
    }
    
    public function setLayoutPath ($new_layout_path = null)
    {
        // No Fallbacks.  If directory exists, change, if not, don't
        if ($new_layout_path) {
            // Change Layout Path
            if (isset($this->layout) && $this->layout->isEnabled()) {
                $new_path = dirname($this->layout->getLayoutPath()) . "/$new_layout_path";
                if (is_dir($new_path)) {
                    $this->layout->setLayoutPath($new_path);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    public function setVMCookie ($cookie_name = '', $cookie_value = '', $cookie_timout = 0)
    {
        $_COOKIE[$cookie_name] = $cookie_value;
        return setcookie($cookie_name, $cookie_value, $cookie_timout, '/', '.' . $this->vars->domain_name);
    }
    
    public function loginMember ($password = null, $password_salt = null, $id = null, $email = null, $persist = null)
    {
        // Get Persist
        if (!isset($persist) && isset($this->cookie->persist)) { $persist = $this->cookie->persist; }
        elseif (!isset($persist) && isset($cpersist)) { $persist = $cpersist; }
        
        // Set Timers
        $login_timer = $password_timer = 0;
        if ($persist) {
            $login_timer = time() + $this->config->auth->cookie_lifetime->login; // 10 Years
            $password_timer = time() + $this->config->auth->cookie_lifetime->password; // 2 Weeks
        }
        
        if ($password && $password_salt) {
            self::setVMCookie($this->config->auth->cookie_name->password, md5(crypt(md5($password_salt.$password), $password_salt)), $password_timer);
        }
        
        if ($id || $email) {
            if (!$id && isset($this->member)) { $id = $this->member->id; }
            elseif (!$id && isset($this->cookie->id)) { $id = $this->cookie->id; }
            
            if (!$email && isset($this->member)) { $id = $this->member->email; }
            elseif (!$email && isset($this->cookie->email)) { $email = $this->cookie->email; }
            
            self::setVMCookie($this->config->auth->cookie_name->login, "$persist:$id:$email", $login_timer);    
        }
    }
    
    public function sendEmail ($toEmail='', $toName='', $subject='', $contentHTML='', $contentTEXT='', $fromEmail='', $fromName='', $attachments=null)
    {
        if (!isset($this->community)) { ViaMe_Controller_Action::registryLoader($this); }
        
        $config = array();
        if (isset($this->config->smtp->auth) && $this->config->smtp->auth) { $config['auth'] = $this->config->smtp->auth; }
        if (isset($this->config->smtp->username) && $this->config->smtp->username) { $config['username'] = $this->config->smtp->username; }
        if (isset($this->config->smtp->password) && $this->config->smtp->password) { $config['password'] = $this->config->smtp->password; }
        if (isset($this->config->smtp->ssl) && $this->config->smtp->ssl) { $config['ssl'] = $this->config->smtp->ssl; }
        if (isset($this->config->smtp->port) && $this->config->smtp->port) { $config['port'] = $this->config->smtp->port; }
        
        if (!$fromEmail) {
            $fromEmail = $this->config->email->from_email;
        }
        if (!$fromName) {
            $fromName = $this->config->email->from_name;
        }
        
        if ($toEmail && $subject && ($contentHTML || $contentTEXT)) {
            
            $transport = new Zend_Mail_Transport_Smtp($this->config->smtp->hostname, $config);
            $mail = new Zend_Mail();
            
            if (is_array($toEmail)) {
            	// array('first last' => 'name@domain.com') or array('email1@domain.com', 'email2@domain.com')
            	foreach ($toEmail as $temp_name => $temp_email) {
            		$mail->addTo($temp_email, (is_string($temp_name) ? $temp_name : null));
            	}
            }
            else {
	            $mail->addTo($toEmail, $toName);
	        }
	        
            $mail->setFrom($fromEmail, $fromName);
            $mail->setSubject($this->community->display . ': ' . $subject);
            
            if ($contentHTML) {
                if (is_file($this->layout->getLayoutPath() . '/email.' . $this->layout->getViewSuffix())) {
                    $mailLayout = new Zend_Layout();
                    $mailLayout->setLayoutPath($this->layout->getLayoutPath());
                    $mailLayout->setLayout('email');
                    $mailLayout->subject = $subject;
                    $mailLayout->content = $contentHTML;
                    $contentHTML = $mailLayout->render();
                }
                
                $mail->setBodyHtml($contentHTML, 'UTF-8', Zend_Mime::ENCODING_7BIT);
            }
            if ($contentTEXT) {
                $mail->setBodyText($contentTEXT, 'UTF-8', Zend_Mime::ENCODING_7BIT);
            }
            elseif ($contentHTML) {
                // Convert the HTML version to TEXT
                require_once $this->vars->APP_PATH . "/library/Other/html2text.php";
                $h2t = new html2text($contentHTML);
                $mail->setBodyText($h2t->get_text(), 'UTF-8', Zend_Mime::ENCODING_7BIT);
            }
            
            if ($attachments) {
                if (is_array($attachments) && count($attachments)) {
                    foreach ($attachments as $attachment) {
                        $mail->addAttachment($attachment);
                    }
                }
                else {
                    $mail->addAttachment($attachments);
                }
            }
            
            try {
                $mail->send($transport);
            } catch (Exception $e) { }

            return true;
        }

        return false;
    }
    
    public function iPgArray ($arr = null)
    {
        if ($arr == null) { return null; }
        elseif (count($arr) == 0) { return '{}'; }
        else {
            #Zend_Debug::Dump($arr);
            return '{' . implode(',', $arr) . '}';
        }
    }
    
    public function ePgArray ($str = null) {
        if ($str == null) { return null; }
        elseif ($str == '') { return array(); }
        else {
            $str = preg_replace('/^\{(.*)\}$/', '${1}', $str);
            
            if (strpos($str, ',')) { return explode(',', $str); }
            elseif ($str) { return array($str); }
            else { return array(); }
        }
    }
    
    public function log ($message = null, $priority = null, $params = null, $extras = null) {
        if (!isset($this->log) || !isset($this->db)) {
            self::init();
        }
        
        
        if (isset($this->log) && isset($this->db) && $message) {
            $p = array();
            
            if (empty($priority)) {
                $priority = Zend_Log::EMERG; // Default is system message
            }
            
            if (!empty($params) && is_array($params) && count($params)) {
                $hashkey = null;
                $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
                for ($i = 0; $i < 10; $i++) {
                    $hashkey .= $characters[mt_rand(0, strlen($characters)-1)];
                }
                
                $p['parameter_values'] = new Zend_Db_Expr("ARRAY[\$$hashkey\$" . implode("\$$hashkey\$,\$$hashkey\$", $params) . "\$$hashkey\$]");
            }
            if (!empty($extras) && is_array($extras) && count($extras)) {
                $p = array_merge($p, $extras);
            }
            
            $this->log->log(
                $message,
                $priority,
                (count($p) ? $p : null)
            );
        }
    }
    
    public function getVSelectFromList ($arr = null, $withemails) {
        if (!isset($this->member) || !isset($this->target) || !isset($this->db)) {
            self::init();
        }
        
        $vids = array();
        $gids = array();

        // Prep the data - Form of C-xxx or V-XXX (contact or profile reference profile id) or G-xxx (group id of groups of current user)
        // Sync up the selected columns with contact/widget
        if (count($arr)) {
            foreach ($arr as $token) {
                $parts = null;
                $parts = explode('-', $token);
                if (strtoupper($parts[0]) == 'G') {
                    $gids[] = $this->db->quoteInto('gm.group_counter_id = ?', $parts[1]);
                } else {
                    if ($parts[0] && (!isset($parts[1]) || !$parts[1])) { $parts[1] = $parts[0]; }
                    $vids[] = $this->db->quoteInto('obj.id = ?', $parts[1]);
                }
            }
        }
        
        $select = $this->db->select()
            ->from(array('obj' => 'profile_profiles'), array('p_id' => 'id', 'p_name' => 'name', 'p_site_admin' => 'site_admin', 'p_active' => 'active', 'type' => new Zend_Db_Expr("'V'")))
            ->where('obj.active = ?',  't')
            
            ->join(array('b' => 'member_members'), 'obj.member_id = b.id', array('b_site_admin' => 'site_admin', 'b_email' => ($withemails ? 'email' : new Zend_Db_Expr('null')), 'b_active' => 'active'))
            ->where('b.active = ?', 't')
            
            ->join(array('c' => 'system_communities'), 'obj.community_id = c.id',
                array(
                'c_id' => 'id',
                'c_name' => 'name',
                'c_hostname' => 'hostname'
                )
            )
            ->where('c.active=?', 't');
            
        if (isset($this->member)) {
            $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                array(
                    'vc_creation' => 'creation',
                    'vc_display' => 'display',
                    'vc_description' => 'description',
                    'vc_message' => 'message',
                    'vc_auto_reciprocate' => 'auto_reciprocate',
                    'vc_status' => 'status',
                    'vc_active' => 'active'
                )
            );
        }
        
        if (count($gids)) {
            $select->joinLeft(array('gm' => 'contact_group_members'), $this->db->quoteInto("obj.id = gm.member_profile_id AND gm.profile_id=? AND gm.active='t'", $this->target->id), array());
        }
        if (count($vids) || count($gids)) {
            $zzz = array();
            if (count($vids)) { $zzz = $vids; }
            if (count($gids)) { $zzz = array_merge($zzz, $gids); }
            $select->where(implode(' OR ', $zzz));
        }
        
        return $select;
    }
    
    public function simpleEncrypt ($message = null) {
        if(!$message) { return false; }
        
        $result = '';
        for($i = 0; $i < strlen($message); $i++) {
            $char = substr($message, $i, 1);
            $keychar = substr($this->config->encrypt->key, ($i % strlen($this->config->encrypt->key))-1, 1);
            $char = chr(ord($char) + ord($keychar));
            $result .= $char;
        }
        
        if ($result) { return base64_encode($result); }
        else { return false; }
    }
    
    public function simpleDecrypt ($message = null) {
        if(!$message) { return false; }
        
        $result = '';
        $message = base64_decode($message);
    
        for($i = 0; $i < strlen($message); $i++) {
            $char = substr($message, $i, 1);
            $keychar = substr($this->config->encrypt->key, ($i % strlen($this->config->encrypt->key))-1, 1);
            $char = chr(ord($char) - ord($keychar));
            $result .= $char;
        }
        
        if ($result) { return $result; }
        else { return false; }
    }
}
