<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Acl_ControlController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        // Display ACLs
        switch($this->target->type) {
            case 'VIA' :
                $selector = 'via_id';
                break;
            case 'NET' :
                $selector = 'net_id';
                break;
            default :
                $selector = 'com_id';
                break;
        }
        
        $select = $this->db->select()
            ->from(array('a' => 'acl_acls'))
            ->where("a.$selector=?", (int) $this->target->id)
            ->where('a.module_id=?', (int) $this->_getParam('mod', 0))
            ->where('a.matrix_counter=?', (int) $this->_getParam('mid', 0))
            ->where('a.item_counter=?', (int) $this->_getParam('iid', 0))
            ->where('a.active NOTNULL')
            
            // Join the Creator's Profile
            ->join(array('p' => 'profile_profiles'), 'a.profile_id = p.id', array('name' => 'name'))
            #->where('p.active=?', 't')
            
            // Left Join Profiles in Contacts
            ->joinLeft(array('c' => 'contact_contacts'), $this->db->quoteInto('p.id=c.contact_profile_id AND c.profile_id=?', $this->member->profile->id), array('cc_display' => 'display'))

            #->join(array('p2' => 'profile_profiles'), 'c.profile_id = p2.id', array())
            #->where('p2.active=?', 't')
            #->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
            #->where('b.active=?', 't')
            #->join(array('b2' => 'member_members'), 'p2.member_id = b2.id', array())
            #->where('b2.active=?', 't')
            ->order(array('orderby DESC'));
        
        $this->view->acls = $this->db->fetchAll($select);
    }
}