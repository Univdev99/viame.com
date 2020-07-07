<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Acl_DeleteController extends ViaMe_Controller_Action
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
        // Delete ACL
        if ($this->_getParam('id')) {
            $acl_acls = new acl_models_acls();
            $row = $acl_acls->find(
                    ($this->target->type == 'COM' ? $this->target->id : 0),
                    ($this->target->type == 'NET' ? $this->target->id : 0),
                    ($this->target->type == 'VIA' ? $this->target->id : 0),
                    $this->_getParam('mod', 0),
                    $this->_getParam('mid', 0),
                    $this->_getParam('iid', 0),
                    $this->_getParam('id')
                )->current();
            #$row->delete();
            $row->active = new Zend_Db_Expr('null');
            $row->save();
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
    
    public function purgeAction()
    {
        // Purge ACL
        $where = array();
        
        switch($this->target->type) {
            case 'VIA' :
                $where[] = $this->db->quoteInto('via_id=?', $this->target->id);
                break;
            case 'NET' :
                $where[] = $this->db->quoteInto('net_id=?', $this->target->id);
                break;
            default :
                $where[] = $this->db->quoteInto('com_id=?', $this->target->id);
                break;
        }
        
        if ($this->_getParam('mod')) { $where[] = $this->db->quoteInto('module_id=?', $this->_getParam('mod')); }
        if ($this->_getParam('mid')) { $where[] = $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid')); }
        if ($this->_getParam('iid')) { $where[] = $this->db->quoteInto('item_counter=?', $this->_getParam('iid')); }
        
        if ($where) {
            #$this->db->delete('acl_acls', $where);
            $this->db->update('acl_acls', array('active' => null), $where);
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}