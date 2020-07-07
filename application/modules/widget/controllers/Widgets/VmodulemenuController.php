<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_VmodulemenuController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        //$this->_helper->viewRenderer->setNoRender();
        
        if ($widget = $this->_getParam('widget')) {
            $params = null;
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            $this->view->params = $params;
            
            $this->view->i = $this->internal;
            $this->view->new_module_set = $this->internal->target->modules;
        }
    }
    
    
    public function profileAction()
    {
        if ($this->_getParam('profile_id')) {
            $this->view->PTYPE = true;
            
            if ($this->internal->target->type == 'VIA' && ($this->internal->target->id == $this->_getParam('profile_id'))) {
                $this->view->i = $this->internal;
                
                $pCountID = $this->_getParam('profile_id');
                $pModules = $this->internal->target->modules;
            }
            else {
                /* SYNC this up with default/viacontroller */
                $acl_clause = $this->db->quoteInto('(check_acl(p.acl, ?, 0, 0, p.id, 0, 0, 0, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                $select = $this->db->select()
                    ->from(array('p' => 'profile_profiles'),
                        array('*',
                            $this->db->quoteInto('(p.member_id=? OR ', (isset($this->member->id) ? $this->member->id : 0)) . "$acl_clause.allowed) AS allowed",
                            "$acl_clause.privilege",
                            "$acl_clause.filter",
                            "$acl_clause.recursive"
                        )
                    )
                    ->where("p.active='t'")
                    ->where('p.id=?', $this->_getParam('profile_id'))
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array('b_id' => 'id', 'b_site_admin' => 'site_admin', 'b_active' => 'active', 'b_email' => 'email'))
                    ->where('b.active=?', 't')
                    ->join(array('c' => 'system_communities'), 'p.community_id = c.id', array('c_id' => 'id', 'c_name' => 'name', 'c_hostname' => 'hostname'))
                    ->where('c.active=?', 't');
                
                if (isset($this->member)) {
                    $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                        array(
                            'vc_status' => 'status',
                            'vc_display' => 'display'
                        )
                    );
                }
                
                try {
                    $via = $this->db->fetchRow($select);
                } catch (Exception $e) { }
                
                if ($via) {
                    $this->view->i = new StdClass;
                    $this->view->i->target = new StdClass;
                    $this->view->i->target->acl = new StdClass;
                    
                    $via->p_id = $via->id;
                    $via->p_name = $via->name;
                    $via->p_site_admin = $via->site_admin;
                    $via->p_active = $via->active;
                    
                    if (isset($this->member) && ((isset($this->member->profile->id) && $this->member->profile->id == $via->id) || ($via->same_member_priv && ($via->member_id == $this->member->id)) || ($this->member->site_admin) || (isset($this->member->profile) && $this->member->profile->site_admin))) {
                        // Owner
                        $this->view->i->target->acl->owner = true;
                        $this->view->i->target->acl->allowed = true;
                        $this->view->i->target->acl->privilege = 100;
                        $this->view->i->target->acl->filter = null;
                        $this->view->i->target->acl->recursive = true;
                    }
                    else {
                        $this->view->i->target->acl->owner = false;
                        $this->view->i->target->acl->allowed = $via->allowed;
                        $this->view->i->target->acl->privilege = $via->privilege;
                        $this->view->i->target->acl->filter = $via->filter;
                        $this->view->i->target->acl->recursive = $via->recursive;
                    }
                    
                    $this->view->i->target->id = $this->_getParam('profile_id');
                    $this->view->i->target->type = 'VIA';
                    $this->view->i->target->pre = '/via/' . $this->_getParam('profile_id');
                    $this->view->i->locale = $this->internal->locale;
                    
                    $this->view->i->target->space = $via;
                    
                    
                    
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
                        ->where('x.via_id=?', $this->_getParam('profile_id'))
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
                        ->order(array('x.display_orderby', 'x.orderby', 'x.display', 'm.display', 'm.name', 'x.counter', 'm.id'));
                    
                    # Need to remove because this module list is used elsewhere, like modifying and listing modules
                    #if (!$this->target->acl->owner) {
                    #    $select->where("x.acl ISNULL OR x.show_on_fail='t' OR ($acl_clause.allowed ISNULL OR ($acl_clause.allowed AND ($acl_clause.privilege > 0)))");
                    #}
                    
                    $pModules = $this->db->fetchAll($select);
                
                    $pCountID = $this->_getParam('profile_id');
                }
            }
            
            if (isset($pCountID)) {
                require_once $this->vars->APP_PATH . "/modules/system/controllers/WidgetController.php";
                $this->_setParam('nomid', 1);
                $this->_setParam('alternate_target_id', $pCountID);
                $this->_setParam('alternate_table_name', 'module_template');
                $SWC = new System_WidgetController($this->getRequest(), $this->getResponse());
                $pSelect = $SWC->profileAction();
                
                $select = $this->db->select()
                  ->from(array('tt' => new Zend_Db_Expr('(' . $pSelect->__toString() . ')')), array('module_id' => 'module_id', 'the_count' => 'COUNT(*)'))
                  ->group('module_id');
                ;
                
                $pCount = $this->db->fetchAll($select);
                
                // Combine the results
                $combinedModules = array();
                $seenModuleID = array();
                foreach ($pModules as $module) {
                    if (!(in_array($module->module_id, $seenModuleID))) {
                        $seenModuleID[] = $module->module_id;
                        $temp = new StdClass;
                        $temp->display_stack = null;
                        $temp->m_display = $module->m_display;
                        $temp->m_name = $module->m_name;
                        $temp->allowed = true;
                        $temp->show_on_fail = true;
                        $temp->total_item_count = 0;
                        foreach ($pCount as $count) {
                            if ($count->module_id == $module->module_id) {
                                $temp->total_item_count = $count->the_count;
                                break;
                            }
                        }
                        $combinedModules[] = $temp;
                    }
                }
                // Stragglers
                $unseen = array();
                foreach ($pCount as $module) {
                    if (!(in_array($module->module_id, $seenModuleID))) {
                        if (!isset($unseen[$module->module_id])) { $unseen[$module->module_id] = 0; }
                        $unseen[$module->module_id] += $module->the_count;
                    }
                }
                foreach ($unseen as $module_id => $the_count) {
                    if (!in_array($module_id, $seenModuleID)) {
                        $seenModuleID[] = $module_id;
                        $temp = new StdClass;
                        $temp->display_stack = null;
                        $temp->m_display = $this->module_modules[$module_id]->display;
                        $temp->m_name = $this->module_modules[$module_id]->name;
                        $temp->allowed = true;
                        $temp->show_on_fail = true;
                        $temp->total_item_count = $the_count;
                        
                        $combinedModules[] = $temp;
                    }
                }
                
                $this->view->new_module_set = $combinedModules;
                
                echo  $this->view->CM(array(
                    'class' => 'cm widget w_vmodulemenu',
                    'bd' => $this->view->render('widgets/vmodulemenu/index.phtml')
                ));
            }
        }
        
        $this->_helper->viewRenderer->setNoRender(true);
    }
}