<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Action extends Zend_Controller_Action
{
    /*
        Important Notes:
        
        Use $this->getRequest()->... and NOT the built in Action Helper functions
        Use $this->getRequest()->getParam('paramname') and NOT $this->_getParam('paramname')
        
        Do not overload the preDispatch() method.  Use only config flags as the preDisptach
        method has 'return $this->_forward()' calls and will return to the calling action.
        
        Forwarding ($this->_forward()) should only be done in the main action function.
        The reason is, if we forward in the init method, the viewRenderer doesn't get a chance to
        update the viewPathStack and only works with forwards to actions that use the default
        viewRenderer render method and default file (ie. $this->renderScript() doesn't work).
    */
    
    protected $_routeThruDefault = false;
    protected $_memberDefined = false;
    protected $_moduleInMatrix = false;
    protected $_modObjectCheck = false;
    protected $_modObject = null;
    protected $_minPrivilege = null;
    protected $_defaultAllow = false;
    protected $_mustBeOwner = false;
    
    protected $_tableName = null;
    
    const ACL_DENY                  =   0;
    const ACL_READ                  =  10;
    const ACL_INTERACT              =  20;
    const ACL_MODERATE              =  30;
    const ACL_WRITE                 =  40;
    const ACL_EDIT                  =  50;
    const ACL_DELETE                =  60;
    const ACL_ADMIN                 =  90;
    const ACL_OWNER                 = 100;
    
    
    public function init()
    {
        // Load up the registry variables
        $this->registryLoader($this);
        
        Zend_Controller_Action_HelperBroker::addPrefix('ViaMe_Controller_Action_Helper');
        $this->view->addHelperPath($this->vars->APP_PATH . '/library/ViaMe/View/Helper', 'ViaMe_View_Helper');
        
        // Set the module table name
        if (!$this->_tableName) { $this->_tableName = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getModuleName() . 's'; }
        
        # Set this in the bootstrap
        #$this->view->doctype('XHTML1_STRICT');
        #$this->view->doctype($this->config->doctype);
        
        /*
        // Set the language
        $languages = array();
        $languages = Zend_Locale::getOrder(); // Browser Based Default
        
        // Set the members first as it will get overridden next by url based
        if (isset($this->member)) {
            $languages = array_merge(array($this->member->language => 1), $languages); // Member Based
            $languages[$this->member->language] = 1;
        }
        if (isset($this->vars->language)) {
            $languages = array_merge(array($this->vars->language => 1), $languages); // URL Based
            $languages[$this->vars->language] = 1;
        }
        
        // Get the registry translator object
        if (!Zend_Registry::isRegistered('translator')) {
            Zend_Registry::set('translator', new StdClass);
            $this->translator = Zend_Registry::get('translator');
        }
        
        # CACHE - Max
        // Set the translators
        if (!isset($this->translate)) {
            if (isset($this->translator->{$this->getRequest()->getModuleName()})) {
                // Load From Registry
                $this->translate = $this->translator->{$this->getRequest()->getModuleName()};
            }
            else {
                // Try to find a language match
                // OPTIONAL - Can put all of the translation into the database
                $dir = $this->vars->APP_PATH . '/modules/' . $this->getRequest()->getModuleName() . '/languages/';
                
                foreach ($languages as $language => $quality) {
                    $parts = explode('_', $language);
                    
                    if (is_dir($dir . $language)) {
                        try {
                            $this->translate = new Zend_Translate('array', $dir . $language, $language);
                        } catch (Exception $e) { }
                        break;
                    }
                    elseif (is_dir($dir . $parts[0])) {
                        try {
                            $this->translate = new Zend_Translate('array', $dir . $parts[0], $parts[0]);
                        } catch (Exception $e) { }
                        break;
                    }
                }
                
                if (!isset($this->translate)) {
                    try {
                        $this->translate = new Zend_Translate('array', $dir, null, array('disableNotices' => true, 'scan' => Zend_Translate::LOCALE_DIRECTORY)); // Blank Default Translate Object
                    } catch (Exception $e) { }
                }
                
                // Save it in the registry
                if (isset($this->translate)) {
                    #$this->translate->addTranslation('/usr/local/www/websites/viame.com/application/modules/default/views/scripts/test.en', 'en');
                    $this->translator->{$this->getRequest()->getModuleName()} = $this->translate;
                }
            }
            
            if (!$this->_tableName) { $this->_tableName = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getModuleName() . 's'; }
        }
        
        // Set the view objects
        if (isset($this->translate)) {
            $this->view->translate()->setTranslator($this->translate);
            Zend_Registry::set('Zend_Translate', $this->translate);
        }
        */

        
        # CACHE - Max
        // Set the translators
        if (!isset($this->translate)) {
            // Set the language
            $languages = array();
            $languages = Zend_Locale::getOrder(); // Browser Based Default
            
            // Set the members first as it will get overridden next by url based
            if (isset($this->member)) {
                $languages = array_merge(array($this->member->language => 1), $languages); // Member Based
                $languages[$this->member->language] = 1;
            }
            if (isset($this->vars->language)) {
                $languages = array_merge(array($this->vars->language => 1), $languages); // URL Based
                $languages[$this->vars->language] = 1;
            }
            
            // Try to find a language match
            // OPTIONAL - Can put all of the translation into the database
            $dir = $this->vars->APP_PATH . '/layouts/' . $this->community->name . '/languages/';
            
            foreach ($languages as $language => $quality) {
                $parts = explode('_', $language);
                
                if (is_dir($dir . $language)) {
                    try {
                        $this->translate = new Zend_Translate('array', $dir . $language, $language);
                    } catch (Exception $e) { }
                    break;
                }
                elseif (is_dir($dir . $parts[0])) {
                    try {
                        $this->translate = new Zend_Translate('array', $dir . $parts[0], $parts[0]);
                    } catch (Exception $e) { }
                    break;
                }
            }
            
            if (!isset($this->translate)) {
                try {
                    $this->translate = new Zend_Translate('array', $dir, null, array('disableNotices' => true, 'scan' => Zend_Translate::LOCALE_DIRECTORY)); // Blank Default Translate Object
                } catch (Exception $e) { }
            }
            
            // Save it in the registry
            if (isset($this->translate)) {
                #$this->translate->addTranslation('/usr/local/www/websites/viame.com/application/modules/default/views/scripts/test.en', 'en');
                #$this->translator->{$this->getRequest()->getModuleName()} = $this->translate;
                
                // Set the view objects
                $this->view->translate()->setTranslator($this->translate);
                Zend_Registry::set('Zend_Translate', $this->translate);
            }
        }
        
        if ($this->getRequest()->getModuleName() && array_key_exists($this->getRequest()->getModuleName(), $this->module_modules)) {
            $this->module = $this->module_modules[$this->getRequest()->getModuleName()];
            $this->log->setEventItem('module_id', $this->module->id);
        }
        
        // Load some view variables
        foreach (array('Module', 'Controller', 'Action') as $key) {
            if ($this->getRequest()->{"get${key}Name"}()) { $this->view->{strtolower($key)} = strtolower($this->getRequest()->{"get${key}Name"}()); }
        }
        
        // Make internal variables available to view object
        if (!isset($this->view->internal)) {
            if (!isset($this->internal)) {
                $this->internal = new StdClass;
                Zend_Registry::set('internal', $this->internal);
            }
            
            foreach (array('config', 'vars', 'log', 'params', 'cookie', 'locale', 'module_modules', 'widget_widgets', 'member', 'community', 'via', 'network', 'target', 'acl', 'navigation') as $temp) {
                if (isset($this->{$temp})) {
                    $this->internal->{$temp} = &$this->{$temp}; // Reference
                }
            }
            
            $this->view->internal = $this->internal;
        }
        #Zend_Debug::Dump($this->getRequest()->getActionName());
    }
    
    
    public function preDispatch()
    {
        // Controllers should generally be called in through a default module controller (com, net, or via)
        if (($this->_routeThruDefault) && (!isset($this->target->type)) && ($this->getRequest()->getModuleName() != 'default')) {
            if (!isset($this->internal->params)) { $this->internal->params = new StdClass; }
            foreach (array('Module', 'Controller', 'Action') as $key) {
                if ($this->getRequest()->{"get${key}Name"}()) { $this->internal->params->{'sub' . strtolower($key)} = strtolower($this->getRequest()->{"get${key}Name"}()); }
            }
            
            $this->view->setScriptPath(null); // Reset the ScriptPath
            return $this->_forward('index', 'index', 'default');
        }
        
        // Do they need to be logged in
        if (($this->_memberDefined) && (!isset($this->member))) {
            if ($this->_getParam('vmpd_jlo')) {
                $this->_redirect('/');
            }
            else {
                $this->_vmredirect('/member/login/p/errorcode/401/' . ($this->_getParam('signup_entrance') ? 'signup_entrance/' . $this->_getParam('signup_entrance') . '/' : ''));
            }
        }
        
        // Check to see if this module is in the list of modules
        if ($this->_moduleInMatrix) {
            if (isset($this->target->modules)) {
                foreach ($this->target->modules as $module) {
                    if (($module->m_name == $this->getRequest()->getModuleName()) && ($module->counter == $this->_getParam('mid'))) {
                        $modacl = $module;
                        break;
                    }
                }
                
                if (!isset($modacl)) {
                    // Module wasn't found in the target modules list
                    return $this->_denied('Module Error', 'There was an error when requesting that module.');
                }
                else {
                    $this->_aclSwitchaRoo($modacl);
                    
                    if ($this->_modObjectCheck) {
                        if (!isset($this->_modObject) || !is_object($this->_modObject)) {
                            return $this->_denied('Item Request Error', 'There was an error when requesting that item.');
                        }
                        else {
                            $this->_aclSwitchaRoo($this->_modObject);
                            
                            // Are they specifically denied
                            if (($this->target->acl->allowed === false) || ($this->target->acl->privilege === self::ACL_DENY)) {
                                if (!$this->_modObject->show_on_fail && isset($this->member)) {
                                    return $this->_denied('Item Error', 'There was an error when requesting that item.');
                                }
                                else {
                                    return $this->_unauthorized('Item Access Error', 'You are not allowed access to this item.');
                                }
                            }
                        }
                    }
                    else {
                        // Are they specifically denied
                        if (($this->target->acl->allowed === false) || ($this->target->acl->privilege === self::ACL_DENY)) {
                            if (!$modacl->show_on_fail && isset($this->member)) {
                                return $this->_denied('Module Error', 'There was an error when requesting that module.');
                            }
                            else {
                                return $this->_unauthorized('Module Access Error', 'You are not allowed access to this module.');
                            }
                        }
                    }
                }
            }
            else {
                return $this->_denied('Module Error', 'There was an error when requesting that module.');
            }
        }
        elseif ($this->_modObjectCheck) {
            if (!$this->_modObject) {
                return $this->_denied('Item Edit Error', 'There was an error when requesting that item.');
            }
            else {
                $this->_aclSwitchaRoo($this->_modObject);
                
                // Are they specifically denied
                if (($this->target->acl->allowed === false) || ($this->target->acl->privilege === self::ACL_DENY)) {
                    if (!$this->_modObject->show_on_fail && isset($this->member)) {
                        return $this->_denied('Item Edit Error', 'There was an error when requesting that item.');
                    }
                    else {
                        return $this->_unauthorized('Item Access Error', 'You are not allowed access to this item.');
                    }
                }
            }
        }
        
        // Do they have sufficient privileges
        if ((isset($this->_minPrivilege) && !($this->target->acl->privilege >= $this->_minPrivilege) && !($this->_defaultAllow && ($this->target->acl->allowed === null || $this->target->acl->privilege === null))) ||
            ($this->_mustBeOwner && !($this->target->acl->owner))) {
            return $this->_unauthorized('Access Error', 'You do not have sufficient privileges to access this area.');
        }
        
        // Set A Breadcrumb to view controller
        if ($this->_modObject && $this->getRequest()->getControllerName() != 'index') {
            $url = null;
            
            if (isset($this->_modObject->title) && $this->_modObject->title) { $url = '/' . $this->view->SEO_Urlify($this->_modObject->title) . '/s'; }
            
            if (isset($this->masked) && $this->masked) {
                $url .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->_getParam('mid') . '/id/' . $this->_modObject->counter . '/';
            }
            else {
                if ($this->_modObject->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->_modObject->com_id != $this->internal->community->id)) { $url .= '/com/' . $this->_modObject->com_id; }
                elseif ($this->_modObject->net_id) { $url .= '/net/' . $this->_modObject->net_id; }
                elseif ($this->_modObject->via_id) { $url .= '/via/' . $this->_modObject->via_id; }
                
                $url .= '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->_modObject->matrix_counter . '/id/' . $this->_modObject->counter . '/';
            }
            
            $this->_addBreadCrumb(array(
                'title' => $this->_modObject->title,
                'simple' => ucfirst($this->getRequest()->getModuleName()) . ' (#' . $this->_modObject->counter . ')',
                'url' => $url
            ));
        }
        
        if (!in_array($this->getRequest()->getControllerName(), array('index', 'view', 'widget', 'comments', 'ratings'))) {
            $this->_addBreadCrumb(array(
                'title' => ucfirst($this->getRequest()->getControllerName()) . ' ' . ucfirst($this->getRequest()->getModuleName()),
                'simple' => ucfirst($this->getRequest()->getControllerName()) . ' ' . ucfirst($this->getRequest()->getModuleName()),
                'url' => ''
            ));
        }
    }
    
    
    /*
    public function postDispatch()
    {
        
    }
    */
    
    
    protected function _buildComplexQuery($table = null, $object = null, $columns = null)
    {
        $select = null;
        if ($table && $object) {
            $acl_clause = $this->db->quoteInto('(check_acl(obj.acl, ?, obj.com_id, obj.net_id, obj.via_id, obj.module_id, obj.matrix_counter, obj.counter, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            
            $select = $this->db->select()
                ->from(array('obj' => $table),
                    array_merge(
                        (($columns && count($columns)) ? $columns : array('*')),
                        array(
                            "GREATEST( obj.creation, obj.published, ((CASE WHEN obj.modified ISNULL THEN obj.activation ELSE null END)::timestamp) ) AS published_display_date",
                            "COALESCE((obj.activation <= 'now'), 't'::bool) AS published_display_activated",
                            "COALESCE((obj.expiration <= 'now'), 'f'::bool) AS published_display_expired",
                            "$acl_clause.allowed",
                            "$acl_clause.privilege",
                            "$acl_clause.filter",
                            "$acl_clause.recursive"
                        )
                    )
                    # Old way
                    #array('*',
                    #    "$acl_clause.allowed",
                    #    "$acl_clause.privilege",
                    #    "$acl_clause.filter",
                    #    "$acl_clause.recursive"
                    #)
                )
                
                ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter',
                    array(
                        'x_interactive' => 'interactive',
                        'x_display' => 'display'
                    )
                )
                ->where('x.active=?', 't')
                
                ->join(array('m' => 'module_modules'), 'x.module_id=m.id',
                    array(
                        'm_display' => 'display',
                        'm_name' => 'name'
                    )
                )
                ->where('m.active=?', 't')
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active',
                        'p_picture_url' => 'picture_url',
                        'p_total_followers_count' => 'total_followers_count',
                        'p_total_following_count' => 'total_following_count'
                    )
                )
                ->where('p.active=?', 't')
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                        'b_id' => 'id',
                        'b_site_admin' => 'site_admin',
                        'b_active' => 'active',
                        'b_email' => 'email'
                    )
                )
                ->where('b.active=?', 't')
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                        'c_id' => 'id',
                        'c_name' => 'name',
                        'c_hostname' => 'hostname'
                    )
                )
                ->where('c.active=?', 't');
                
                // MOVED THIS ALL - COUNTS ARE IN module_views_counts AND TOTAL IS NOW IN module_template WITH ITEM
                // View Counters
                #->joinLeft(array('svcm' => 'system_view_count_matrix'), 'obj.com_id=svcm.com_id AND obj.net_id=svcm.net_id AND obj.via_id=svcm.via_id AND obj.module_id=svcm.module_id AND #obj.matrix_counter=svcm.matrix_counter AND obj.counter=svcm.item_counter',
                #    array(
                #        'total_view_counter' => 'total_view_counter'
                #    )
                #);
            
            if (isset($this->member)) {
                $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                    array(
                        'vc_status' => 'status',
                        'vc_display' => 'display'
                    )
                );
                $select->joinLeft(array('pfm' => 'profile_follow_matrix'), $this->db->quoteInto("p.id = pfm.follow_profile_id AND pfm.profile_id=? AND pfm.status='t' AND pfm.active='t'", $this->member->profile->id),
                    array(
                        'pfm_following' => 'active'
                    )
                );
            }
            
            // What are we actually fetching??
            $this->masked = false;
            
            if (isset($object->module_id) && $object->module_id) {
                $select->where('obj.module_id=?', $object->module_id);
            }
            
            if (((isset($object->community_mask) && $object->community_mask) || (isset($object->network_mask) && $object->network_mask) || (isset($object->profile_mask) && $object->profile_mask)) && ($object->mask_counter)) {
                $select->where('m.allow_mask=?', 't');
                $this->masked = true;
                switch($this->target->type) {
                    case 'VIA':
                        $tempwhere = 'profile';
                        break;
                    case 'NET':
                        $tempwhere = 'network';
                        break;
                    default:
                        $tempwhere = 'community';
                        break;
                }
                
                if ($object->community_mask) {
                    $select->where('obj.com_id=?', $object->community_mask);
                    $select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                }
                elseif ($object->network_mask) {
                    $select->where('obj.net_id=?', $object->network_mask);
                    $select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                }
                elseif ($object->profile_mask) {
                    $select->where('obj.via_id=?', $object->profile_mask);
                    $select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                }
                
                if (isset($object->mask_counter) && $object->mask_counter && $object->mask_counter != '*') {
                    $select->where('obj.matrix_counter=?', $object->mask_counter);
                }
                
                // ACL For Masking - Anonymous Read Only
                $select->where('obj.active=?', 't');
                $select->where('obj.activation ISNULL OR obj.activation <= now()');
                $select->where('obj.expiration ISNULL OR obj.expiration >= now()');
                
                if (!($this->target->acl->privilege >= self::ACL_READ && $this->target->acl->recursive)) {
                    $select->where("obj.acl ISNULL OR obj.show_on_fail='t' OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > 0)))");
                }
            }
            else {
                $tempclause = '(';
                switch($this->target->type) {
                    case 'VIA':
                        #$select->where('obj.via_id=?', $this->target->id);
                        $tempclause .= $this->db->quoteInto('obj.via_id=?', $this->target->id);
                        $tempwhere = 'profile';
                        break;
                    case 'NET':
                        #$select->where('obj.net_id=?', $this->target->id);
                        $tempclause .= $this->db->quoteInto('obj.net_id=?', $this->target->id);
                        $tempwhere = 'network';
                        break;
                    case 'COM':
                        #$select->where('obj.com_id=?', $this->target->id);
                        $tempclause .= $this->db->quoteInto('obj.com_id=?', $this->target->id);
                        $tempwhere = 'community';
                        break;
                }
                
                if (isset($object->counter) && $object->counter && $object->counter != '*') {
                    $tempclause .= ' AND ' . $this->db->quoteInto('obj.matrix_counter=?', $object->counter);
                }
                $tempclause .= ')';
                
                if (($this->getRequest()->getControllerName() == 'index' || $this->getRequest()->getControllerName() == 'widget') && isset($object->allow_in_flow) && $object->allow_in_flow && ((isset($object->allow_community_inflow) && $object->allow_community_inflow) || (isset($object->allow_network_inflow) && $object->allow_network_inflow) || (isset($object->allow_profile_inflow) && $object->allow_profile_inflow))) {
                    $tempclause .= " OR (m.allow_flow='t' AND x.allow_out_flow AND ";
                    
                    // Hook in the denies first with AND
                    if (isset($object->deny_community_inflow) && !is_null($object->deny_community_inflow) && !($object->deny_community_inflow == '{}') && $object->deny_community_inflow) {
                        #$tempclause .= "NOT ('" . $object->deny_community_inflow . "' @> ARRAY[obj.com_id]::bigint[]) AND ";
                        
                        #$tempclause .= "NOT (" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->deny_community_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.com_id, x.counter]] :: bigint[]) AND ";
                        
                        $tempclause .= "(ARRAY[obj.com_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->deny_community_inflow) . "') AND ";
                    }
                    if (isset($object->deny_network_inflow) && !is_null($object->deny_network_inflow) && !($object->deny_network_inflow == '{}') && $object->deny_network_inflow) {
                        #$tempclause .= "NOT ('" . $object->deny_network_inflow . "' @> ARRAY[obj.net_id]::bigint[]) AND ";
                        
                        #$tempclause .= "NOT (" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->deny_network_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.net_id, x.counter]] :: bigint[]) AND ";
                        
                        $tempclause .= "(ARRAY[obj.net_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->deny_network_inflow) . "') AND ";
                    }
                    if (isset($object->deny_profile_inflow) && !is_null($object->deny_profile_inflow) && !($object->deny_profile_inflow == '{}') && $object->deny_profile_inflow) {
                        #$tempclause .= "NOT ('" . $object->deny_profile_inflow . "' @> ARRAY[obj.via_id]::bigint[]) AND ";
                        
                        #$tempclause .= "NOT (" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->deny_profile_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.via_id, x.counter]] :: bigint[]) AND ";
                        
                        $tempclause .= "(ARRAY[obj.via_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->deny_profile_inflow) . "') AND ";
                    }
                    if (isset($object->deny_via_user_inflow) && !is_null($object->deny_via_user_inflow) && !($object->deny_via_user_inflow == '{}') && $object->deny_via_user_inflow) {
                        $tempclause .= "(NOT ('" . $object->deny_via_user_inflow . "' @> ARRAY[obj.profile_id] :: bigint[])) AND ";
                    }
                    
                    
                    if (($this->target->type == 'COM') && (is_null($object->allow_community_inflow)) && ($object->allow_profile_inflow == '{}')) {
                        // Allow all profiles to flow in
                        //  allow_community_inflow = null
                        //  allow_profile_inflow = '{}'
                        #$tempclause2[] = 'p.community_id NOTNULL';
                        $tempclause2[] = 'obj.net_id = 0';
                    }
                    elseif (($this->target->type == 'COM') && ($object->allow_community_inflow == '{}') && ($object->allow_profile_inflow == '{}')) {
                        // Allow all profiles that are members of this or children community to flow in
                        //  allow_community_inflow = '{}'
                        //  allow_profile_inflow = '{}'
                        $wheres = array();
                        $wheres[] = $this->db->quoteInto('p.community_id=?', $this->community->id);
                        foreach ($this->VMAH->ePgArray($this->community->children_ids_array) as $temp) {
                            $wheres[] = $this->db->quoteInto('p.community_id=?', $temp);
                        }
                        $tempclause2[] = $this->db->quoteInto("(obj.com_id <> ? AND obj.net_id = 0 AND (" . implode(' OR ', $wheres) . '))', $this->community->id);
                        
                        #$tempclause2[] = $this->db->quoteInto("obj.com_id <> ? AND obj.net_id = 0 AND p.community_id IN (SELECT * FROM recursive_find('system_communities', 'id', 'parent_id', CAST(? AS bigint), 't'))", $this->community->id);
                    }
                    elseif (($this->target->type == 'COM') && ($object->allow_community_inflow == '{' . $this->community->id . '}') && ($object->allow_profile_inflow == '{}')) {
                        // Allow all profiles that are members of the community to flow in
                        //  allow_community_inflow = '{cid}'
                        //  allow_profile_inflow = '{}'
                        $tempclause2[] = $this->db->quoteInto('(obj.com_id=0 AND obj.net_id = 0 AND p.community_id=?)', $this->community->id);
                    }
                    elseif ($object->allow_community_inflow) {
                        #$tempclause2[] = "'" . $object->allow_community_inflow . "' && ARRAY[obj.com_id] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[]";
                        
                        #$tempclause2[] = "(" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->allow_community_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.com_id, x.counter]] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        
                        $tempclause2[] = "(ARRAY[obj.com_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->allow_community_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                    }
                    
                    if ($object->allow_network_inflow) {
                        #$tempclause2[] = "('" . $object->allow_network_inflow . "' && ARRAY[obj.net_id] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        
                        #$tempclause2[] = "(" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->allow_network_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.net_id, x.counter]] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        
                        $tempclause2[] = "(ARRAY[obj.net_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->allow_network_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                    }
                    if ($object->allow_profile_inflow) {
                        #$tempclause2[] = "('" . $object->allow_profile_inflow . "' && ARRAY[obj.via_id] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        
                        #$tempclause2[] = "(" . preg_replace(array('/{/', '/}/', '/NULL/'), array('ARRAY[', ']', 'x.counter'), $object->allow_profile_inflow) . " :: bigint[] @> ARRAY[ARRAY[obj.via_id, x.counter]] AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        
                        $tempclause2[] = "(ARRAY[obj.via_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $object->allow_profile_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                    }
                    
                    if ($tempclause2) { $tempclause .= '(' . implode(' OR ', $tempclause2) . ')'; }
                    $tempclause .= ')';
                }
                
                if (!(isset($object->overrideSpace) && $object->overrideSpace)) {
                    $select->where($tempclause);
                }
                
                // ACL Standard
                if ($this->target->acl->privilege >= self::ACL_EDIT) {
                    $select->where('obj.active NOTNULL');
                }
                elseif (isset($this->member)) {
                    // Allowing pulling of own objects regardless
                    $select->where("obj.active NOTNULL AND (p.id=? OR (obj.active='t' AND (obj.activation ISNULL OR obj.activation <= now()) AND (obj.expiration ISNULL OR obj.expiration >= now())))", $this->member->profile->id);
                }
                else {
                    $select->where('obj.active=?', 't');
                    $select->where('obj.activation ISNULL OR obj.activation <= now()');
                    $select->where('obj.expiration ISNULL OR obj.expiration >= now()');
                }
                
                if ($this->_minPrivilege && (!($this->target->acl->privilege >= $this->_minPrivilege && $this->target->acl->recursive))) {
                    $select->where("obj.acl ISNULL OR obj.show_on_fail='t' OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > ".$this->_minPrivilege.")))");
                }
            }
        }
        
        return $select;
    }
    
    
    protected function _aclSwitchaRoo($obj)
    {
        if (isset($obj)) {
            if (!$this->target->acl->owner) {
                if ($obj->privilege > $this->target->acl->privilege) {
                    $this->target->acl->allowed = $obj->allowed;
                    $this->target->acl->privilege = $obj->privilege;
                    $this->target->acl->filter = $obj->filter;
                    $this->target->acl->recursive = $obj->recursive;
                }
                elseif (!$this->target->acl->recursive && (!($obj->allowed === null))) {
                    $this->target->acl->allowed = $obj->allowed;
                    $this->target->acl->privilege = $obj->privilege;
                    $this->target->acl->filter = $obj->filter;
                    $this->target->acl->recursive = $obj->recursive;
                }
            }
        }
    }
    
    
    protected function _addBreadCrumb($breadcrumb)
    {
        if (isset($breadcrumb) && is_array($breadcrumb) && Zend_Registry::isRegistered('breadcrumbs')) {
            $breadcrumbs = Zend_Registry::get('breadcrumbs');
            $breadcrumbs[] = $breadcrumb;
            Zend_Registry::set('breadcrumbs', $breadcrumbs);
        }
        
    }
    
    
    protected function _denied($title = null, $message = null)
    {
        return $this->_forward('error', 'error', 'default', array('title' => $title, 'message' => $message));
    }
    
    
    protected function _unauthorized($title, $message)
    {
        return $this->_forward('error', 'error', 'default', array('errorcode' => 401, 'title' => $title, 'message' => $message,
            'denyParams' => array(
                'type' => $this->target->type,
                'id' => $this->target->id,
                'mod' => (($this->getRequest()->getModuleName() != 'default') ? $this->module_modules[$this->getRequest()->getModuleName()]->id : 0),
                'mid' => (($this->getRequest()->getModuleName() != 'default' && $this->_getParam('mid', 0)) ? $this->_getParam('mid', 0) : 0),
                'iid' => (($this->getRequest()->getModuleName() != 'default' && $this->_getParam('mid', 0) && $this->_getParam('id', 0)) ? $this->_getParam('id', 0) : 0),
                'priv' => ($this->_minPrivilege ? $this->_minPrivilege : self::ACL_READ)
            )
        ));
        #print $this->_getParam('mid', 0);
    }
    
    
    protected function _autoredirect($url)
    {
        // Use this function after action is finished
        
        // If the redirect parameter is set, use that
        if ($this->_getParam('redirect') &&
        	// DO NOT MATCH A REDIRECT TO SELF
        	! ( preg_match('/^(https*:\/\/(' . $this->getRequest()->getServer('HTTP_HOST') . '|' . $this->getRequest()->getServer('SERVER_NAME') . '))*' . preg_quote($this->getRequest()->getServer('REQUEST_URI'), '/') . '/i', $this->_getParam('redirect')) )
        ) {
            $url = $this->_getParam('redirect');
        }
        elseif (!$url && isset($this->target->pre)) {
            $url = $this->target->pre . '/';
        }
        
        // Fallback is to go to home
        $this->_helper->redirector->gotoUrl($url ? $url : '/');
    }
    
    
    protected function _vmredirect($url)
    {
        // Use this function to redirect to another function, returning to self and saving redirect parameter
        #$url .= '?redirect=' . urlencode($this->getRequest()->getServer('SCRIPT_URL'));
        $url .= '?redirect=' . urlencode($this->getRequest()->getServer('REQUEST_URI'));
        if ($this->_getParam('redirect') && !strpos($this->getRequest()->getServer('REQUEST_URI'), 'redirect=')) {
            $url .= urlencode((strpos($this->getRequest()->getServer('REQUEST_URI'), '?') ? '&' : '?') . 'redirect=' . $this->_getParam('redirect'));
        }        
        
        // Fallback is to go to home
        $this->_helper->redirector->gotoUrl($url ? $url : '/');
    }
    
    
    public static function registryLoader($class = null)
    {
        if ($class === null) {
            return false;
        }
        
        if (is_object($class)) {
            foreach (Zend_Registry::getInstance() as $index => $value) {
                if (!preg_match('/^Zend/', $index) && !isset($class->{$index})) {
                    $class->{$index} = $value;
                }
            }
        }
        
        return true;
    }
}
