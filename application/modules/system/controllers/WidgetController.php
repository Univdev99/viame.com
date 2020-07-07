<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_WidgetController extends ViaMe_Controller_Default_Widget
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction($select = null)
    {
        /* 
           BIG ACL PROBLEM
           No checks on modules or space - SO...  If a module has ACL that denies, it will not deny those items as this only check item level ACL
        */
        $table_name = 'module_template';
        $selects = array();
        $fields = array('obj.creation', 'obj.modified', 'obj.published', 'obj.com_id', 'obj.net_id', 'obj.via_id', 'obj.module_id', 'obj.matrix_counter', 'obj.counter', 'obj.profile_id', 'obj.title', 'obj.meta_keywords', 'obj.summary', 'obj.content', 'obj.symbols', 'obj.active', 'obj.total_views_count');
        
        // NOMID
        if ($this->_getParam('nomid')) {
            $table_name = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getModuleName() . 's';
            $fields = array('*');
        }
        
        /* Start Custom By Profile or by Symbols or by Follow */
            $profiles_select_clause = $symbols_select_clause = $follows_select_clause = $follows_comments_select_clause = '';
            // Profile ID clause
            if ($this->_getParam('profile_id')) {
                $wheres = array();
                foreach (preg_split('/[,\s]+/', $this->_getParam('profile_id')) as $profile_id) {
                    $profile_id = trim($profile_id);
                    if (is_numeric($profile_id)) {
                        $wheres[] = $this->db->quoteInto('obj.profile_id=?', $profile_id);
                    }
                }
                if (count($wheres)) {
                    $profiles_select_clause = join(' OR ', $wheres);
                }
            }
            
            // Look up by symbol or symbol id
            if ($this->_getParam('symbol') || $this->_getParam('symbol_id')) {
                $select_symbols = $this->db->select()
                    ->from(array('obj' => 'quote_view_symbol_matrix'))
                    ->where('obj.active = ?', 't');
                    
                $wheres = array();
                if ($this->_getParam('symbol')) {
                    foreach (preg_split('/[,\s]+/', $this->_getParam('symbol')) as $symbol) {
                        $symbol = trim($symbol);
                        if ($symbol) {
                            $wheres[] = $this->db->quoteInto('obj.internal_symbol=?', $symbol);
                        }
                    }
                }
                if ($this->_getParam('symbol_id')) {
                    foreach (preg_split('/[,\s]+/', $this->_getParam('symbol_id')) as $symbol_id) {
                        $symbol_id = trim($symbol_id);
                        if (is_numeric($symbol_id)) {
                            $wheres[] = $this->db->quoteInto('obj.id=?', $symbol_id);
                        }
                    }
                }
                if (count($wheres)) {
                    $select_symbols->where(implode(' OR ', $wheres))->limit(100);
                }
                else {
                    $select_symbols->limit(1);
                }
                
                $symbols = $this->db->fetchAll($select_symbols);
                if (count($symbols)) {
                    $wheres = array();
                    foreach ($symbols as $symbol) {
                        $wheres[] = '(' . $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $symbol->id) . 'obj.search @@ plainto_tsquery(' . $this->db->quote($symbol->internal_symbol) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $symbol->name))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($symbol->internal_symbol) . " || E'((,|\\\\s)+|$)'))" . ')';
                    }
                    if (count($wheres)) { $symbols_select_clause = implode(' OR ', $wheres); }    
                }
            }
            
            // Look up by follows - Profiles or symbols
            if ($this->_getParam('following')) {
                $wheres = array();
                
                if ($this->_getParam('following') == 'profiles' || $this->_getParam('following') == 'profilesandsymbols') {
                    $wheres[] = $this->db->quoteInto("obj.profile_id IN (SELECT follow_profile_id FROM profile_follow_matrix WHERE status='t' AND active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                    $follows_comments_select_clause = $this->db->quoteInto("obj.profile_id IN (SELECT follow_profile_id FROM profile_follow_matrix WHERE status='t' AND active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                }
                if ($this->_getParam('following') == 'symbols' || $this->_getParam('following') == 'profilesandsymbols') {
                    $wheres[] = $this->db->quoteInto("obj.symbols && (SELECT array_accum(symbol_id) FROM quote_follow_matrix WHERE active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                }
                
                if (count($wheres)) { $follows_select_clause = implode(' OR ', $wheres); }
            }
        /* End Custom By Profile or by Symbols or by Follow */
        
        $system_comments_select = $this->db->select()
            ->from(array('obj' => 'system_comments'),
                array('creation', 'updated AS modified', new Zend_Db_Expr('null::timestamp AS published'), 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter AS counter', 'profile_id', new Zend_Db_Expr("'[Comment] ' || obj.title AS title"), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), 'content', new Zend_Db_Expr('null'), 'active', new Zend_Db_Expr('null'), 'creation AS published_display_date', new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'))
            )
            ->where('obj.active=?', 't')
            
            ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter',
                array(
                    'x_interactive' => 'interactive',
                    'x_display' => 'display'
                )
            )
            ->where('x.active=?', 't')
            ->where('x.interactive=?', 't')
            
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
            
            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                array(
                'b_id' => 'id',
                'b_site_admin' => 'site_admin',
                'b_active' => 'active',
                'b_email' => 'email'
                )
            )
            ->where('b.active NOTNULL')
            
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                array(
                'c_id' => 'id',
                'c_name' => 'name',
                'c_hostname' => 'hostname'
                )
            )
            ->where('c.active=?', 't')
            ->where('obj.' . strtolower($this->target->type) . '_id=?', $this->target->id);
        if (isset($this->member)) {
            $system_comments_select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                array(
                    'vc_status' => 'status',
                    'vc_display' => 'display'
                )
            );
            $system_comments_select->joinLeft(array('pfm' => 'profile_follow_matrix'), $this->db->quoteInto("p.id = pfm.follow_profile_id AND pfm.profile_id=? AND pfm.status='t' AND pfm.active='t'", $this->member->profile->id),
                array(
                    'pfm_following' => 'active'
                )
            );
        }
        
        $recursive_read_acl = false;
        if (
            (!$this->_getParam('mid')) &&
            ((isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))
            ||
            (isset($this->_minPrivilege) && $this->target->acl->privilege >= $this->_minPrivilege && $this->target->acl->recursive))
           ) {
            // Load Up All Space Content and All Space Comments
            $recursive_read_acl = true;
            if (!$this->_getParam('commentsonly')) {
                $temp_select = $this->_buildComplexQuery($table_name, new StdClass(), $fields);
                if ($profiles_select_clause) { $temp_select->where($profiles_select_clause); }
                if ($symbols_select_clause) { $temp_select->where($symbols_select_clause); }
                if ($follows_select_clause) { $temp_select->where($follows_select_clause); }
                $selects[] = $temp_select;
            }
            if ($this->_getParam('comments') || $this->_getParam('commentsonly')) {
                if ($profiles_select_clause) { $system_comments_select->where($profiles_select_clause); }
                if ($follows_comments_select_clause) { $system_comments_select->where($follows_comments_select_clause); }
                $selects[] = $system_comments_select;
            }
        }


        $temp_target_acl_priv = $this->target->acl->privilege;
        $temp_target_acl_recursive = $this->target->acl->recursive;
        $acl_clause = $this->db->quoteInto('(check_acl(target.acl, ?, target.com_id, target.net_id, target.via_id, target.module_id, target.matrix_counter, target.counter, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
        
        $allowed_mids = $disallowed_mids = array();
        if ($this->_getParam('mid')) {
            foreach (explode(',', $this->_getParam('mid')) as $tMid) {
                if ($tMid < 0) { array_push($disallowed_mids, abs($tMid)); }
                else { array_push($allowed_mids, $tMid); }
            }
            
            $disallowed_mids = array_diff($disallowed_mids, $allowed_mids);
        }
        #Zend_Debug::Dump($allowed_mids);
        #Zend_Debug::Dump($disallowed_mids);
        
        if (isset($this->target->modules) && count($this->target->modules)) {
            foreach ($this->target->modules as $module) {
                if (!($this->internal->target->acl->owner ||
                  $module->allowed === null ||
                  $module->show_on_fail ||
                  ($module->allowed && ($module->privilege > 0)) ||
                  ($this->internal->target->acl->recursive && ($this->internal->target->acl->privilege > 0)))) {
                       continue;
                }
                
                if ($module->display_stack == 'none' && !$module->allow_out_flow && !$module->widget) {
                    // Don't include modules that are trying to be hidden
                    continue;
                }
                
                if ($this->_getParam('mid')) {
                    if (
                        (count($allowed_mids) && !in_array($module->module_id, $allowed_mids)) ||
                        (!(count($allowed_mids)) && in_array($module->module_id, $disallowed_mids))
                    ) { continue; }
                    #if ($module->module_id != $this->_getParam('mid')) { continue; }
                    if ($this->_getParam('xid')) {
                        if ($module->counter != $this->_getParam('xid')) { continue; }
                        else {
                            // Replace the feed information
                            $mWidget = $module;
                        }
                    }
                }
                
                $module_name = $this->module_modules[$module->module_id]->name;
    
                $temp_system_comments_select = $this->db->select()
                    ->from(array('obj' => 'system_comments'),
                        array('creation', 'updated AS modified', new Zend_Db_Expr('null::timestamp AS published'), 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter AS counter', 'profile_id', new Zend_Db_Expr("'[Comment] ' || obj.title AS title"), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), 'content', new Zend_Db_Expr('null'), 'active', new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), "$acl_clause.allowed", "$acl_clause.privilege", "$acl_clause.filter", "$acl_clause.recursive")
                    )
                    ->where('obj.active=?', 't')
                
                    ->join(array('target' => $module_name . '_' . $module_name . 's'), 'obj.module_id = target.module_id AND obj.com_id = target.com_id AND obj.net_id = target.net_id AND obj.via_id=target.via_id AND obj.matrix_counter = target.matrix_counter AND obj.item_counter = target.counter', array())
                    ->where('target.active=?', 't')
                    ->where('target.activation ISNULL OR target.activation <= now()')
                    ->where('target.expiration ISNULL OR target.expiration >= now()')
                        
                    ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter',
                        array(
                            'x_interactive' => 'interactive',
                            'x_display' => 'display'
                        )
                    )
                    ->where('x.active=?', 't')
                    ->where('x.interactive=?', 't')
                    
                    ->join(array('m' => 'module_modules'), 'x.module_id=m.id',
                        array(
                            'm_display' => 'display',
                            'm_name' => 'name'
                        )
                    )
                    ->where('m.active=?', 't')
                    
                    ->join(array('p' => 'profile_profiles'), 'target.profile_id = p.id',
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
                    
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                        array(
                        'b_id' => 'id',
                        'b_site_admin' => 'site_admin',
                        'b_active' => 'active',
                        'b_email' => 'email'
                        )
                    )
                    ->where('b.active NOTNULL')
                    
                    ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                        array(
                        'c_id' => 'id',
                        'c_name' => 'name',
                        'c_hostname' => 'hostname'
                        )
                    )
                    ->where('c.active=?', 't');
                    
                if (isset($this->member)) {
                    $temp_system_comments_select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                        array(
                            'vc_status' => 'status',
                            'vc_display' => 'display'
                        )
                    );
                    $temp_system_comments_select->joinLeft(array('pfm' => 'profile_follow_matrix'), $this->db->quoteInto("p.id = pfm.follow_profile_id AND pfm.profile_id=? AND pfm.status='t' AND pfm.active='t'", $this->member->profile->id),
                        array(
                            'pfm_following' => 'active'
                        )
                    );
                }
                
                
                /* NEED TO SYNC THIS WITH ACTION */
                // What are we actually fetching??
                $this->masked = false;
                
                if (isset($module->module_id) && $module->module_id) {
                    $temp_system_comments_select->where('target.module_id=?', $module->module_id);
                }
                
                if (($module->community_mask || $module->network_mask || $module->profile_mask) && ($module->mask_counter)) {
                    $temp_system_comments_select->where('m.allow_mask=?', 't');
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
                    
                    if ($module->community_mask) {
                        $temp_system_comments_select->where('target.com_id=?', $module->community_mask);
                        $temp_system_comments_select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                    }
                    elseif ($module->network_mask) {
                        $temp_system_comments_select->where('target.net_id=?', $module->network_mask);
                        $temp_system_comments_select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                    }
                    elseif ($object->profile_mask) {
                        $temp_system_comments_select->where('target.via_id=?', $module->profile_mask);
                        $temp_system_comments_select->where('x.allow_'.$tempwhere.'_mask @> ARRAY[?] :: bigint[]', $this->target->id);
                    }
                    
                    $temp_system_comments_select->where('target.matrix_counter=?', $module->mask_counter);
                }
                else {
                    $tempclause = '(';
                    switch($this->target->type) {
                        case 'VIA':
                            $tempclause .= $this->db->quoteInto('target.via_id=?', $this->target->id);
                            $tempwhere = 'profile';
                            break;
                        case 'NET':
                            $tempclause .= $this->db->quoteInto('target.net_id=?', $this->target->id);
                            $tempwhere = 'network';
                            break;
                        case 'COM':
                            $tempclause .= $this->db->quoteInto('target.com_id=?', $this->target->id);
                            $tempwhere = 'community';
                            break;
                    }
                    
                    if ($module->counter) {
                        $tempclause .= ' AND ' . $this->db->quoteInto('target.matrix_counter=?', $module->counter);
                    }
                    $tempclause .= ')';
                    
                    if ($module->allow_in_flow && ($module->allow_community_inflow || $module->allow_network_inflow || $module->allow_profile_inflow)) {
                        $tempclause .= " OR (m.allow_flow='t' AND x.allow_out_flow AND ";
                        
                        // Hook in the denies first with AND
                        if (isset($module->deny_community_inflow) && !is_null($module->deny_community_inflow) && !($module->deny_community_inflow == '{}') && $module->deny_community_inflow) {
                            $tempclause .= "(ARRAY[target.com_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->deny_community_inflow) . "') AND ";
                        }
                        if (isset($module->deny_network_inflow) && !is_null($module->deny_network_inflow) && !($module->deny_network_inflow == '{}') && $module->deny_network_inflow) {
                            $tempclause .= "(ARRAY[target.net_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->deny_network_inflow) . "') AND ";
                        }
                        if (isset($module->deny_profile_inflow) && !is_null($module->deny_profile_inflow) && !($module->deny_profile_inflow == '{}') && $module->deny_profile_inflow) {
                            $tempclause .= "(ARRAY[target.via_id, x.counter] :: text !~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->deny_profile_inflow) . "') AND ";
                        }
                        if (isset($module->deny_via_user_inflow) && !is_null($module->deny_via_user_inflow) && !($module->deny_via_user_inflow == '{}') && $module->deny_via_user_inflow) {
                            $tempclause .= "NOT '" . $module->deny_via_user_inflow . "' @> ARRAY[target.profile_id] :: bigint[] AND ";
                        }
                        
                        
                        if (($this->target->type == 'COM') && (is_null($module->allow_community_inflow)) && ($module->allow_profile_inflow == '{}')) {
                            $tempclause2[] = 'target.net_id = 0';
                        }
                        elseif (($this->target->type == 'COM') && ($module->allow_community_inflow == '{}') && ($module->allow_profile_inflow == '{}')) {
                            $wheres = array();
                            $wheres[] = $this->db->quoteInto('p.community_id=?', $this->community->id);
                            foreach ($this->VMAH->ePgArray($this->community->children_ids_array) as $temp) {
                                $wheres[] = $this->db->quoteInto('p.community_id=?', $temp);
                            }
                            $tempclause2[] = $this->db->quoteInto("target.com_id <> ? AND target.net_id = 0 AND (" . implode(' OR ', $wheres) . ')', $this->community->id);
                        }
                        elseif (($this->target->type == 'COM') && ($module->allow_community_inflow == '{' . $this->community->id . '}') && ($module->allow_profile_inflow == '{}')) {
                            $tempclause2[] = $this->db->quoteInto('target.com_id=0 AND target.net_id = 0 AND p.community_id=?', $this->community->id);
                        }
                        elseif ($module->allow_community_inflow) {
                            $tempclause2[] = "(ARRAY[target.com_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->allow_community_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        }
                        
                        if ($module->allow_network_inflow) {
                            $tempclause2[] = "(ARRAY[target.net_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->allow_network_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        }
                        if ($module->allow_profile_inflow) {
                            $tempclause2[] = "(ARRAY[target.via_id, x.counter] :: text ~ E'" . preg_replace(array('/^\{(.*)\}$/', '/\},\{/', '/\{/', '/\}/', '/NULL/'), array('($1)', '}|{', '\\\\\\{', '\\\\\\}', '\\\\\\d+'), $module->allow_profile_inflow) . "' AND x.allow_${tempwhere}_outflow && ARRAY[".$this->target->id."] :: bigint[])";
                        }
                        
                        if ($tempclause2) { $tempclause .= '(' . implode(' OR ', $tempclause2) . ')'; }
                        $tempclause .= ')';
                    }
                    
                    $temp_system_comments_select->where($tempclause);
                }
    
    
                
                if (!$recursive_read_acl) {
                    // Add the modules - With comments
                    $this->target->acl->privilege = null;
                    $this->target->acl->recursive = null;
                    if ($module->privilege) { $this->target->acl->privilege = $module->privilege; }
                    if ($module->recursive) { $this->target->acl->recursive = $module->recursive; }
                    if (!$this->_getParam('commentsonly')) {
                        $temp_select = $this->_buildComplexQuery($table_name, $module, $fields);
                        
                        if ($profiles_select_clause) { $temp_select->where($profiles_select_clause); }
                        if ($symbols_select_clause) { $temp_select->where($symbols_select_clause); }
                        if ($follows_select_clause) { $temp_select->where($follows_select_clause); }
                        
                        $selects[] = $temp_select;
                    }
                    if (($this->_getParam('comments') || $this->_getParam('commentsonly')) && (is_null($module->privilege) || ($module->privilege >= $this->_minPrivilege))) {
                        #$temp_system_comments_select = clone $system_comments_select;
                        $temp_system_comments_select->where("target.acl ISNULL OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > ".$this->_minPrivilege.")))");
                        
                        if ($profiles_select_clause) { $temp_system_comments_select->where($profiles_select_clause); }
                        if ($follows_comments_select_clause) { $temp_system_comments_select->where($follows_comments_select_clause); }
                        
                        $selects[] = $temp_system_comments_select;
                    }
                }
                elseif (
                     (($module->community_mask || $module->network_mask || $module->profile_mask) && ($module->mask_counter))
                     ||
                     ($module->allow_in_flow && ($module->allow_community_inflow || $module->allow_network_inflow || $module->allow_profile_inflow))
                   ) {
                    // Even for READ recursive, have to add the inflows - No Comments
                    if (!$this->_getParam('commentsonly')) {
                        $temp_select = $this->_buildComplexQuery($table_name, $module, $fields);
                        
                        if ($profiles_select_clause) { $temp_select->where($profiles_select_clause); }
                        if ($symbols_select_clause) { $temp_select->where($symbols_select_clause); }
                        if ($follows_select_clause) { $temp_select->where($follows_select_clause); }
                        
                        $selects[] = $temp_select;
                    }
                    if ($this->_getParam('comments') || $this->_getParam('commentsonly')) {
                        if ($profiles_select_clause) { $temp_system_comments_select->where($profiles_select_clause); }
                        if ($follows_comments_select_clause) { $temp_system_comments_select->where($follows_comments_select_clause); }
                        
                        $selects[] = $temp_system_comments_select;
                    }
                }
            }
        }
        // Put the ACL back
        $this->target->acl->privilege = $temp_target_acl_priv;
        $this->target->acl->recursive = $temp_target_acl_recursive;


        if (count($selects)) {
            $select = $this->db->select()
                ->union($selects);
            
            // NOMID
            if ($this->_getParam('nomid')) {
                return $select;
            }
            
            if (isset($mWidget)) {
                $widget = $mWidget;
            }
            else {
                $widget = new StdClass();
                  $widget->creation = $this->target->space->creation;
                  $widget->updated = $this->target->space->updated;
                  $widget->display = (isset($this->target->space->display) && $this->target->space->display ? $this->target->space->display : (isset($this->target->space->name) && $this->target->space->name ? $this->target->space->name : ''));
                  $widget->{strtolower($this->target->type) . '_id'} = $this->target->space->id;
                  $widget->meta_description = $this->target->space->meta_description;
            }
            
            $this->_setParam('widget', $widget);
            
            // Wrap the select to apply the order by COALESCE
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            $this->_setParam('w_limit', $this->_getParam('w_limit', 20));
            
            parent::indexAction($select);
        }
        
        #echo $select;
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function profileAction($select = null)
    {
        # Can be called from any TYPE
        #if ($this->target->type != 'VIA') { return $this->_denied('Invalid Profile', 'There was an error when requesting data for the profile.'); }
        if ($this->target->type != 'VIA' && !$this->_getParam('profile_id') && !$this->_getParam('alternate_target_id')) { return $this->_denied('Invalid Profile', 'There was an error when requesting data for the profile.'); }
        
        // Profile ID clause
        $profiles_select_clause = $this->db->quoteInto('obj.profile_id=?', $this->target->id);
        if ($this->_getParam('profile_id')) {
            $wheres = array();
            foreach (preg_split('/[,\s]+/', $this->_getParam('profile_id')) as $profile_id) {
                $profile_id = trim($profile_id);
                if (is_numeric($profile_id)) {
                    $wheres[] = $this->db->quoteInto('obj.profile_id=?', $profile_id);
                }
            }
            if (count($wheres)) {
                $profiles_select_clause = join(' OR ', $wheres);
            }
        }
        
        $table_name = 'module_template';
        $selects = array();
        $fields = array('obj.creation', 'obj.modified', 'obj.published', 'obj.com_id', 'obj.net_id', 'obj.via_id', 'obj.module_id', 'obj.matrix_counter', 'obj.counter', 'obj.profile_id', 'obj.title', 'obj.meta_keywords', 'obj.summary', 'obj.content', 'obj.symbols', 'obj.active', 'obj.total_views_count', 'GREATEST( obj.creation, obj.published, ((CASE WHEN obj.modified ISNULL THEN obj.activation ELSE null END)::timestamp) ) AS published_display_date', "COALESCE((obj.activation <= 'now'), 't'::bool) AS published_display_activated", "COALESCE((obj.expiration <= 'now'), 'f'::bool) AS published_display_expired");
        
        // NOMID
        if ($this->_getParam('nomid')) {
            if ($this->_getParam('alternate_table_name')) {
                $table_name = $this->_getParam('alternate_table_name');
            }
            else {
                $table_name = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getModuleName() . 's';
            }
            $fields = array('*', 'GREATEST( obj.creation, obj.published, ((CASE WHEN obj.modified ISNULL THEN obj.activation ELSE null END)::timestamp) ) AS published_display_date', "COALESCE((obj.activation <= 'now'), 't'::bool) AS published_display_activated", "COALESCE((obj.expiration <= 'now'), 'f'::bool) AS published_display_expired");
            if ($this->_getParam('alternate_target_id')) { $profiles_select_clause = $this->db->quoteInto('obj.profile_id=?', $this->_getParam('alternate_target_id')); }
        }
        
        $select = $this->_getContentSelect($table_name, $fields);
        $select->where($profiles_select_clause);
        
        // Look up by symbol or symbol id
        if ($this->_getParam('symbol') || $this->_getParam('symbol_id')) {
            $select_symbols = $this->db->select()
                ->from(array('obj' => 'quote_view_symbol_matrix'))
                ->where('obj.active = ?', 't');
                
            $wheres = array();
            if ($this->_getParam('symbol')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('symbol')) as $symbol) {
                    $symbol = trim($symbol);
                    if ($symbol) {
                        $wheres[] = $this->db->quoteInto('obj.internal_symbol=?', $symbol);
                    }
                }
            }
            if ($this->_getParam('symbol_id')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('symbol_id')) as $symbol_id) {
                    $symbol_id = trim($symbol_id);
                    if (is_numeric($symbol_id)) {
                        $wheres[] = $this->db->quoteInto('obj.id=?', $symbol_id);
                    }
                }
            }
            if (count($wheres)) {
                $select_symbols->where(implode(' OR ', $wheres))->limit(100);
            }
            else {
                $select_symbols->limit(1);
            }
            
            $symbols = $this->db->fetchAll($select_symbols);
            if (count($symbols)) {
                $wheres = array();
                foreach ($symbols as $symbol) {
                    $wheres[] = '(' . $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $symbol->id) . 'obj.search @@ plainto_tsquery(' . $this->db->quote($symbol->internal_symbol) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $symbol->name))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($symbol->internal_symbol) . " || E'((,|\\\\s)+|$)'))" . ')';
                }
                if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }
            }
        }
        
        // Look up by follows - Profiles or symbols
        if ($this->_getParam('following')) {
            $wheres = array();
            
            if ($this->_getParam('following') == 'profiles' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.profile_id IN (SELECT follow_profile_id FROM profile_follow_matrix WHERE status='t' AND active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            if ($this->_getParam('following') == 'symbols' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.symbols && (SELECT array_accum(symbol_id) FROM quote_follow_matrix WHERE active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            
            if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }
        }
        
        if (!(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) && (!isset($this->target->acl->owner) || !$this->target->acl->owner)) {
            $obj_acl_clause = $this->db->quoteInto('(check_acl(obj.acl, ?, obj.com_id, obj.net_id, obj.via_id, obj.module_id, obj.matrix_counter, obj.counter, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $mod_acl_clause = $this->db->quoteInto('(check_acl(x.acl, ?, x.com_id, x.net_id, x.via_id, x.module_id, x.counter, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $com_acl_clause = $this->db->quoteInto('(check_acl(c_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $net_acl_clause = $this->db->quoteInto('(check_acl(n_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $via_acl_clause = $this->db->quoteInto('(check_acl(p_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            
            $select
            // Join System Communities Space ACL
            ->joinLeft(array('c_acl' => 'system_communities'), "obj.com_id = c_acl.id AND c_acl.active='t' AND c_acl.id > 0", array())
            // Join Network Networks Space ACL
            ->joinLeft(array('n_acl' => 'network_networks'), "obj.net_id = n_acl.id AND n_acl.active='t' AND n_acl.id > 0", array())
            // Join Profile Profiles Space ACL
            ->joinLeft(array('p_acl' => 'profile_profiles'), "obj.via_id = p_acl.id AND p_acl.active='t' AND p_acl.id > 0", array())
            // Construct all of the ACL possibilities
            //  obj_acl_clause - mod_acl_clause - com_acl_clause - net_acl_clause - via_acl_clause
            //  allowed - privilege - filter - recursive
            /*
                Space is allowed and recursive
                OR
                    Space is allowed or neutral
                    AND
                    Module is allowed and recursive
                    OR
                        Space is allowed or neutral
                        AND
                        Module is allowed or neutral
                        AND
                        Object is allowed or neutral
                    
            */
            ->where("
                (
                    CASE WHEN
                        (obj.acl NOTNULL OR x.acl NOTNULL OR (obj.com_id > 0 AND c_acl.acl NOTNULL) OR (obj.net_id > 0 AND n_acl.acl NOTNULL) OR (obj.via_id > 0 AND p_acl.acl NOTNULL))
                    THEN
                        (
                            (obj.com_id > 0 AND c_acl.acl NOTNULL AND $com_acl_clause.allowed AND $com_acl_clause.recursive)
                            OR
                            (obj.net_id > 0 AND n_acl.acl NOTNULL AND $net_acl_clause.allowed AND $net_acl_clause.recursive)
                            OR
                            (obj.via_id > 0 AND p_acl.acl NOTNULL AND $via_acl_clause.allowed AND $via_acl_clause.recursive)
                        )
                        OR
                        (
                            (
                                (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                OR
                                (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                OR
                                (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                            )
                            AND(
                                (x.acl AND $mod_acl_clause.allowed AND $mod_acl_clause.recursive)
                            )
                        )
                        OR
                        (
                            (obj.show_on_fail)
                            OR
                            (
                                (
                                    (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                    OR
                                    (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                    OR
                                    (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                                )
                                AND(
                                    (x.acl ISNULL OR $mod_acl_clause.allowed)
                                )
                                AND (
                                    (obj.acl ISNULL)
                                    OR
                                    (obj.acl AND $obj_acl_clause.allowed)
                                )
                            )
                        )
                    ELSE
                        true
                    END
                )
            ")
            ;
        }
        
        if (isset($this->member)) {
            // Left Join Contact Contacts
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
        
        $system_comments_select = $this->_getCommentSelect();
        $system_comments_select->where($profiles_select_clause);
        
        if (!(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) && (!isset($this->target->acl->owner) || !$this->target->acl->owner)) {
        #if (!(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))) {
            $obj_acl_clause = $this->db->quoteInto('(check_acl(t.acl, ?, t.com_id, t.net_id, t.via_id, t.module_id, t.matrix_counter, t.counter, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $mod_acl_clause = $this->db->quoteInto('(check_acl(x.acl, ?, x.com_id, x.net_id, x.via_id, x.module_id, x.counter, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $com_acl_clause = $this->db->quoteInto('(check_acl(c_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $net_acl_clause = $this->db->quoteInto('(check_acl(n_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $via_acl_clause = $this->db->quoteInto('(check_acl(p_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            
            $system_comments_select
            // Join System Communities Space ACL
            ->joinLeft(array('c_acl' => 'system_communities'), "obj.com_id = c_acl.id AND c_acl.active='t' AND c_acl.id > 0", array())
            // Join Network Networks Space ACL
            ->joinLeft(array('n_acl' => 'network_networks'), "obj.net_id = n_acl.id AND n_acl.active='t' AND n_acl.id > 0", array())
            // Join Profile Profiles Space ACL
            ->joinLeft(array('p_acl' => 'profile_profiles'), "obj.via_id = p_acl.id AND p_acl.active='t' AND p_acl.id > 0", array())
            // Construct all of the ACL possibilities
            //  obj_acl_clause - mod_acl_clause - com_acl_clause - net_acl_clause - via_acl_clause
            //  allowed - privilege - filter - recursive
            /*
                Space is allowed and recursive
                OR
                    Space is allowed or neutral
                    AND
                    Module is allowed and recursive
                    OR
                        Space is allowed or neutral
                        AND
                        Module is allowed or neutral
                        AND
                        Object is allowed or neutral
                    
            */
            ->where("
                (
                    CASE WHEN
                        (t.acl NOTNULL OR x.acl NOTNULL OR (obj.com_id > 0 AND c_acl.acl NOTNULL) OR (obj.net_id > 0 AND n_acl.acl NOTNULL) OR (obj.via_id > 0 AND p_acl.acl NOTNULL))
                    THEN
                        (
                            (obj.com_id > 0 AND c_acl.acl NOTNULL AND $com_acl_clause.allowed AND $com_acl_clause.recursive)
                            OR
                            (obj.net_id > 0 AND n_acl.acl NOTNULL AND $net_acl_clause.allowed AND $net_acl_clause.recursive)
                            OR
                            (obj.via_id > 0 AND p_acl.acl NOTNULL AND $via_acl_clause.allowed AND $via_acl_clause.recursive)
                        )
                        OR
                        (
                            (
                                (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                OR
                                (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                OR
                                (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                            )
                            AND(
                                (x.acl AND $mod_acl_clause.allowed AND $mod_acl_clause.recursive)
                            )
                        )
                        OR
                        (
                            (t.show_on_fail)
                            OR
                            (
                                (
                                    (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                    OR
                                    (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                    OR
                                    (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                                )
                                AND(
                                    (x.acl ISNULL OR $mod_acl_clause.allowed)
                                )
                                AND (
                                    (t.acl ISNULL)
                                    OR
                                    (t.acl AND $obj_acl_clause.allowed)
                                )
                            )
                        )
                    ELSE
                        true
                    END
                )
            ")
            ;
        }
        
        if (isset($this->member)) {
            $system_comments_select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                array(
                    'vc_status' => 'status',
                    'vc_display' => 'display'
                )
            );
            $system_comments_select->joinLeft(array('pfm' => 'profile_follow_matrix'), $this->db->quoteInto("p.id = pfm.follow_profile_id AND pfm.profile_id=? AND pfm.status='t' AND pfm.active='t'", $this->member->profile->id),
                array(
                    'pfm_following' => 'active'
                )
            );
        }
        
        if ($this->_getParam('mid') && !$this->_getParam('nomid')) {
            $allowed_mids = $disallowed_mids = array();
            if ($this->_getParam('mid')) {
                foreach (explode(',', $this->_getParam('mid')) as $tMid) {
                    if ($tMid < 0) { array_push($disallowed_mids, abs($tMid)); }
                    else { array_push($allowed_mids, $tMid); }
                }
                
                $disallowed_mids = array_diff($disallowed_mids, $allowed_mids);
                
                $mids_clause = '(';
                
                if (count($allowed_mids)) {
                    $tempAM_array = array();
                    foreach ($allowed_mids as $tempAM) {
                        array_push($tempAM_array, $this->db->quoteInto('obj.module_id=?', $tempAM));
                    }
                    
                    $mids_clause .= '(' . implode(' OR ', $tempAM_array) . ')';
                }
                
                if (count($allowed_mids) && count($disallowed_mids)) { $mids_clause .= ' AND '; }
                
                if (count($disallowed_mids)) {
                    $tempDM_array = array();
                    foreach ($disallowed_mids as $tempDM) {
                        array_push($tempDM_array, $this->db->quoteInto('obj.module_id<>?', $tempDM));
                    }
                    
                    $mids_clause .= '(' . implode(' AND ', $tempDM_array) . ')';
                }
                
                $mids_clause .= ')';
                #Zend_Debug::Dump($mids_clause);
                $select->where($mids_clause);
                $system_comments_select->where($mids_clause);
            }
            
            #$select->where('obj.module_id=?', $this->_getParam('mid'));
            #$system_comments_select->where('obj.module_id=?', $this->_getParam('mid'));
        }
        
        if (!$this->_getParam('commentsonly')) {
            $selects[] = $select;
        }
        if ($this->_getParam('comments') || $this->_getParam('commentsonly')) {                  
            $selects[] = $system_comments_select;
        }
        
        if (count($selects)) {
            $select = $this->db->select()
                ->union($selects);
            
            // NOMID
            if ($this->_getParam('nomid')) {
                return $select;
            }
            
            $widget = new StdClass();
              $widget->creation = $this->target->space->creation;
              $widget->updated = $this->target->space->updated;
              $widget->display = (isset($this->target->space->display) && $this->target->space->display ? $this->target->space->display : (isset($this->target->space->name) && $this->target->space->name ? $this->target->space->name : ''));
              #if ($this->_getParam('commentsonly')) {
              #    $widget->display .= ' (Comments Only)';
              #} elseif ($this->_getParam('comments')) {
              #    $widget->display .= ' (Content and Comments)';
              #}
              $widget->{strtolower($this->target->type) . '_id'} = $this->target->space->id;
              $widget->meta_description = $this->target->space->meta_description;
            
            $this->_setParam('widget', $widget);
            
            // Wrap the select to apply the order by COALESCE
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            $this->_setParam('w_limit', $this->_getParam('w_limit', 20));
            
            parent::indexAction($select);
        }
        
        #echo $select;
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function symbolAction($select = null)
    {
        if ($this->target->type != 'COM' || (!$this->_getParam('symbol_id') && !$this->_getParam('symbol'))) { return $this->_denied('Invalid Symbol', 'There was an error when requesting data for that symbol.'); }
        
        // Look up the symbol or symbol id
        $select = $this->db->select()
            ->from(array('obj' => 'quote_view_symbol_matrix'))
            ->where('obj.active = ?', 't');
        $wheres = array();
        if ($this->_getParam('symbol')) {
            foreach (preg_split('/[,\s]+/', $this->_getParam('symbol')) as $symbol) {
                $symbol = trim($symbol);
                if ($symbol) {
                    $wheres[] = $this->db->quoteInto('obj.internal_symbol=?', $symbol);
                }
            }
        }
        if ($this->_getParam('symbol_id')) {
            foreach (preg_split('/[,\s]+/', $this->_getParam('symbol_id')) as $symbol_id) {
                $symbol_id = trim($symbol_id);
                if (is_numeric($symbol_id)) {
                    $wheres[] = $this->db->quoteInto('obj.id=?', $symbol_id);
                }
            }
        }
        if (count($wheres)) {
            $select->where(implode(' OR ', $wheres))->limit(100);
        }
        else {
            $select->limit(1);
        }
        
        $symbols = $this->db->fetchAll($select);
        if (!(count($symbols))) {
            return $this->_denied('Invalid Symbol', 'There was an error when requesting data for that symbol.');
        }
        #$symbol = $symbols[0]; // See below for setting on multiple symbol search
        
        $table_name = 'module_template';
        $selects = array();
        $fields = array('obj.creation', 'obj.modified', 'obj.published', 'obj.com_id', 'obj.net_id', 'obj.via_id', 'obj.module_id', 'obj.matrix_counter', 'obj.counter', 'obj.profile_id', 'obj.title', 'obj.meta_keywords', 'obj.summary', 'obj.content', 'obj.symbols', 'obj.active', 'obj.total_views_count', 'GREATEST( obj.creation, obj.published, ((CASE WHEN obj.modified ISNULL THEN obj.activation ELSE null END)::timestamp) ) AS published_display_date', "COALESCE((obj.activation <= 'now'), 't'::bool) AS published_display_activated", "COALESCE((obj.expiration <= 'now'), 'f'::bool) AS published_display_expired");
        
        // NOMID
        if ($this->_getParam('nomid')) {
            $table_name = $this->getRequest()->getModuleName() . '_' . $this->getRequest()->getModuleName() . 's';
            $fields = array('*', 'GREATEST( obj.creation, obj.published, ((CASE WHEN obj.modified ISNULL THEN obj.activation ELSE null END)::timestamp) ) AS published_display_date', "COALESCE((obj.activation <= 'now'), 't'::bool) AS published_display_activated", "COALESCE((obj.expiration <= 'now'), 'f'::bool) AS published_display_expired");
        }
        
        $select = $this->_getContentSelect($table_name, $fields);
        
        $wheres = array();
        foreach ($symbols as $symbol) {
            $wheres[] = '(' . $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $symbol->id) . 'obj.search @@ plainto_tsquery(' . $this->db->quote($symbol->internal_symbol) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $symbol->name))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($symbol->internal_symbol) . " || E'((,|\\\\s)+|$)'))" . ')';
        }
        if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }
        if (count($symbols) == 1) {
            $symbol = $symbols[0];
        }
        else {
            // Using name and internal_symbol in index
            $symbol = new StdClass();
            $symbol->name = 'Multiple Symbols';
            $symbols_array = array();
            foreach ($symbols as $temp_symbol) { $symbols_array[] = $temp_symbol->internal_symbol; }
            $symbol->internal_symbol = implode(',', $symbols_array);
        }
        
        // Profile ID clause
        if ($this->_getParam('profile_id')) {
            $wheres = array();
            foreach (preg_split('/[,\s]+/', $this->_getParam('profile_id')) as $profile_id) {
                $profile_id = trim($profile_id);
                if (is_numeric($profile_id)) {
                    $wheres[] = $this->db->quoteInto('obj.profile_id=?', $profile_id);
                }
            }
            if (count($wheres)) {
                $select->where(join(' OR ', $wheres));
            }
        }
        
        // Look up by follows - Profiles or symbols
        if ($this->_getParam('following')) {
            $wheres = array();
            
            if ($this->_getParam('following') == 'profiles' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.profile_id IN (SELECT follow_profile_id FROM profile_follow_matrix WHERE status='t' AND active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            if ($this->_getParam('following') == 'symbols' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.symbols && (SELECT array_accum(symbol_id) FROM quote_follow_matrix WHERE active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            
            if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }
        }
        
        if (!(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) && (!isset($this->target->acl->owner) || !$this->target->acl->owner)) {
        #if (!isset($this->target->acl->owner) || !$this->target->acl->owner) {
            $obj_acl_clause = $this->db->quoteInto('(check_acl(obj.acl, ?, obj.com_id, obj.net_id, obj.via_id, obj.module_id, obj.matrix_counter, obj.counter, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $mod_acl_clause = $this->db->quoteInto('(check_acl(x.acl, ?, x.com_id, x.net_id, x.via_id, x.module_id, x.counter, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $com_acl_clause = $this->db->quoteInto('(check_acl(c_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $net_acl_clause = $this->db->quoteInto('(check_acl(n_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $via_acl_clause = $this->db->quoteInto('(check_acl(p_acl.acl, ?, obj.com_id, obj.net_id, obj.via_id, 0, 0, 0, ' . self::ACL_READ . '))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            
            $select
            // Join System Communities Space ACL
            ->joinLeft(array('c_acl' => 'system_communities'), "obj.com_id = c_acl.id AND c_acl.active='t' AND c_acl.id > 0", array())
            // Join Network Networks Space ACL
            ->joinLeft(array('n_acl' => 'network_networks'), "obj.net_id = n_acl.id AND n_acl.active='t' AND n_acl.id > 0", array())
            // Join Profile Profiles Space ACL
            ->joinLeft(array('p_acl' => 'profile_profiles'), "obj.via_id = p_acl.id AND p_acl.active='t' AND p_acl.id > 0", array())
            // Construct all of the ACL possibilities
            //  obj_acl_clause - mod_acl_clause - com_acl_clause - net_acl_clause - via_acl_clause
            //  allowed - privilege - filter - recursive
            /*
                Space is allowed and recursive
                OR
                    Space is allowed or neutral
                    AND
                    Module is allowed and recursive
                    OR
                        Space is allowed or neutral
                        AND
                        Module is allowed or neutral
                        AND
                        Object is allowed or neutral
                    
            */
            ->where("
                (
                    CASE WHEN
                        (obj.acl NOTNULL OR x.acl NOTNULL OR (obj.com_id > 0 AND c_acl.acl NOTNULL) OR (obj.net_id > 0 AND n_acl.acl NOTNULL) OR (obj.via_id > 0 AND p_acl.acl NOTNULL))
                    THEN
                        (
                            (obj.com_id > 0 AND c_acl.acl NOTNULL AND $com_acl_clause.allowed AND $com_acl_clause.recursive)
                            OR
                            (obj.net_id > 0 AND n_acl.acl NOTNULL AND $net_acl_clause.allowed AND $net_acl_clause.recursive)
                            OR
                            (obj.via_id > 0 AND p_acl.acl NOTNULL AND $via_acl_clause.allowed AND $via_acl_clause.recursive)
                        )
                        OR
                        (
                            (
                                (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                OR
                                (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                OR
                                (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                            )
                            AND(
                                (x.acl AND $mod_acl_clause.allowed AND $mod_acl_clause.recursive)
                            )
                        )
                        OR
                        (
                            (obj.show_on_fail)
                            OR
                            (
                                (
                                    (obj.com_id > 0 AND (c_acl.acl ISNULL OR $com_acl_clause.allowed))
                                    OR
                                    (obj.net_id > 0 AND (n_acl.acl ISNULL OR $net_acl_clause.allowed))
                                    OR
                                    (obj.via_id > 0 AND (p_acl.acl ISNULL OR $via_acl_clause.allowed))
                                )
                                AND(
                                    (x.acl ISNULL OR $mod_acl_clause.allowed)
                                )
                                AND (
                                    (obj.acl ISNULL)
                                    OR
                                    (obj.acl AND $obj_acl_clause.allowed)
                                )
                            )
                        )
                    ELSE
                        true
                    END
                )
            ")
            ;
        }
        
        if (isset($this->member)) {
            // Left Join Contact Contacts
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
        
        if ($this->_getParam('mid') && !$this->_getParam('nomid')) {
            $allowed_mids = $disallowed_mids = array();
            if ($this->_getParam('mid')) {
                foreach (explode(',', $this->_getParam('mid')) as $tMid) {
                    if ($tMid < 0) { array_push($disallowed_mids, abs($tMid)); }
                    else { array_push($allowed_mids, $tMid); }
                }
                
                $disallowed_mids = array_diff($disallowed_mids, $allowed_mids);
                
                $mids_clause = '(';
                
                if (count($allowed_mids)) {
                    $tempAM_array = array();
                    foreach ($allowed_mids as $tempAM) {
                        array_push($tempAM_array, $this->db->quoteInto('obj.module_id=?', $tempAM));
                    }
                    
                    $mids_clause .= '(' . implode(' OR ', $tempAM_array) . ')';
                }
                
                if (count($allowed_mids) && count($disallowed_mids)) { $mids_clause .= ' AND '; }
                
                if (count($disallowed_mids)) {
                    $tempDM_array = array();
                    foreach ($disallowed_mids as $tempDM) {
                        array_push($tempDM_array, $this->db->quoteInto('obj.module_id<>?', $tempDM));
                    }
                    
                    $mids_clause .= '(' . implode(' AND ', $tempDM_array) . ')';
                }
                
                $mids_clause .= ')';
                #Zend_Debug::Dump($mids_clause);
                $select->where($mids_clause);
            }
            
            #$select->where('obj.module_id=?', $this->_getParam('mid'));
        }
        
        if ($select) {
            #$select = $this->db->select()
            #    ->union($selects);
                
            // NOMID
            if ($this->_getParam('nomid')) {
                // Set the symbol data first
                return array($select, $symbol);
            }
            
            $widget = new StdClass();
              $widget->creation = $this->target->space->creation;
              $widget->updated = $this->target->space->updated;
              $widget->display = $symbol->name;
              $widget->{strtolower($this->target->type) . '_id'} = $this->target->space->id;
              $widget->meta_description = $symbol->name;
            
            $this->_setParam('widget', $widget);
            
            // Wrap the select to apply the order by COALESCE
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            $this->_setParam('w_limit', $this->_getParam('w_limit', 20));
            
            parent::indexAction($select);
        }
        
        #echo $select;
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    protected function _getContentSelect($table_name, $fields)
    {
        if ($table_name && $fields) {
            $select = $this->db->select()
                ->from(array('obj' => $table_name), $fields)
                ->where('obj.active=?', 't')
                
                // Join Module Matrix
                ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter',
                    array(
                        'x_interactive' => 'interactive',
                        'x_display' => 'display'
                    )
                )
                ->where('x.active=?', 't')
                
                // Join Module Modules
                ->join(array('m' => 'module_modules'), 'x.module_id=m.id',
                    array(
                        'm_display' => 'display',
                        'm_name' => 'name'
                    )
                )
                ->where('m.active=?', 't')
                
                // Join Profile Profiles For Author
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
                
                // Join Member Members For Author
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email'
                    )
                )
                ->where('b.active=?', 't')
                
                // Join System Communities For Author
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                ->where('c.active=?', 't')
                
                // MOVED THIS ALL - COUNTS ARE IN module_views_counts AND TOTAL IS NOW IN module_template WITH ITEM
                #// Left Join View Counters
                #->joinLeft(array('svcm' => 'system_view_count_matrix'), 'obj.com_id=svcm.com_id AND obj.net_id=svcm.net_id AND obj.via_id=svcm.via_id AND obj.module_id=svcm.module_id AN#D obj.matrix_counter=svcm.matrix_counter AND obj.counter=svcm.item_counter',
                #    array(
                #        'total_view_counter' => 'total_view_counter'
                #    )
                #)
            ;
            
            return $select;
        }
    }
    
    
    protected function _getCommentSelect()
    {
        $system_comments_select = $this->db->select()
            ->from(array('obj' => 'system_comments'),
                array('creation', 'updated AS modified', 'creation AS published', 'com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter AS counter', 'profile_id', new Zend_Db_Expr("'[Comment] ' || obj.title AS title"), new Zend_Db_Expr('null'), new Zend_Db_Expr('null'), 'content', new Zend_Db_Expr('null'), 'active', new Zend_Db_Expr('null'), 'creation AS published_display_date', new Zend_Db_Expr('null'), new Zend_Db_Expr('null'))
            )
            ->where('obj.active=?', 't')
            
            ->join(array('t' => 'module_template'), 'obj.com_id=t.com_id AND obj.net_id=t.net_id AND obj.via_id=t.via_id AND obj.module_id=t.module_id AND obj.matrix_counter=t.matrix_counter AND obj.item_counter=t.counter',
                array(
                )
            )
            ->where('t.active=?', 't')
            
            ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter',
                array(
                    'x_interactive' => 'interactive',
                    'x_display' => 'display'
                )
            )
            ->where('x.active=?', 't')
            ->where('x.interactive=?', 't')
            
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
            
            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                array(
                'b_id' => 'id',
                'b_site_admin' => 'site_admin',
                'b_active' => 'active',
                'b_email' => 'email'
                )
            )
            ->where('b.active NOTNULL')
            
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                array(
                'c_id' => 'id',
                'c_name' => 'name',
                'c_hostname' => 'hostname'
                )
            )
            ->where('c.active=?', 't')
        ;
        
        return $system_comments_select;
    }
}