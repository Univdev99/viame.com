<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_IndexController extends ViaMe_Controller_Action
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
    }
    
    
    public function indexAction() {
        $this->view->headTitle('Contact Manager', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Display Profiles
        $select = $this->db->select()
            ->from(array('c' => 'contact_contacts'))
            ->join(array('p' => 'profile_profiles'), 'c.contact_profile_id = p.id', array('name' => 'name'))
            ->where('p.active=?', 't')
            ->join(array('p2' => 'profile_profiles'), 'c.profile_id = p2.id', array('p2_name' => 'name'))
            ->where('p2.active=?', 't')
            ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
            ->where('b.active=?', 't')
            ->join(array('b2' => 'member_members'), 'p2.member_id = b2.id', array())
            ->where('b2.active=?', 't')
            ->order(array('status', 'name'));
        
        if ($this->_getParam('gid')) {
            switch ($this->_getParam('gid')) {
                case 'I': // Incoming Requests
                    $select->where('c.contact_profile_id=?', $this->target->id)->where('c.status ISNULL')->where('c.active=?', 't');
                    break;
                case 'R': // Outgoing Requests
                    $select->where("(c.profile_id=? AND (c.status ISNULL OR c.active='f'))", $this->target->id);
                    break;
                case 'P': // Outgoing Requests - Pending
                    $select->where('c.profile_id=?', $this->target->id)->where('c.status ISNULL')->where('c.active=?', 't');
                    break;
                case 'D': // Outgoing Requests - Denied
                    $select->where('c.profile_id=?', $this->target->id)->where('c.status=?', 't')->where('c.active=?', 'f');
                    break;
                case 'F': // Outgoing Requests - Denied Forever
                    $select->where('c.profile_id=?', $this->target->id)->where('c.status=?', 'f')->where('c.active=?', 'f');
                    break;
                /*
                case 'A': // All Contacts and Requests
                    $select->where("((c.profile_id=? AND c.active NOTNULL) OR (c.contact_profile_id=? AND c.status ISNULL and c.active='t'))", $this->target->id);
                    break;
                */
                default: // Specific Group Specified Contacts
                    $select
                        ->where('c.profile_id=?', $this->target->id)
                        ->join(array('g' => 'contact_group_groups'), 'g.profile_id = c.profile_id', array())
                        ->where('g.counter = ?', $this->_getParam('gid'))
                        ->where('g.active=?', 't')
                        ->join(array('m' => 'contact_group_members'), 'm.profile_id=g.profile_id AND m.group_counter_id=g.counter AND m.member_profile_id=p.id', array())
                        ->where('m.active=?', 't');
                    break;
            }
        } else {
            // Approved Contacts
            $select->where('c.profile_id=?', $this->target->id)->where('c.status=?', 't')->where('c.active=?', 't');
        }
        
        $this->view->contacts = $this->db->fetchAll($select);
        
        /*
        // Display Incoming Requests
        $select = $this->db->select()
            ->from(array('c' => 'contact_contacts'))
            ->where('c.contact_profile_id=?', $this->target->id)
            ->where('c.status ISNULL')
            ->where('c.active NOTNULL')
            ->join(array('p' => 'profile_profiles'), 'c.profile_id = p.id', array('name' => 'name'))
            ->where('p.active=?', 't')
            ->join(array('p2' => 'profile_profiles'), 'c.contact_profile_id = p2.id', array())
            ->where('p2.active=?', 't')
            ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
            ->where('b.active=?', 't')
            ->join(array('b2' => 'member_members'), 'p2.member_id = b2.id', array())
            ->where('b2.active=?', 't')
            ->order(array('status', 'name'));
        
        $this->view->requests = $this->db->fetchAll($select);
        */
        
        /*
        // Display Groups
        $select = $this->db->select()
            ->from(array('g' => 'contact_group_groups'),
                array(
                    '*',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('contact_group_groups', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('g.active=?', 't')
            ->where('g.profile_id=?', $this->target->id)
            ->order(array('sort_order', 'g.counter', 'g.name'));
        
        $temp_groups = $temp_sorted_groups = array();
        foreach ($this->db->fetchAll($select) as $group) {
            $temp_groups[$group->sort_order] = $group;
        }
        
        if (count($temp_groups)) {
            $temp_sorted_keys = array_keys( $temp_groups );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $temp_sorted_groups[$temp_groups[$key]->counter] = $temp_groups[$key];
            }
        }
        
        $this->view->groups = $temp_sorted_groups;
        */
    }
}
