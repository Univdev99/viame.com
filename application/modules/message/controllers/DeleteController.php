<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_DeleteController extends ViaMe_Controller_Default_Delete
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
    
    
    public function preDispatch()
    {
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) { return $this->_denied(); }
    }
}