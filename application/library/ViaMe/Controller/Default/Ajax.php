<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Ajax extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true; // Not for widgets
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $this->_helper->viewRenderer->setNoRender(); // No View Scripts - Render out of the controller
        $this->_helper->layout->disableLayout();
    }
}