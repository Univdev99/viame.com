<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_LeaveController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        # Should add a confirmation and ability to cancel
        
        
        if ($this->target->type == 'NET') {
            $network_members = new network_models_members();
            
            $where[] = $network_members->getAdapter()->quoteInto('network_id=?', $this->target->id);
            $where[] = $network_members->getAdapter()->quoteInto('member_profile_id=?', $this->member->profile->id);
            
            $network_members->delete($where);
        }
        
        $this->_autoredirect($this->target->pre . '/');
    }
}