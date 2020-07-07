<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_DeleteController extends ViaMe_Controller_Action
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
        if ($this->_getParam('wid') && $this->_getParam('id')) {
            $query = 'UPDATE widget_matrix SET active=NULL WHERE widget_id=? AND counter=? AND ' . strtolower($this->target->type) . '_id=?';
                    
            $this->db->query($query, Array($this->_getParam('wid'), $this->_getParam('id'), $this->target->id));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}