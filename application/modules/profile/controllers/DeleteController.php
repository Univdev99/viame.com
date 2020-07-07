<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_DeleteController extends ViaMe_Controller_Action
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
        else {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        // Delete Profile
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE profile_profiles SET active=NULL WHERE base<>'t' AND member_id=? AND id=?", Array($this->target->id, $this->_getParam('id')));
        }
        
        // After a Profile switch, must redirect to cause a user reload
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}