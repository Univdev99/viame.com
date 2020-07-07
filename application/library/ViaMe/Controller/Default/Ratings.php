<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Ratings extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    protected $_moduleInMatrix = true;
    protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_INTERACT;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init()
    {
        parent::init();
        
        // 1st time through on communities, type is not set yet
        if (isset($this->target->type) && isset($this->target->currentModule)) {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            $select->where('obj.counter=?', $this->_getParam('id'))
                ->limit(1);
            
            try {
                $this->_modObject = $this->db->fetchRow($select);
            } catch (Exception $e) { }
        }
    }
    
    
    public function __call($method, $args)
    {
        $this->internal->subModule = $this->target->currentModule;
        $this->internal->subModule->_modObject = $this->_modObject;
        
        $this->_helper->viewRenderer->setNoRender();
        
        return $this->_forward($this->getRequest()->getActionName(), $this->getRequest()->getControllerName(), 'system', $this->_getAllParams());
    }
}