<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Edit extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    protected $_moduleInMatrix = true;
    protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_EDIT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        // 1st time through on communities, type is not set yet
        if (isset($this->target->type) && isset($this->target->currentModule) &&
            !(($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter))
          ) {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            $select
                ->where('obj.' . strtolower($this->target->type) . '_id=?', $this->target->id)
                ->where('obj.matrix_counter = ?', $this->_getParam('mid', 0))
                ->where('obj.counter=?', $this->_getParam('id', 0))
                ->where('obj.active NOTNULL')
                ->limit(1);
            
            try {
                $this->_modObject = $this->db->fetchRow($select);
                
                // Can Edit Objects Created By You Anywhere
                if (isset($this->_modObject->profile_id) && isset($this->member->profile->id) && $this->_modObject->profile_id == $this->member->profile->id) {
                    $this->_modObject->allowed = true;
                    $this->_modObject->privilege = self::ACL_OWNER;
                }
            } catch (Exception $e) { }
        }
    }
}