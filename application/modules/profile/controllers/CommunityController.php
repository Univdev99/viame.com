<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_CommunityController extends ViaMe_Controller_Action
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
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function addAction()
    {
        // Add Community to a Profile
        $this->db->query("UPDATE profile_profiles SET community_ids = array_distinct(community_ids || ? ::bigint) WHERE id = ?", array(($this->_getParam('id') ? $this->_getParam('id') : $this->community->id), $this->target->id));

        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
    
    
    public function removeAction()
    {
        // Remove Community from a Profile
        $this->db->query("UPDATE profile_profiles SET community_ids = array_except(community_ids, array[? ::bigint]) WHERE id = ?", array(($this->_getParam('id') ? $this->_getParam('id') : $this->community->id), $this->target->id));

        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
    
    
    public function switchAction()
    {
        // Switch a Profile's Community
        $this->db->query("UPDATE profile_profiles SET community_id = ? WHERE id = ?", array(($this->_getParam('id') ? $this->_getParam('id') : $this->community->id), $this->target->id));

        $this->_autoredirect('/via/' . $this->target->id . '/');
    }
}