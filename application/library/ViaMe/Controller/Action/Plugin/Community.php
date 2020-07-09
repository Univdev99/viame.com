<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action_Plugin_Community extends Zend_Controller_Plugin_Abstract
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
        
        // META - Defaults
        $viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
            #$viewRenderer->view->headTitle('ViaMe'); # Will get set in layout
            $viewRenderer->view->headMeta()->setName('author', 'ViaMe');
            $viewRenderer->view->headMeta()->setName('copyright', 'Copyright by ViaMe');
            $viewRenderer->view->headMeta()->setName('description', 'ViaMe Community');
            $viewRenderer->view->headMeta()->setName('keywords', 'ViaMe');
            if (isset($this->member)) {
                $viewRenderer->view->inlineScript()->appendScript('var vm_user = ' . $this->member->id . ";\nvar vm_via_id = " . $this->member->profile->id . ";\n");
            }
        
        // Get Community From Hostname
        if (isset($this->vars->host)) {

            $system_models_communities = new system_models_communities();
            $acl_clause = $this->db->quoteInto("(check_acl(c.acl, ?, c.id, 0, 0, 0, 0, 0, 0))", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $select = $system_models_communities->select()
                ->from(array('c' => 'system_communities'),
                    array('*',
                        $this->db->quoteInto('(b.id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                        "$acl_clause.privilege",
                        "$acl_clause.filter",
                        "$acl_clause.recursive",
                    )
                )
                ->where("c.active='t'")
                // ACL Check
                #->where(
                #    'c.acl IS NULL OR ' .                                                       // No ACL
                #    'c.member_id=CAST(? AS bigint) OR ' .                                       // Owner
                #    '(check_acl(c.acl, CAST(? AS bigint), c.id, 0,0,0,0,0,0)).allowed OR ' .    // Default is Allowed
                #    '(check_acl(c.acl, CAST(? AS bigint), c.id, 0,0,0,0,0,0)).allowed IS NULL', // Specifically Allowed
                #    (isset($this->member->id) ? $this->member->id : 0),
                #    (isset($this->member->profile->id) ? $this->member->profile->id : 0),
                #    (isset($this->member->profile->id) ? $this->member->profile->id : 0)
                #)
                ->join(array('b' => 'member_members'), 'c.member_id = b.id', array())
                ->where("b.active='t'");
                
            $select->where("c.hostname_negative_regexps ISNULL OR (? !~* ('^(' || array_to_string(c.hostname_negative_regexps, '|') || ')$'))", $this->vars->host_name . '.' . $this->vars->domain_name);
            
            try {
                $selectBackup = clone $select;
                $selectBackup->order(array('c.id ASC'));
                
                // Get community based on id or name
                if ($request->getParam('com_id')) {
                    $select->where('c.id=?', $request->getParam('com_id'));
                }
                elseif (stripos($this->vars->domain_name, $this->config->default_domain) === 0) {
                    if (isset($this->vars->host_name)) { $community_name = $this->vars->host_name; }
                    else { $community_name = 'www'; }
                    if ($community_name == 'www' || !$community_name) { $community_name = 'default'; }
                    
                    $select->where('c.name=?', $community_name);
                }
                else {
                    $select->where("lower(c.hostname)=lower(?) OR (? ~* ('^(' || array_to_string(c.hostname_positive_regexps, '|') || ')$'))", $this->vars->host_name . '.' . $this->vars->domain_name);
                }
                
                $select->order(array('c.orderby ASC'));
                
                $COMM_REDIRECT = true;
                if (!($community = $this->db->fetchRow($select))) {
                    $COMM_REDIRECT = false;
                    $community = $this->db->fetchRow($selectBackup);
                }
            } catch (Exception $e) { }
            
            if (isset($community) && $community) {
                // Redirect to hostname if matched on a regexp
                // 7/13/2010 - Adjusted to only redirect for anonymous users
                if ((getenv('APPLICATION_ENV') != 'development') && !isset($this->member) && isset($community->hostname) && $community->hostname &&
                  $COMM_REDIRECT && !preg_match('/' . $community->hostname . '$/i', $this->vars->host) &&
                  $this->config->debug < 5 // Don't redirect in the staging/testing phase
                  ) {
                    $this->_response->setRedirect(
                        'https://' .
                        ((isset($this->vars->pre_lang) && $this->vars->pre_lang) ? $this->vars->pre_lang . '.' : '') .
                        ((isset($this->vars->language) && $this->vars->language) ? $this->vars->language . '.' : '') .
                        $community->hostname . ($request->getParam('com_id') ? str_ireplace("/com/" . $request->getParam('com_id'), '', $this->vars->request_uri) : $this->vars->request_uri)
                        ,
                        301
                    )->sendHeaders();
                    exit;
                }
               
                
                // See if the parameter was necessary and strip if it was redundant
                if ($request->getParam('com_id') && (strtolower($community->hostname) == strtolower($this->vars->host_name . '.' . $this->vars->domain_name))) {
                    $request->setParam('com_id', null);
                    if (Zend_Registry::isRegistered('params')) {
                        $temp_params = Zend_Registry::get('params');
                        if (isset($temp_params->com_id)) {
                            unset($temp_params->com_id);
                            Zend_Registry::set('params', $temp_params);
                        }
                    }
                }
                
                $this->log->setEventItem('com_id', $community->id);
                
                # Load up the children ids
                $query = $this->db->quoteInto("SELECT array_accum(recursive_find) FROM recursive_find('system_communities', 'id', 'parent_id', CAST(? AS bigint), 'f')", $community->id);
                $community->children_ids_array = $this->db->fetchOne($query);
                
                if ($community->parent_id) {
                    # Load Up the Parent Tree
                    $select = $system_models_communities->select()
                        ->from(array('c' => 'system_communities'),
                            array('*',
                                $this->db->quoteInto('(b.id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                                "$acl_clause.privilege",
                                "$acl_clause.filter",
                                "$acl_clause.recursive"
                            )
                        )
                        ->where("c.active='t'")
                        ->where("c.id IN (SELECT * FROM recursive_find('system_communities', 'parent_id', 'id', CAST(? AS bigint), 'f'))", $community->id)
                        ->join(array('b' => 'member_members'), 'c.member_id = b.id', array())
                        ->where("b.active='t'")
                        ->order('id DESC');
                    $community->parent_tree = $this->db->fetchAll($select);
                }
    
                // Set Layout
                $this->VMAH->resetLayout($community);
                
                $this->target = new StdClass;
                Zend_Registry::set('target', $this->target);
                
                $this->target->type = null;
                
                $this->target->id = $community->id;
                #$this->target->pre = ((isset($community->hostname) && $community->hostname) ? 'https://' . $community->hostname : '');
                $this->target->pre = '';
                
                $this->target->acl = new StdClass;
                
                $this->target->acl->owner = (isset($this->member) && (($community->member_id == $this->member->id) || ($this->member->site_admin) || (isset($this->member->profile) && $this->member->profile->site_admin)));
                $this->target->acl->allowed = $community->allowed;
                $this->target->acl->privilege = $community->privilege;
                $this->target->acl->filter = $community->filter; 
                $this->target->acl->recursive = $community->recursive;
                
                if ($this->target->acl->owner) {
                    $this->target->acl->allowed = true;
                    $this->target->acl->privilege = 100;
                    $this->target->acl->filter = null;
                    $this->target->acl->recursive = true;
                }
                elseif (isset($community->parent_tree) && isset($this->member)) {
                    foreach ($community->parent_tree as $parent) {
                        // Owner of parent community is also owner
                        if ($parent->member_id == $this->member->id) {
                            $this->target->acl->owner = true;
                            $this->target->acl->allowed = true;
                            $this->target->acl->privilege = 100;
                            $this->target->acl->filter = null;
                            $this->target->acl->recursive = true;
                            break;
                        }
                        elseif ((!($parent->allowed === null)) && (($this->target->acl->privilege === null) || ($parent->recursive && ($parent->privilege > $this->target->acl->privilege)))) {
                            // Give higher parent privileges if they exist
                            $this->target->acl->allowed = $parent->allowed;
                            $this->target->acl->privilege = $parent->privilege;
                            $this->target->acl->filter = $parent->filter;
                            $this->target->acl->recursive = $parent->recursive;
                        }
                    }
                }
                
                // Copy the target ACL to community
                $this->acl = new StdClass;
                Zend_Registry::set('acl', $this->acl);
                $this->acl->COM = clone $this->target->acl;
                
                // META
                if ($community->name != 'default') {
                    $viewRenderer->view->headTitle(($community->meta_title ? $community->meta_title : ($community->display ? $community->display : $community->name)));
                    $viewRenderer->view->headMeta()->setName('author', ($community->display ? $community->display : $community->name));
                    $viewRenderer->view->headMeta()->setName('copyright', 'Copyright by ' . ($community->display ? $community->display : $community->name));
                }
                if ($community->meta_description) {
                    $viewRenderer->view->headMeta()->setName('description', $community->meta_description);
                }
                if ($community->meta_keywords) {
                    $viewRenderer->view->headMeta()->setName('keywords', $community->meta_keywords);
                }
                
                // Not allowed
                if (($this->target->acl->allowed === false) || ($this->target->acl->privilege === 0)) {
                    $fcr = Zend_Controller_Front::getInstance()->getRequest();
                    if (!$community->show_on_fail) {
                        $fcr->setModuleName('default')->setControllerName('error')->setActionName('error')->setParams(array('title' => 'Community Error', 'message' => 'That community does not exist or is deactivated.'));
                    }
                    // Allow access to member and acl modules
                    elseif ((stripos($fcr->getServer('REQUEST_URI'), '/member/') !== 0) && (stripos($fcr->getServer('REQUEST_URI'), '/acl/') !== 0)) {
                        $fcr->setModuleName('default')->setControllerName('error')->setActionName('error')->setParams(array('errorcode' => 401, 'title' => 'Community Error', 'message' => 'You are not allowed access to this community.',
                            'denyParams' => array(
                                'type' => 'COM',
                                'id' => $this->target->id,
                                'mod' => 0,
                                'mid' => 0,
                                'iid' => 0,
                                'priv' => 10
                            )
                        ));
                    }
                }
                else {
                    // REFERRERS
                    /*
                        NOTE : This whole section may not be necessary anymore with the new cookie setter functionality in
                                 Controller/Action/Plugin/Community.php
                               
                               ?vmpd_ckstr[community_referrer_id]=2&vmpd_ckstr[profile_referrer_id]=2
                    */
                    if (!isset($this->member) && !isset($_COOKIE[$this->config->auth->cookie_name->login])) {
                        // Redid this a little so that it doesn't automatically set the community referrer cookie unless it is
                        //   passed in by parameter.  nginx does cache pages with set-cookie in header...
                        #if (isset($community->id) && !isset($_COOKIE[$this->config->auth->cookie_name->community_referrer_id])) {
                        if ($request->getParam('community_referrer_id') && !isset($_COOKIE[$this->config->auth->cookie_name->community_referrer_id])) {
                            if (!isset($this->cookie)) { $this->cookie = new StdClass; }
                            #$this->cookie->{$this->config->auth->cookie_name->community_referrer_id} = $community->id;
                            #$this->VMAH->setVMCookie($this->config->auth->cookie_name->community_referrer_id, $community->id);
                            $this->cookie->{$this->config->auth->cookie_name->community_referrer_id} = $request->getParam('community_referrer_id');
                            $this->VMAH->setVMCookie($this->config->auth->cookie_name->community_referrer_id, $request->getParam('community_referrer_id'));
                        }
                        if ($request->getParam('profile_referrer_id')) {
                            // Set Cookie If Not Already Set
                            if (!isset($_COOKIE[$this->config->auth->cookie_name->profile_referrer_id])) {
                                if (!isset($this->cookie)) { $this->cookie = new StdClass; }
                                $this->cookie->{$this->config->auth->cookie_name->profile_referrer_id} = $request->getParam('profile_referrer_id');
                                $this->VMAH->setVMCookie($this->config->auth->cookie_name->profile_referrer_id, $request->getParam('profile_referrer_id'));
                            }
                            
                            // Track Referrer Profile ID Click For All Clicks
                            $this->db->insert('member_referrer_clicks_counts', array('profile_id' => $request->getParam('profile_referrer_id'), 'ip_address' => $_SERVER['REMOTE_ADDR']));
                        }
                    }
                }
                
                Zend_Registry::set('community', $community);
            }
            else {
                // Community doesn't exist
                $community = new StdClass;
                $community->name = 'default';
                Zend_Registry::set('community', $community);

                $fcr = Zend_Controller_Front::getInstance()->getRequest();
                $fcr->setModuleName('default')->setControllerName('error')->setActionName('error')->setParams(array('title' => 'Community Error', 'message' => 'That community does not exist or is deactivated.'));
            }
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
