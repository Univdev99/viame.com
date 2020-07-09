<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class IndexController extends ViaMe_Controller_Action
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
        $this->target->type = 'COM';
        
        if (isset($this->target->id)) {
            if ($this->_getParam('com_id')) {
                $this->target->pre .= '/com/' . $this->target->id;
            }
            
            $acl_clause = $this->db->quoteInto('(check_acl(x.acl, ?, x.com_id, 0, 0, x.module_id, x.counter, 0, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            $select = $this->db->select()
                ->from(array('x' => 'module_matrix'),
                    array('*',
                        "REPLACE(array_to_string(parameter_values, '" . $this->config->delimiter . "'), 'http://', 'https://') as parameter_values_delimited",
                        $this->db->quoteInto('(b.id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                        "$acl_clause.privilege",
                        "$acl_clause.filter",
                        "$acl_clause.recursive"
                    )
                )
                ->where('x.active=?', 't')
                ->where('x.com_id=?', $this->community->id)
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
                // Redundant as we are already have a valid com - Keep For allowed acl clause
                ->join(array('c' => 'system_communities'), 'c.id = x.com_id', array())
                ->where('c.active=?', 't')
                ->join(array('b' => 'member_members'), 'c.member_id = b.id', array())
                ->where('b.active=?', 't')
                ->order(array('x.display_orderby', 'x.orderby', 'x.display', 'm.display', 'm.name', 'x.counter', 'm.id'));
            
            # Need to remove because this module list is used elsewhere, like modifying and listing modules
            #if (!$this->target->acl->owner) {
            #    $select->where("x.acl ISNULL OR x.show_on_fail='t' OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > 0)))");
            #}
            
            $this->target->modules = $this->db->fetchAll($select);
        }
        
        #$this->view->module_modules = $this->module_modules;
        
        // Load up widgets
        $select = $this->db->select()
            ->from(array('x' => 'widget_matrix'),
                array('*',
                    "REPLACE(array_to_string(parameter_values, '" . $this->config->delimiter . "'), 'http://', 'https://') as parameter_values_delimited"
                )
            )
            ->where("x.active='t'")
            ->where('x.com_id=?', $this->community->id)
            ->join(array('w' => 'widget_widgets'), 'x.widget_id = w.id', array(
                'w_name' => 'name',
                'w_display' => 'display',
                'w_description' => 'description',
                'w_parameters' => 'parameters',
                'w_allow_multiple' => 'allow_multiple'
              ))
            ->where('w.active=?', 't')
            // Redundant as we are already have a valid com - Keep For allowed acl clause
            #->join(array('p' => 'profile_profiles'), 'p.id = x.com_id', array())
            #->where('p.active=?', 't')
            #->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
            #->where('b.active=?', 't')
            ->order(array('x.orderby', 'x.display', 'w.display', 'w.name', 'x.counter', 'w.id'));

        
        $this->target->widgets = $this->db->fetchAll($select);
        
        Zend_Registry::set('com', $this->community);
        $this->internal->com = $this->community;
        $this->internal->target->space = $this->community;
        
        
        /*
        // Some Test Stuff
        $select = $this->db->select()
            ->from(array('p' => 'profile_profiles'))
            ->where('p.active=?', 't')
            ->join(array('b' => 'member_members'), 'p.member_id = b.id', array('b_site_admin' => 'site_admin'))
            ->where('b.active=?', 't')
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id', array('c_name' => 'name', 'c_hostname' => 'hostname'))
            ->where('c.active NOTNULL');
         
        if (isset($this->member)) {
            $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id), array('vc_contact' => 'status', 'vc_display' => 'display'));
        }
        $this->view->profiles = $this->db->fetchAll($select);
        
        $select = $this->db->select()->from(array('n' => 'network_networks'))
            ->where('n.active NOTNULL')
            // Placeholder
            ->join(array('p' => 'profile_profiles'), 'n.profile_id = p.id', array())
            ->where('p.active NOTNULL')
            ->joinLeft(array('c' => 'system_communities'), 'p.community_id = c.id', array())
            ->where('c.active NOTNULL')
            ->order('n.id');
        
        $this->view->networks = $this->db->fetchAll($select);
        */
        
        
        // META
        /* Already done in the plugin
        $this->view->headTitle(($this->internal->com->display ? $this->internal->com->display : $this->internal->com->name), 'PREPEND');
        $this->view->headMeta()->setName('author', ($this->internal->com->display ? $this->internal->com->display : $this->internal->com->name));
        $this->view->headMeta()->setName('copyright', 'Copyright by ' . ($this->internal->com->display ? $this->internal->com->display : $this->internal->com->name));
        if ($this->internal->com->meta_description) {
            $this->view->headMeta()->setName('description', $this->internal->com->meta_description);
        }
        if ($this->internal->com->meta_keywords) {
            $this->view->headMeta()->setName('keywords', $this->internal->com->meta_keywords);
        }
        */
        
        // Alternate Feeds
        $temp_dn = ($this->internal->com->display ? $this->internal->com->display : $this->internal->com->name);
        $link =  'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($temp_dn) . '/s' . $this->target->pre . '/system/widget/p/format';
        foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
            $this->view->headLink()->appendAlternate(
                "$link/$key/",
                $val,
                $temp_dn . " - " . strtoupper($key) . ' Feed'
            );
            $this->view->headLink()->appendAlternate(
                "$link/$key/comments/1/",
                $val,
                $temp_dn . " (With Comments) - " . strtoupper($key) . ' Feed'
            );
            $this->view->headLink()->appendAlternate(
                "$link/$key/commentsonly/1/",
                $val,
                $temp_dn . " (Comments Only) - " . strtoupper($key) . ' Feed'
            );
        }
        
        // Custom Space Style
        if ($this->internal->com->page_style) {
            $this->view->headStyle()->appendStyle(str_replace('<', '&lt;', $this->internal->com->page_style));
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
            $query = $this->db->quoteInto('UPDATE system_communities SET grid_c1 = grid_c1 || ? ::text[]', ('{' . implode(',', array_keys($to_display)) . '}'));         
            $query .= $this->db->quoteInto(' WHERE id=?', $this->target->id);
            try { $this->db->exec($query); } catch (Exception $e) { }
            #print $query;
        }
        
        $this->view->grid = $grid;
        
        
        // Community Breadcrumb
        $this->_addBreadCrumb(array(
            'title' => ($this->community->display ? $this->community->display : $this->community->name),
            'simple' => 'Home',
            #'url' =>  '/' . $this->view->SEO_Urlify(isset($this->target->space->display) ? $this->target->space->display : $this->target->space->name) . '/s' . (isset($this->target->pre) ? $this->target->pre : '') . '/'
            'url' =>  (isset($this->target->pre) ? $this->target->pre : '') . '/'
        ));
        
        
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
                        $this->view->headMeta()->setName('description', $this->community->display . ' ' . $module->m_display . "s.");
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
                    'url' => '/' . $this->view->SEO_Urlify(isset($this->target->space->display) ? $this->target->space->display : $this->target->space->name) . '/s' . (isset($this->target->pre) ? $this->target->pre : '') . '/' . $this->internal->params->submodule . '/'
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
                'href' => (isset($this->target->pre) ? $this->target->pre : '') . '/',
                'title' => $this->view->escape($this->community->display ? $this->community->display : $this->community->name)
            ));
            $this->view->headMeta()->setName('robots', 'all');
        }
        
        // Render In SubLayout
        // Force the set of a pre in javascript
        $this->view->inlineScript()->appendScript("var vm_pre = '" . ($this->target->pre ? $this->target->pre : '/com/' . $this->target->id) . "';");
        $this->_helper->ViaMe->setSubLayout('space');
        return $this->_helper->viewRenderer->setNoRender();
        
        /*
        // META
        //  Community based meta done in the community plugin
        
        if (isset($this->internal->params->submodule)
          && array_key_exists($this->internal->params->submodule, $this->module_modules)) {
            $this->_helper->actionStack(
                ((isset($this->internal->params->subaction) && $this->internal->params->subaction != '') ? $this->internal->params->subaction : 'index'),
                ((isset($this->internal->params->subcontroller) && $this->internal->params->subcontroller != '') ? $this->internal->params->subcontroller : 'index'),
                ($this->internal->params->submodule),
                (array) $this->internal->params
            );
            
            // META
            foreach ($this->target->modules as $module) {
                if ($module->m_name == $this->internal->params->submodule &&
                    ($module->counter == ($this->_getParam('mid')))) {
                    $this->view->headTitle($module->display ? $module->display : $module->m_display, 'PREPEND');
                    if ($module->meta_description) {
                        $this->view->headMeta()->setName('description', $module->meta_description);
                    }
                    if ($module->meta_keywords) {
                        $this->view->headMeta()->setName('keywords', $module->meta_keywords);
                    }
                    
                    // Set the module from the matrix
                    $this->target->currentModule = $module;
                    $this->log->setEventItem('matrix_counter', $module->counter);
                    
                    break;
                }
            }
        }
        */
    }
    
    
    public function __call($method, $args)
    {
        return $this->_forward('index');
    }
}