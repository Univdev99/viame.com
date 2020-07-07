<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_ModerateController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_MODERATE;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        // 1st time through on communities, type is not set yet
        if (isset($this->target->type) &&
            !(($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter))
          ) {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            $select
                ->where('obj.matrix_counter = ?', $this->_getParam('mid', 0))
                ->where('obj.counter=?', $this->_getParam('id', 0))
                ->where('obj.active=?', 't')
                ->where('obj.status <> ? ', 't')
                ->limit(1);
            
            try {
                $this->_modObject = $this->db->fetchRow($select);
            } catch (Exception $e) { }
        }
    }
    
    
    public function indexAction() {
        if ($this->_getParam('mid', 0) && $this->_getParam('id', 0)) {
            $data = array('status' => ($this->_getParam('status') == 'approve' ? 't' : 'f'));
            
            $where = array();
            $where[] = $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid', 0));
            $where[] = $this->db->quoteInto('counter=?', $this->_getParam('id', 0));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);            
            
            $this->db->update(
                $this->_tableName,
                $data,
                $where
            );
        }
        
        $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid', 0) . '/');
    }
}