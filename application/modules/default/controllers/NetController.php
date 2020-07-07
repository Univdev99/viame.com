<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class NetController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        $this->target->type = 'NET';
        
        $this->target->id = $this->_getParam('net_id');
        $this->target->pre .= '/net/' . $this->target->id;
        
        $net_id = $this->target->id;
        
        if ($net_id) {
            $acl_clause = $this->db->quoteInto('(check_acl(n.acl, ?, 0, n.id, 0, 0, 0, 0, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $select = $this->db->select()
                ->from(array('n' => 'network_networks'),
                    array('*',
                        $this->db->quoteInto('(p.member_id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                        "$acl_clause.privilege",
                        "$acl_clause.filter",
                        "$acl_clause.recursive",
                        'long_category' => "(SELECT array_to_string(array_accum(category), ' : ') FROM network_networks WHERE id IN (SELECT * from recursive_find('network_networks', 'parent_id', 'id', n.id, 't')))"
                    )
                )
                ->where("n.active='t'")
                ->where('n.id=?', $net_id)
                ->join(array('p' => 'profile_profiles'), 'n.profile_id = p.id', array('p_member_id' => 'member_id', 'p_name' => 'name', 'p_same_member_priv' => 'same_member_priv'))
                ->where('p.active=?', 't')
                ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                ->where('b.active=?', 't')
                ->join(array('c' => 'system_communities'), 'n.community_id = c.id', array('c_id' => 'id', 'c_name' => 'name', 'c_hostname' => 'hostname'))
                ->where('c.active=?', 't');
            
            if (isset($this->member)) {
                $select->joinLeft(array('nm' => 'network_members'), $this->db->quoteInto("nm.network_id = n.id AND nm.member_profile_id=? AND nm.active='t'", $this->member->profile->id), array('nm_status' => 'status', 'nm_active' => 'active'));
            }
            
            try {
                $network = $this->db->fetchRow($select);
            } catch (Exception $e) { }
            
            if ($network) {
                if ($this->community->id != $network->community_id &&
                  $this->config->debug < 5 // Don't redirect in the staging/testing phase
                  ) {
                    $this->_redirect(
                        'http://' .
                        (isset($this->vars->language) ? $this->vars->language . '.' : '') .
                        (isset($network->c_hostname) ? $network->c_hostname : (($network->c_name != 'default' ? $network->c_name : 'www') . '.' . $this->config->default_domain)) .
                        $this->getRequest()->getServer('REQUEST_URI')
                    );
                }
                
                $this->log->setEventItem('net_id', $network->id);
                
                // Set some default ACLs prior to Overrides
                // Members have create privileges, guests have interact privileges
                if (isset($network->nm_status) && $network->nm_status && isset($network->nm_active) && $network->nm_active) {
                    $this->target->acl->allowed = true;
                    $this->target->acl->privilege = ViaMe_Controller_Action::ACL_WRITE;
                    $this->target->acl->filter = null;
                    $this->target->acl->recursive = true;
                }
                
                if (isset($this->member) && (($network->p_same_member_priv && ($network->p_member_id == $this->member->id)) || ($this->member->site_admin)  || (isset($this->member->profile) && $this->member->profile->site_admin))) {
                    // Owner
                    $this->target->acl->owner = true;
                    $this->target->acl->allowed = true;
                    $this->target->acl->privilege = ViaMe_Controller_Action::ACL_OWNER;
                    $this->target->acl->filter = null;
                    $this->target->acl->recursive = true;
                    $network->nm_status = true;
                    $network->nm_active = true;
                }
                else {
                    $this->_aclSwitchaRoo($network);
                }
                
                // Not allowed
                if (($this->target->acl->allowed === false) || ($this->target->acl->privilege === ViaMe_Controller_Action::ACL_DENY)) {
                    if (!$network->show_on_fail) {
                        return $this->_denied('Network Error', 'That network does not exist or is deactivated.');
                    }
                    else {
                        return $this->_unauthorized('Network Access Error', 'You are not allowed access to this network.');
                    }
                }
                
                // Copy the target ACL to network
                $this->acl->NET = clone $this->target->acl;
                
                $acl_clause = $this->db->quoteInto('(check_acl(x.acl, ?, 0, x.net_id, 0, x.module_id, x.counter, 0, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                $select = $this->db->select()
                    ->from(array('x' => 'module_matrix'),
                        array('*',
                            "array_to_string(parameter_values, '" . $this->config->delimiter . "') as parameter_values_delimited",
                            $this->db->quoteInto('(b.id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                            "$acl_clause.privilege",
                            "$acl_clause.filter",
                            "$acl_clause.recursive"
                        )
                    )
                    ->where("x.active='t'")
                    ->where('x.net_id=?', $net_id)
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
                    ->join(array('n' => 'network_networks'), 'n.id = x.net_id', array())
                    ->where('n.active=?', 't')
                    ->join(array('p' => 'profile_profiles'), 'p.id = n.profile_id', array())
                    ->where('p.active=?', 't')
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                    ->where('b.active=?', 't')
                    ->order(array('x.display_orderby', 'x.orderby', 'x.display', 'm.display', 'm.name', 'x.counter', 'm.id'));
                
                #if (!$this->target->acl->owner) {
                #    $select->where("x.acl ISNULL OR x.show_on_fail='t' OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > 0)))");
                #}
                
                $this->target->modules = $this->db->fetchAll($select);
                
                # Not needed as we are no longer using the action view helper, but are now using actionstack
                $this->view->module_modules = $this->module_modules;
                
                // Load up widgets
                $select = $this->db->select()
                    ->from(array('x' => 'widget_matrix'),
                        array('*',
                            "array_to_string(parameter_values, '" . $this->config->delimiter . "') as parameter_values_delimited"
                        )
                    )
                    ->where("x.active='t'")
                    ->where('x.net_id=?', $net_id)
                    ->join(array('w' => 'widget_widgets'), 'x.widget_id = w.id', array(
                        'w_name' => 'name',
                        'w_display' => 'display',
                        'w_description' => 'description',
                        'w_parameters' => 'parameters',
                        'w_allow_multiple' => 'allow_multiple'
                      ))
                    ->where('w.active=?', 't')
                    // Redundant as we are already have a valid via - Keep For allowed acl clause
                    ->join(array('n' => 'network_networks'), 'n.id = x.net_id', array())
                    ->where('n.active=?', 't')
                    ->join(array('p' => 'profile_profiles'), 'p.id = n.profile_id', array())
                    ->where('p.active=?', 't')
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                    ->where('b.active=?', 't')
                    ->order(array('x.orderby', 'x.display', 'w.display', 'w.name', 'x.counter', 'w.id'));
                
                $this->target->widgets = $this->db->fetchAll($select);
                
                Zend_Registry::set('network', $network);
                $this->internal->network = $network;
                $this->internal->target->space = $network;
            }
            else {
                #Zend_Debug::Dump($network);
                return $this->_denied('Network Error', 'That network does not exist or is deactivated.');
            }
        }
        else {
            #Zend_Debug::Dump($network);
            return $this->_denied('Network Error', 'That network does not exist or is deactivated.');
        }
        
        /*
        // Some Test Stuff
        $select = $this->db->select()->from(array('m' => 'network_members'))
            ->where('m.active NOTNULL')
            ->where("m.status ISNULL OR m.status='t'")
            // Placeholder
            ->join(array('n' => 'network_networks'), 'm.network_id = n.id', array('n_name' => 'name'))
            ->where('n.id=?', $net_id)
            ->join(array('p' => 'profile_profiles'), 'm.member_profile_id = p.id', array('p_name' => 'name'))
            ->where('p.active=?', 't')
            ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
            ->where('b.active=?', 't')
            ;
        
        #print $select;
        $this->view->members = $this->db->fetchAll($select);
        // End Test Stuff
        */
        
        
        // META
        $this->view->headTitle(($this->internal->network->meta_title ? $this->internal->network->meta_title : $this->internal->network->name), 'PREPEND');
        $this->view->headMeta()->setName('author', $this->internal->network->p_name);
        $this->view->headMeta()->setName('copyright', 'Copyright by ' . $this->internal->network->p_name);
        if ($this->internal->network->meta_description) {
            $this->view->headMeta()->setName('description', $this->internal->network->meta_description);
        }
        else {
            $this->view->headMeta()->setName('description', 'The ' . $this->internal->network->name . ' network at the ' . $this->community->display . " community.");
        }
        if ($this->internal->network->meta_keywords) {
            $this->view->headMeta()->setName('keywords', $this->internal->network->meta_keywords);
        }
        else {
            $this->view->headMeta()->setName('keywords', $this->view->SEO_Keywords($this->internal->network->name));
        }
        
        // Alternate Feeds        
        $link =  'http://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($network->name) . '/s' . $this->target->pre . '/system/widget/p/format';
        foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
            $this->view->headLink()->appendAlternate(
                "$link/$key/",
                $val,
                $network->name . " Network - " . strtoupper($key) . ' Feed'
            );
            $this->view->headLink()->appendAlternate(
                "$link/$key/comments/1/",
                $val,
                $network->name . " Network (With Comments) - " . strtoupper($key) . ' Feed'
            );
            $this->view->headLink()->appendAlternate(
                "$link/$key/commentsonly/1/",
                $val,
                $network->name . " Network (Comments Only) - " . strtoupper($key) . ' Feed'
            );
        }
        
        // Custom Space Style
        if ($this->internal->network->page_style) {
            $this->view->headStyle()->appendStyle(str_replace('<', '&lt;', $this->internal->network->page_style));
        }
        
        
        // Setup the Grid
        $to_display = $grid = array();
        foreach ($this->target->modules as $module) {
            if ($module->widget) {
                $to_display['m-'.$module->module_id.'-'.$module->counter] = $module;
            }
        }
        foreach ($this->target->widgets as $widget) {
            if ($widget->widget) {
                $to_display['w-'.$widget->widget_id.'-'.$widget->counter] = $widget;
            }
        }
        
        foreach (array('hd', 'ft', 'cx', 'c2', 'c3', 'c4', 'c1') as $section) {
            $grid[$section] = array();
            
            if ( ($section == 'cx') && 
                 (!(isset($this->target->space->page_layout) || $this->target->space->page_layout)) ) {
                continue;
            } 
            
            if ( (preg_match('/^c[2-4]$/', $section)) &&
                 ( (!(isset($this->target->space->page_sublayout) || $this->target->space->page_sublayout)) ||
                   ($section == 'c3' && $this->target->space->page_sublayout != 'gb' && $this->target->space->page_sublayout != 'gg') ||
                   ($section == 'c4' && $this->target->space->page_sublayout != 'gg') ) ) {
                    continue;
            } 
            
            if (isset($this->target->space->{"grid_$section"}) && $this->target->space->{"grid_$section"} != '{}') {
                foreach (explode(',', preg_replace('/^\{(.*)\}$/', '${1}', $this->target->space->{"grid_$section"})) as $part) {
                    if (isset($to_display[$part])) {
                        $grid[$section][$part] = $to_display[$part];
                        unset($to_display[$part]);
                    }
                }
            }
        }
        
        // Grid Stragglers
        if ($to_display) {
            $grid['c1'] = array_merge($grid['c1'], $to_display);
            $query = $this->db->quoteInto('UPDATE network_networks SET grid_c1 = grid_c1 || ? ::text[]', ('{' . implode(',', array_keys($to_display)) . '}'));         
            $query .= $this->db->quoteInto(' WHERE id=?', $this->target->id);
            try { $this->db->getConnection()->exec($query); } catch (Exception $e) { }
        }
        
        $this->view->grid = $grid;
        
        
        // Network Breadcrumb
        $this->_addBreadCrumb(array(
            'title' => $network->name,
            'simple' => 'Home',
            'url' => '/' . $this->view->SEO_Urlify($network->name) . '/s' . (isset($this->target->pre) ? $this->target->pre : '') . '/'
        ));
        
        
        // Render In SubLayout
        $this->view->inlineScript()->appendScript("var vm_pre = '" . $this->target->pre . "';");
        $this->_helper->ViaMe->setSubLayout('space');
        
        
        // SubModule
        if (isset($this->internal->params->submodule)
          && array_key_exists($this->internal->params->submodule, $this->module_modules)) {
            $breadcrumb_set = false;
            
            // META
            foreach ($this->target->modules as $module) {
                if ($module->m_name == $this->internal->params->submodule &&
                    ($module->counter == ($this->_getParam('mid')))) {
                    $this->view->headTitle(($module->meta_title ? $module->meta_title : ($module->display ? $module->display : $module->m_display)), 'PREPEND');
                    if ($module->meta_description) {
                        $this->view->headMeta()->setName('description', $module->meta_description);
                    }
                    else {
                        $this->view->headMeta()->setName('description', $module->m_display . "s by the " . $this->internal->network->name . ' network.');
                    }
                    if ($module->meta_keywords) {
                        $this->view->headMeta()->setName('keywords', $module->meta_keywords);
                    }
                    else {
                        $this->view->headMeta()->setName('keywords', $this->view->SEO_Keywords($module->display ? $module->display : $module->m_display));
                    }
                    
                    // Set the module from the matrix
                    $this->target->currentModule = clone $module;
                    $this->log->setEventItem('matrix_counter', $module->counter);
                    
                    // SubModule Breadcrumb
                    $this->_addBreadCrumb(array(
                        'title' => ($module->display ? $module->display : ($module->m_display ? $module->m_display : $module->m_name)),
                        'simple' => ($module->m_display ? $module->m_display : $module->m_name) . ' Module (#' . $this->_getParam('mid') . ')',
                        'url' => '/' . $this->view->SEO_Urlify($module->display ? $module->display : $module->m_display) . '/s' . (isset($this->target->pre) ? $this->target->pre : '') . '/' . $module->m_name . '/p/mid/' . $this->_getParam('mid') . '/'
                    ));
                    $breadcrumb_set = true;
                    
                    break;
                }
            }
            
            if (!$breadcrumb_set) {
                // SubModule Breadcrumb - System Modules, Perhaps
                $this->_addBreadCrumb(array(
                    'title' => ($this->module_modules[$this->internal->params->submodule]->display ? $this->module_modules[$this->internal->params->submodule]->display : $this->module_modules[$this->internal->params->submodule]->name),
                    'simple' => ($this->module_modules[$this->internal->params->submodule]->display ? $this->module_modules[$this->internal->params->submodule]->display : $this->module_modules[$this->internal->params->submodule]->name),
                    'url' => '/' . $this->view->SEO_Urlify($network->name) . '/s' . (isset($this->target->pre) ? $this->target->pre : '') . '/' . $this->internal->params->submodule . '/'
                ));
            }
            
            $this->_helper->actionStack(
                ((isset($this->internal->params->subaction) && $this->internal->params->subaction != '') ? $this->internal->params->subaction : 'index'),
                ((isset($this->internal->params->subcontroller) && $this->internal->params->subcontroller != '') ? $this->internal->params->subcontroller : 'index'),
                ($this->internal->params->submodule),
                (array) $this->internal->params
            );
        }
        else {
            $this->view->headLink(array(
                'rel' => 'canonical',
                'href' => ($this->view->SEO_Urlify($network->name) ? '/' . $this->view->SEO_Urlify($network->name) . '/s' : '') . (isset($this->target->pre) ? $this->target->pre : '') . '/',
                'title' => $this->view->escape($network->name)
            ));
            
            if (!(count($this->target->modules) || count($this->target->widgets))) {
                $this->_helper->ViaMe->setSubLayout('default');
                // Empty space
                if ($this->target->acl->owner || $this->target->acl->privilege >= self::ACL_OWNER) {
                    $this->render('setup');
                }
                else {
                    $this->render('empty');
                }
            } elseif ($this->db->fetchOne('SELECT 1 FROM module_template WHERE ' . strtolower($this->target->type) . '_id=? AND active LIMIT 1', array($this->target->id))) {
                // No content in this space
                $this->view->headMeta()->setName('robots', 'all');
            }
        }
    }
    
    
    public function __call($method, $args)
    {
        return $this->_forward('index');
    }
}
