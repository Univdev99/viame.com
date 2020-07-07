<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Create extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_WRITE;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        // 1st time through on communities, type is not set yet
        if (isset($this->target->type) && isset($this->target->currentModule) && (($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter))) {
            return $this->_denied('Module Error', 'There was an error when requesting that module.');
        }
    }
}