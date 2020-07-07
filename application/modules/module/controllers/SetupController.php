<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_SetupController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (!$this->_getParam('id') && $this->target->type == 'VIA') { $this->_mustBeOwner = true; }
    }


    public function indexAction()
    {
        $this->view->headTitle('Setup Space Modules', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        
        if ($this->target->type == 'VIA') {
            $modules = $this->target->modules;
            foreach ($this->member->profiles as $temp_profile) {
                if ($temp_profile->id == $this->target->id) {
                    $profile = $temp_profile;
                    break;
                }
            }
        }
        
        if ($this->_getParam('id')) {
            if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
                $id = $this->_getParam('id');
            }
            else {
                foreach ($this->member->profiles as $temp_profile) {
                    if ($temp_profile->id == $this->_getParam('id') && (($temp_profile->same_member_priv) || ($temp_profile->id == $this->member->profile->id))) {
                        $id = $temp_profile->id;
                        break;
                    }
                }
            }
            
            if ($this->target->type == 'VIA' && $this->target->id != $this->_getParam('id')) {
                unset($modules, $profile);
            }
        }
        elseif ($this->target->type == 'VIA') {
            foreach ($this->member->profiles as $temp_profile) {
                if ($temp_profile->id == $this->target->id) {
                    // Flow privileges already checked in init
                    $id = $temp_profile->id;
                }
            }
        }
        else {
            $id = $this->member->profile->id;
            $profile = $this->member->profile;
        }
        
        if (!$id) { return $this->_unauthorized('Access Error', 'You do not have sufficient privileges to access this area.'); }
        
        
        // Got a valid id - validate profile and/or modules
        if (!isset($profile) && !($profile = $this->db->fetchRow("SELECT community_id FROM profile_profiles WHERE active='t' AND id=?", $id))) {
            return $this->_denied('Profile Error', 'That profile does not exist or is deactivated.');
        }
        
        if ((isset($modules) && count($modules)) || (!isset($modules) && ($this->db->fetchOne("SELECT COUNT(*)-1 FROM module_matrix WHERE active NOTNULL AND via_id=?", $id)))) {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Cannot Setup Space',
                'bd' => '<p class="error">Cannot Setup Space</p><p>Cannot setup a space that is already in use and populated with content modules.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : '/via/' . $id . '/') . '">Continue &raquo;</a></p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
        
        
        // All fetched, checked, and accounted for - SYNC up with member/register/verify
        $this->db->beginTransaction();
        try {
            // Insert the default modules
            $this->db->query("INSERT INTO module_matrix (via_id, profile_id, module_id) (SELECT ?, ?, id FROM module_modules WHERE status='t' AND active='t' AND name IN (SELECT list_array(default_modules) FROM system_communities WHERE active='t' AND id=?))", array($id, $id, $profile->community_id));
            
            // Add some generic widgets
            $this->db->query("INSERT INTO widget_matrix (via_id, profile_id, widget_id) (SELECT ?, ?, id FROM widget_widgets WHERE status='t' AND active='t' AND name IN (SELECT list_array(ARRAY['breadcrumb', 'vmodulemenu', 'addthis'])))", array($id, $id));
            
            $num_columns = 2; // Not including fixed column
            $counter = 0;
            $col_mods = array();
            #foreach ($this->db->fetchAll('SELECT x.module_id, x.counter FROM "module_matrix" AS "x" INNER JOIN "module_modules" AS "m" ON x.module_id=m.id WHERE (m.id > 0) AND (x.active=' . "'t'" . ') AND (x.via_id=?) ORDER BY m.name', array($id)) as $temp) {
            foreach ($this->db->fetchAll('SELECT x.module_id, x.counter FROM "module_matrix" AS "x" INNER JOIN "module_modules" AS "m" ON x.module_id=m.id LEFT JOIN "system_communities" AS "sc" ON sc.id=? WHERE (m.id > 0) AND (x.active=?) AND (x.via_id=?) ORDER BY idx(sc.default_modules, m.name::text)', array($profile->community_id, 't', $id)) as $temp) {
                $col_mods[$counter % $num_columns][] = 'm-' . $temp->module_id . '-' . $temp->counter;
                $counter++;
            }
            
            // Setup the space layout - Organize the layout a little
            $this->db->query("UPDATE profile_profiles SET page_layout=?, page_sublayout=?, grid_hd=ARRAY['w-8-1'], grid_ft='{}', grid_cx=ARRAY['w-2-1', 'w-9-1'], grid_c1=?, grid_c2=? WHERE id=?", array('t2', 'g', 
                '{"' . implode('", "', $col_mods[0]) . '"}',
                '{"' . implode('", "', $col_mods[1]) . '"}',
                $id));
            
            $this->db->commit();
        } catch (Exception $e) {
            #$profiler = $this->db->getProfiler();
            #$query = $profiler->getLastQueryProfile();
            #echo $query->getQuery();
            #Zend_Debug::Dump(array('t2', $id));
            
            $this->db->rollBack();
            
            $this->log->ALERT('Setup failed!');
            
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Setup Failed',
                'hd2' => 'Please try setting up again',
                'bd' => '<p class="error">An error has occurred. Your space setup has failed.</p><p>An unexpected error has occurred and has caused your space setup to not complete.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : '/via/' . $id . '/') . '">Continue &raquo;</a></p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
        
        echo $this->view->CM(array(
            'class' => 'cm decorated plain successmessage',
            'hd' => 'Setup Complete',
            'hd2' => 'Space setup has been completed',
            'bd' => '<p class="success">Space Setup Complete</p><p>Your space has been setup with the default content modules.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : '/via/' . $id . '/') . '">Continue &raquo;</a></p>'
        ));
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function publishAction()
    {
        // Work out a via space
        if ($this->target->type == 'VIA') {
            $target_via = $this->target->id;
        }
        
        if ($this->_getParam('id')) {
            if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
                $target_via = $this->_getParam('id');
            }
            else {
                foreach ($this->member->profiles as $temp_profile) {
                    if ($temp_profile->id == $this->_getParam('id') && (($temp_profile->same_member_priv) || ($temp_profile->id == $this->member->profile->id))) {
                        $target_via = $temp_profile->id;
                        break;
                    }
                }
            }
        }
        elseif ($this->target->type == 'VIA') {
            foreach ($this->member->profiles as $temp_profile) {
                if ($temp_profile->id == $this->target->id) {
                    // Flow privileges already checked in init
                    $target_via = $temp_profile->id;
                }
            }
        }
        else {
            $target_via = $this->member->profile->id;
        }
        
        if (!$target_via) { return $this->_unauthorized('Access Error', 'You do not have sufficient privileges to access this area.'); }
        
        
        // Work out the module
        if ($this->_getParam('module_name') && isset($this->module_modules[$this->_getParam('module_name')])) {
            $target_module_id = $this->module_modules[$this->_getParam('module_name')]->id;
        }
        elseif ($this->_getParam('module_id')) {
            $target_module_id = $this->_getParam('module_id');
        }
        
        
        // Only valid for via spaces
        if (isset($target_via) && $target_via && isset($target_module_id) && $target_module_id) {
            #echo "Target VIA : $target_via<br />";
            #echo "Target Module ID : $target_module_id<br />";
            #echo "Redirect : " . $this->_getParam('redirect');
            
            $select = $this->db->select()
                ->from(array('x' => 'module_matrix'), array('counter'))
                  ->where("x.active='t'")
                  ->where('x.via_id=?', $target_via)
                  ->where('x.module_id=?', $target_module_id)
                ->join(array('m' => 'module_modules'), 'x.module_id = m.id', array())
                  ->where('m.active=?', 't')
                  ->where('m.system ISNULL OR m.system <> ?', 't')
                // Redundant as we are already have a valid via - Keep For allowed acl clause
                ->join(array('p' => 'profile_profiles'), 'p.id = x.via_id', array())
                  ->where('p.active=?', 't')
                ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                  ->where('b.active=?', 't')
                ->order('counter')
                ->limit(1);
            #echo $select;
            #Zend_Debug::Dump($this->_getParam('redirect'));
            if ($matrix_counter = $this->db->fetchOne($select)) {
                $this->_redirect(
                #print (
                    '/via/' . $target_via . '/' . $this->module_modules[$target_module_id]->name . '/create/p/mid/' . $matrix_counter . '/?redirect=' .
                    ($this->_getParam('redirect') ? urlencode($this->_getParam('redirect')) : '') .
                    ($this->_getParam('extra_params') ? '&' . $this->_getParam('extra_params') : '')
                );
            }
            else {
                $this->_redirect(
                #print (
                    '/via/' . $target_via . '/module/add/p/vmpd_fp/1/widget/1/publicly_searchable/1/interactive/1/next_action/publish/module_id/' . $target_module_id . '/' .
                    ($this->_getParam('extra_params') ? 'extra_params/' . urlencode($this->_getParam('extra_params')) . '/' : '') .
                    ($this->_getParam('redirect') ? '?redirect=' . preg_replace('/&/', '%26', $this->_getParam('redirect')) : '')
                );
            }
        }
        
        
        // Didn't get redirect - display error
        echo $this->view->CM(array(
            'class' => 'cm decorated plain errormessage',
            'hd' => 'Publishing Error',
            'bd' => '<p class="error">Publishing Error</p><p>An error has occurred while trying to publish.</p>'
        ));
        return $this->_helper->viewRenderer->setNoRender();
        
        $this->_redirect('http://' . (isset($this->vars->language) ? $this->vars->language . '.' : '') . 'www.' . $this->config->default_domain);
        
    }
}