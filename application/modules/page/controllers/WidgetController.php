<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Page_WidgetController extends ViaMe_Controller_Default_Widget
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
        if (isset($this->target->currentModule)) { $widget = $this->target->currentModule; }
        else {
            $widget = $this->_getParam('widget');
            if (!$widget && $this->_getParam('mid', 0)) {
                foreach ($this->target->modules as $module) {
                    if ($module->module_id == $this->module_modules[$this->getRequest()->getModuleName()]->id && $module->counter == $this->_getParam('mid', 0)) {
                        $widget = $module;
                        break;
                    }
                }
            }
        }
        if ($widget) {
            if (isset($widget->parameter_values_delimited) && $widget->parameter_values_delimited) {
                foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                    $tokens = preg_split('/:/', $param, 2);
                    if ($tokens[0]) { $dp_params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
                }
                $this->view->dp_params = $dp_params;
            }
            
            $select = $this->_buildComplexQuery($this->_tableName, $widget);
            
            $select = $this->addProfileSymbolClauses($select);
            
            if (isset($dp_params['dp_hide_children']) && $dp_params['dp_hide_children']) {
                $select->where('obj.parent_id ISNULL');
            }
            
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            
            parent::indexAction($select);
        }

        
        parent::indexAction($select);
    }
}
