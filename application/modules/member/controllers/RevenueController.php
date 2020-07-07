<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_RevenueController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->member_id;
        }
        elseif (isset($this->member)) {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
        
        $this->view->headTitle('Member', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Revenue', 'PREPEND');
        
        $date = new Zend_Date();
        $start_date = null;
        $end_date = null;
        if (isset($this->member)) { $date->setTimezone($this->internal->member->timezone); }
        if ($this->_getParam('start_date')) {
            $date->set($this->_getParam('start_date'));
            $start_date = $date->get(Zend_Date::ISO_8601);
        }
        if ($this->_getParam('end_date')) {
            $date->set($this->_getParam('end_date'));
            $end_date = $date->get(Zend_Date::ISO_8601);
        }
        
        
        
        // Clicks
        $select = $this->db->select()
            ->from(array('obj' => 'member_referrer_clicks_counts'), 'COUNT(*)')
            ->where('obj.active=?', 't')
            
            ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                array()
            )
            
            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                array()
            )
            ->where('b.id=?', $this->member->id)
        ;
        // Date Range
        if ($this->_getParam('start_date')) { $select->where('date(obj.creation) >= ?', $start_date); }
        if ($this->_getParam('end_date')) { $select->where('date(obj.creation) <= ?', $end_date); }
        $this->view->total_clicks = $this->db->fetchOne($select);
        
        
        
        // Records
        $select = $this->db->select()
            ->from(array('obj' => 'log_trans'))
            ->where('obj.active=?', 't')
            #->where('obj.amount <> 0')
            
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
            
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                )
            )
            
            // Left Join on acl_acls
            ->joinLeft(array('aa' => 'acl_acls'), 'obj.com_id=aa.com_id AND obj.net_id=aa.net_id AND obj.via_id=aa.via_id AND obj.module_id=aa.module_id AND obj.matrix_counter=aa.matrix_counter AND obj.item_counter=aa.item_counter AND obj.acl_counter=aa.counter',
                array(
                    'aa_title' => 'title',
                    'aa_description' => 'description',
                    'aa_display' => 'display'
                )
            )
            
            // Left Join For referrer on member
            ->joinLeft(array('rpa' => 'profile_profiles'), 'b.referrer_profile_id=rpa.id',
                array(
                    'rpa_name' => 'name',
                    'rpa_id' => 'id',
                    'rpa_member_id' => 'member_id',
                    'rpa_site_admin' => 'site_admin',
                    'rpa_active' => 'active',
                    'rpa_picture_url' => 'picture_url',
                    'rpa_total_followers_count' => 'total_followers_count',
                    'rpa_total_following_count' => 'total_following_count'
                )
            )
            
            // Left Join For referrer on log_trans
            ->joinLeft(array('rpb' => 'profile_profiles'), 'obj.referrer_profile_id=rpb.id',
                array(
                    'rpb_name' => 'name',
                    'rpb_id' => 'id',
                    'rpb_member_id' => 'member_id',
                    'rpb_site_admin' => 'site_admin',
                    'rpb_active' => 'active',
                    'rpb_picture_url' => 'picture_url',
                    'rpb_total_followers_count' => 'total_followers_count',
                    'rpb_total_following_count' => 'total_following_count'
                )
            )
            
            // referrer_profile_id in log_trans overrides or takes precedence over the member_member referrer_profile_id
            ->where('rpb.member_id=? OR (rpa.member_id=? AND (rpb.member_id=? OR rpb.member_id ISNULL))', $this->member->id)
            
            ->order('obj.creation')
            #->limit(10)
        ;
        // Date Range
        #if ($this->_getParam('start_date')) { $select->where('date(obj.creation) >= ?', $start_date); }
        #if ($this->_getParam('end_date')) { $select->where('date(obj.creation) <= ?', $end_date); }
        $this->view->records = $this->db->fetchAll($select);
    }
}