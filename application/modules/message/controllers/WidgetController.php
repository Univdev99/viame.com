<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_WidgetController extends ViaMe_Controller_Default_Widget
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
    
    
    public function indexAction($select = null)
    {
        /* Need to construct the select here because of the status field */
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $widget = $this->_getParam('widget');
        if (!$widget && $this->_getParam('mid', 0)) {
            foreach ($this->target->modules as $module) {
                if ($module->module_id == $this->module_modules[$this->getRequest()->getModuleName()]->id && $module->counter == $this->_getParam('mid', 0)) {
                    $widget = $module;
                    break;
                }
            }
        }
        
        if ($widget) {
            $select = $this->_buildComplexQuery($this->_tableName, $widget);
            
            $select = $this->addProfileSymbolClauses($select);
            
            $select->where('obj.status=?', 't');
            
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            
            parent::indexAction($select);
        }
        else {
            $this->_helper->viewRenderer->setNoRender();
        }
    }
}