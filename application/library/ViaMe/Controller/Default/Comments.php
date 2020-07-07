<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Comments extends ViaMe_Controller_Action
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
        
        // Switch ACL
        if ($this->getRequest()->getActionName() == 'moderate' || $this->getRequest()->getActionName() == 'delete') {
            $this->_minPrivilege = self::ACL_MODERATE;
            $this->_defaultAllow = false;
        }
        elseif ($this->getRequest()->getActionName() == 'list' || $this->getRequest()->getActionName() == 'widget') {
            $this->_minPrivilege = self::ACL_READ;
            $this->_memberDefined = false;
            if ($this->getRequest()->getActionName() == 'widget') {
                $this->_modObjectCheck = false;
            }
        }
        
        // 1st time through on communities, type is not set yet
        if (isset($this->target->type) && isset($this->target->currentModule)) {
            if ($this->_getParam('id')) {
                $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
                $select->where('obj.counter=?', $this->_getParam('id'))
                    ->limit(1);
                
                try {
                    $this->_modObject = $this->db->fetchRow($select);
                    
                    // Hack to get the object information into the RSS feeds
                    if ($this->_modObject) {
                        $this->target->currentModule->display = $this->_modObject->title;
                        if ($this->_modObject->meta_description) { $this->target->currentModule->meta_description = $this->_modObject->meta_description; }
                    }
                } catch (Exception $e) { }
            }
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