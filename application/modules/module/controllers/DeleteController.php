<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_DeleteController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function indexAction()
    {
        if ($this->_getParam('mid') && $this->_getParam('id')) {
            $query = 'UPDATE module_matrix SET active=NULL WHERE module_id=? AND counter=? AND ' . strtolower($this->target->type) . '_id=?';
        
            $this->db->query($query, Array($this->_getParam('mid'), $this->_getParam('id'), $this->target->id));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}