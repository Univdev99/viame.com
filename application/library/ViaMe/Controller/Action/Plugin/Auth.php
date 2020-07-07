<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_Auth extends Zend_Controller_Plugin_Abstract
{
    /*
    public function routeStartup(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>routeStartup() called</p>\n");
    }
    
    
    public function routeShutdown(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>routeShutdown() called</p>\n");
    }
    */
    
    public function dispatchLoopStartup(Zend_Controller_Request_Abstract $request)
    {
        ViaMe_Controller_Action::registryLoader($this);
        
        $cpersist = $cid = $cemail = $cprofile = $cpassword = $vmotli = null;
        
        if ($request->getParam('vmoti') && $request->getParam('vmote') && $request->getParam('vmotpw')) {
            // One Time Login
            $vmotli = true;
            $cid = $request->getParam('vmoti');
            $cemail = $request->getParam('vmote');
            $cpassword = $request->getParam('vmotpw');
            if (!isset($this->cookie)) {
                $this->cookie = new StdClass;
                Zend_Registry::set('cookie', $this->cookie);
            }
            if ($request->getParam('vmotp')) {
                $cprofile = $request->getParam('vmotp');
            }
        }
        else {
            // Persistent Login
            if ($request->has($this->config->auth->cookie_name->login)) {
                list($cpersist, $cid, $cemail) = explode(':', $request->getCookie($this->config->auth->cookie_name->login));
                if (!isset($this->cookie)) {
                    $this->cookie = new StdClass;
                    Zend_Registry::set('cookie', $this->cookie);
                }
                $this->cookie->{$this->config->auth->cookie_name->login} = $request->getCookie($this->config->auth->cookie_name->login);
                if ($cpersist) { $this->cookie->persist = true; }
                if ($cid) { $this->cookie->id = $cid; }
                if ($cemail) { $this->cookie->email = $cemail; }
            }
            if ($request->has($this->config->auth->cookie_name->profile)) {
                if (!isset($this->cookie)) { $this->cookie = new StdClass; }
                $this->cookie->{$this->config->auth->cookie_name->profile} = $request->getCookie($this->config->auth->cookie_name->profile);
                $cprofile = $request->getCookie($this->config->auth->cookie_name->profile);
                if ($cprofile) { $this->cookie->profile = $cprofile; }
            }
            if ($request->has($this->config->auth->cookie_name->password)) {
                if (!isset($this->cookie)) { $this->cookie = new StdClass; }
                $this->cookie->{$this->config->auth->cookie_name->password} = $request->getCookie($this->config->auth->cookie_name->password);
                $cpassword = $request->getCookie($this->config->auth->cookie_name->password);
                if ($cpassword) { $this->cookie->password = $cpassword; }
            }
        }
        
        if ($cid && $cemail && $cpassword) {
            $member = new StdClass;
            // Load up the member object
            # CACHE - Min
            $member_members = new member_models_members();
            $select = $member_members->select()
                ->where("active='t'")
                ->where('id=?', $cid)
                ->where('lower(email)=lower(?)', $cemail);
            $member = $this->db->fetchRow($select);
            
            if ($member &&
                (($vmotli && (md5($cpassword) == $member->password)) || (md5(crypt(md5($member->password_salt.$member->password), $member->password_salt)) == $cpassword))) {
                unset($member->password, $member->password_salt);
                
                // Administrator masking - only for member->site_admin
                // Need to save in cookie and persist - Also need unmask function
                if ($member->site_admin && $request->getParam('admin_member_mask_id')) {
                	$this->VMAH->setVMCookie('admin_member_mask_id', $request->getParam('admin_member_mask_id'));
                	$this->VMAH->setVMCookie('admin_profile_mask_id', '');
                }
                elseif ($member->site_admin && $request->getParam('admin_profile_mask_id')) {
                	$this->VMAH->setVMCookie('admin_profile_mask_id', $request->getParam('admin_profile_mask_id'));
                	$this->VMAH->setVMCookie('admin_member_mask_id', '');
                }
                elseif ($member->site_admin && $request->getParam('admin_unmask')) {
                	$this->VMAH->setVMCookie('admin_member_mask_id', '');
                	$this->VMAH->setVMCookie('admin_profile_mask_id', '');
                }
                if ($member->site_admin && ($request->getCookie('admin_member_mask_id') || $request->getCookie('admin_profile_mask_id'))) {
                    $select = $this->db->select()
                        ->from(array('b' => 'member_members'))
                        ->join(array('p' => 'profile_profiles'), 'p.member_id = b.id', array());
                    if ($request->getCookie('admin_member_mask_id')) {
                        $select->where('b.id=?', $request->getCookie('admin_member_mask_id'));
                    }
                    else {
                        $select->where('p.id=?', $request->getCookie('admin_profile_mask_id'));
                    }
                    if ($temp_member = $this->db->fetchRow($select)) { $member = $temp_member; }
                }
                
                // Load up the profiles
                # CACHE - Min
                $profile_profiles = new profile_models_profiles();
                $select = $this->db->select()
                    ->from(array('p' => 'profile_profiles'), array('*', "(SELECT COUNT(*) FROM contact_contacts cc WHERE cc.contact_profile_id=p.id AND status ISNULL AND active='t') AS total_contact_requests_count"))
                    ->where("p.active='t'")
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                    ->where('b.id=?', $member->id)
                    ->joinLeft(array('ms' => 'mail_stats'), 'ms.profile_id=p.id', array('possible_new_mail', 'total_new_mail_count'))
                    ->order(array('p.default_profile DESC', 'p.base DESC', 'p.name', 'p.id'));
                $member->profiles = $this->db->fetchAll($select);
                
                // Select Profile
                if ($member->profiles) {
                    if (!isset($member->profile) && $cprofile) {
                        // Get Profile From pid
                        foreach ($member->profiles as $profile) {
                            if ($profile->id == $cprofile) {
                                $member->profile = $profile;
                            }
                        }
                    }
                    
                    if (!isset($member->profile)) {
                        // Drop to Default Profile
                        $member->profile = $member->profiles[0];
                    }
                }
                
                // Add Profile ID to All Log Messages
                if (isset($member->profile->id)) {
                    $this->log->setEventItem('profile_id', $member->profile->id);
                    
                    if ($member->profile->possible_new_mail) {
                        $member->profile->total_new_mail_count = $this->db->fetchOne('SELECT resync_mail_stats(?)', $member->profile->id);
                    }
                }
                
                // Load up the profiles community subscriptions and owned and joined networks
                $select = $this->db->select()
                    ->from(array('c' => 'system_communities'), array('id', 'name', 'display', 'hostname'))
                    ->where('c.active=?', true)
                    ->join(array('b' => 'member_members'), 'c.member_id = b.id', array())
                    ->where('b.active=?', true)
                    ->where('c.id IN (SELECT unnest(array_except(community_ids, ARRAY[community_id])) FROM profile_profiles WHERE id=?)', $member->profile->id);
                $member->profile->communities = $this->db->fetchAll($select);
                
                $select = $this->db->select()
                    ->from(array('n' => 'network_networks'), array('id', 'profile_id', 'name', 'community_id'))
                    ->join(array('p' => 'profile_profiles'), 'n.profile_id = p.id', array())
                    ->where('p.active=?', true)
                    ->join(array('c' => 'system_communities'), 'n.community_id = c.id', array())
                    ->where('c.active=?', true)
                    ->joinLeft(array('nm' => 'network_members'), "nm.network_id=n.id AND nm.status='t' AND nm.active='t'", array())
                    ->where('n.profile_id=? OR nm.member_profile_id=?', $member->profile->id);
                $member->profile->networks = $this->db->fetchAll($select);
                
                // Load up the profiles modules
                $select = $this->db->select()
                    ->from(array('x' => 'module_matrix'),
                        array('*',
                            "array_to_string(parameter_values, '" . $this->config->delimiter . "') as parameter_values_delimited"
                        )
                    )
                    ->where("x.active='t'")
                    ->where('x.via_id=?', $member->profile->id)
                    ->join(array('m' => 'module_modules'), 'x.module_id = m.id', array(
                        'm_name' => 'name',
                        'm_display' => 'display',
                        'm_description' => 'description',
                        'm_customizable' => 'customizable',
                        'm_allow_multiple' => 'allow_multiple',
                        'm_allow_flow' => 'allow_flow',
                        'm_allow_mask' => 'allow_mask',
                        'm_system' => 'system',
                        'm_profile_only' => 'profile_only'
                      ))
                    ->where('m.active=?', 't')
                    #->where('m.system ISNULL OR m.system<>?', 't')
                    // Redundant as we are already have a valid via - Keep For allowed acl clause
                    ->join(array('p' => 'profile_profiles'), 'p.id = x.via_id', array())
                    ->where('p.active=?', 't')
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                    ->where('b.active=?', 't')
                    ->order(array('m.display', 'm.name', 'x.counter', 'm.id'));
                
                $member->profile->modules = $this->db->fetchAll($select);
                
                
                // Set the Users Locale Based on Language and Country
                if (!isset($this->vars->language) || !$this->vars->language) {
                    $this->locale->setLocale($member->language . '_' . $member->country);
                }
                
                Zend_Registry::set('member', $member);
                
                // Turn on more debugging for member site admins
                if ($member->site_admin || $member->profile->site_admin) {
                    if ($this->config->loglevel >= 7) {
                        $this->log->addWriter(new Zend_Log_Writer_Stream('php://output'));
                    }
                    
                    if ($this->config->enable_firebug) {
                        $this->log->addWriter(new Zend_Log_Writer_Firebug());
                        
                        #if ($this->config->db->options->profiler->enabled) {
                        if ($this->config->resources->db->params->profiler->enabled) {
                            // Make sure the front cache can handle large headers
                            $this->db->setProfiler(new Zend_Db_Profiler_Firebug('All Database Queries:'));
                            $this->db->getProfiler()->setEnabled(true);
                        }
                    }
                }
            }
            
            #$this->log->info($request->getParam('module') . ' - ' . $request->getParam('submodule') . ' - ' . $request->getParam('controller') . ' - ' . $request->getParam('subcontroller'));
        }
    }

    /*
    public function preDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>preDispatch() called</p>\n");
    }

    public function postDispatch(Zend_Controller_Request_Abstract $request)
    {
        $this->getResponse()->appendBody("<p>postDispatch() called</p>\n");
    }

    public function dispatchLoopShutdown()
    {
        $this->getResponse()->appendBody("<p>dispatchLoopShutdown() called</p>\n");
    }
    */
}
