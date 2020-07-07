<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_DeleteController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        // Delete Profile
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE network_networks SET active=NULL WHERE id=?", Array($this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}