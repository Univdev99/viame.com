<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_ViewController extends ViaMe_Controller_Default_View
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init($select = null)
    {
        ViaMe_Controller_Action::init();
        
        if (isset($this->target->type) && isset($this->target->currentModule)) {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            
            // Should we show unmoderated messages?
            if (!$this->masked && ($this->internal->target->acl->owner || ($this->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE))) {
                $select->where('obj.status ISNULL OR obj.status <> ?', 'f');
            }
            else {
                $select->where('obj.status=?', 't');
            }
        }
        
        parent::init($select);
    }
}