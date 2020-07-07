<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_MultiController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
        
        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function indexAction() {
        if ($this->_getParam('function') && method_exists(get_class(), (strtolower($this->_getParam('function')) . 'Action'))) {
            $func = strtolower($this->_getParam('function')) . 'Action';
            $this->$func();
        }
    }
    
    
    public function postDispatch()
    {
        if ($this->_getParam('nar')) {
            return $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        else {
            return $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
    }
    
    
    public function approveAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        // Approve Contact Request
        if (count($cids)) {
            
            $contact_contacts = new contact_models_contacts();
            
            $data = array(
                'status' => 't',
                'message' => NULL
            );
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->target->id);
            $where[] = 'status ISNULL';
            
            $temp_array = $notr_array = array();
            foreach ($cids as $temper) {
                $temp_array[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $temper);
                $notr_array[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $temper);
            }
            $where[] = implode(' OR ', $temp_array);
            
            
            if ($contact_contacts->update($data, $where)) {
                // Reciprocate Add Contact - No Customization
                if ($this->_getParam('r')) {
                    try {
                        $this->db->query("INSERT INTO contact_contacts (profile_id, contact_profile_id, status, active) SELECT contact_profile_id, profile_id, NULLIF(auto_reciprocate, 'f'::bool), active FROM contact_contacts WHERE contact_profile_id=? AND (". implode(' OR ', $temp_array) . ")", array($this->target->id));
                    } catch (Exception $e) { }
                    
                    $not_reciprocated = array();
                    // Load ids of not-reciprocated contacts
                    $not_reciprocated = $this->db->fetchCol("SELECT contact_profile_id FROM contact_contacts WHERE profile_id=? AND active='t' AND status ISNULL AND (" . implode(' OR ', $notr_array) . ")", array($this->target->id));
                }
                
                try {
                    // Have to backwards selected the contact
                    $select = $this->db->select()
                        ->from(array('obj' => 'profile_profiles'), array(
                                'p_id' => 'id',
                                'p_name' => 'name',
                                'p_site_admin' => 'site_admin',
                                'p_active' => 'active'
                            ))
                        
                        ->join(array('b' => 'member_members'), 'obj.member_id = b.id',
                            array(
                                'b_email' => 'email',
                                'b_site_admin' => 'site_admin',
                                'b_active' => 'active',
                            )
                        )
                        
                        ->join(array('c' => 'system_communities'), 'obj.community_id = c.id',
                            array(
                                'c_id' => 'id',
                                'c_name' => 'name',
                                'c_hostname' => 'hostname'
                            )
                        );
                    
                    // Send E-Mails
                    foreach ($cids as $temp) {
                        $select1 = clone $select;
                        $select1->where('obj.id=?', $this->target->id)
                            ->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $temp),
                                array(
                                    'vc_status' => 'status',
                                    'vc_display' => 'display'
                                )
                            );
                        if ($tp = $this->db->fetchRow($select1)) {
                            $select2 = clone $select;
                            $select2->where('obj.id=?', $temp);
                            
                            if ($tf = $this->db->fetchRow($select2)) {
                                $partial_array = array('profile' => $tp, 'tf' => $tf, 'not_reciprocated' => $not_reciprocated, 'internal' => $this->internal);                
                                $this->_helper->ViaMe->sendEmail(
                                    $tf->b_email,
                                    $tf->p_name,
                                    ((isset($tp->vc_display) && $tp->vc_display) ? $tp->vc_display : $tp->p_name) . ' Has Approved Your Contact Request On ' . $this->community->display,
                                    $this->view->partial('multi/emails/approve.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display
                                );
                            }
                        }
                    }
                } catch (Exception $e) { }
            }
        }
    }
    
    
    public function denyAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        // Deny Contact Request
        if (count($cids)) {
            
            $contact_contacts = new contact_models_contacts();
            
            $data = array(
                'active' => 'f',
                'status' => ($this->_getParam('f') ? 'f' : 't'),
                'message' => ($this->_getParam('m', null) ? $this->_getParam('m') : null)
            );
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->target->id);
            $where[] = 'status ISNULL';
            
            $temp_array = array();
            foreach ($cids as $temper) {
                $temp_array[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $temper);
            }
            $where[] = implode(' OR ', $temp_array);
            
            $contact_contacts->update($data, $where);
        }
    }
    
    
    public function deleteAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        // Deny Contact Request
        if (count($cids)) {
            
            $contact_contacts = new contact_models_contacts();
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->target->id);
            $where[] = $contact_contacts->getAdapter()->quoteInto('status ISNULL OR status <> ?', 'f');
            
            $temp_array = array();
            foreach ($cids as $temper) {
                $temp_array[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $temper);
            }
            $where[] = implode(' OR ', $temp_array);
            
            $contact_contacts->delete($where);
        }
    }
    
    
    public function hideAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        // Deny Contact Request
        if (count($cids)) {
            
            $contact_contacts = new contact_models_contacts();
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->target->id);
            $where[] = $contact_contacts->getAdapter()->quoteInto('status = ? AND active = ?', 'f');
            
            $temp_array = array();
            foreach ($cids as $temper) {
                $temp_array[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $temper);
            }
            $where[] = implode(' OR ', $temp_array);
            
            $contact_contacts->update(array('active' => null), $where);
        }
    }
    
    
    public function addgroupAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        if ($this->_getParam('gid')) { $gid = $this->_getParam('gid'); }
        elseif ($this->_getParam('gid2')) { $gid = $this->_getParam('gid2'); }
        
        // Deny Contact Request
        if (count($cids) && $gid) {
            
            // Handle Add To Group
            $which = array();

            foreach ($cids as $vc) {
                $which[] = $this->db->quoteInto('contact_profile_id=?',  $vc);
            }
            
            $query = 'INSERT INTO contact_group_members (profile_id, group_counter_id, member_profile_id) SELECT ';
            $query .= $this->db->quoteInto('?, ', $this->target->id);
            $query .= $this->db->quoteInto('?, contact_profile_id FROM contact_contacts WHERE ', $gid);
            $query .= $this->db->quoteInto("profile_id=? AND status='t' AND active='t'", $this->target->id);
            $query .= ' AND (' . implode(' OR ', $which) . ')';
            
            // Avoid Duplicates to avoid failure
            $query .= $this->db->quoteInto(' AND contact_profile_id NOT IN (SELECT member_profile_id FROM contact_group_members WHERE profile_id=?', $this->target->id);
            $query .= $this->db->quoteInto(' AND group_counter_id=?)', $gid);
            
            try {
                $this->db->query($query);
            } catch (Exception $e) { }
        }
    }
    
    
    public function remgroupAction() {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $cids = $this->_getParam('cid');
        if ($cids && !is_array($cids)) {
            $cids = array($cids);
        }
        
        if ($this->_getParam('gid')) { $gid = $this->_getParam('gid'); }
        elseif ($this->_getParam('gid2')) { $gid = $this->_getParam('gid2'); }
        
        // Deny Contact Request
        if (count($cids) && $gid) {
            // Handle Delete From Group
            $which = $where = array();

            foreach ($cids as $vc) {
                $which[] = $this->db->quoteInto('member_profile_id=?',  $vc);
            }
            
            $contact_group_members = new contact_models_groupMembers();
            
            $where[] = $contact_group_members->getAdapter()->quoteInto('profile_id=?', $this->target->id);
            $where[] = $contact_group_members->getAdapter()->quoteInto('group_counter_id=?', $gid);
            $where[] = '(' . implode(' OR ', $which) . ')';
            
            $contact_group_members->delete($where);
        }
    }
}