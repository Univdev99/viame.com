<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Page_IndexController extends ViaMe_Controller_Default_Index
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    // Change the default limit to 100 for this module  That's it...
    public function indexAction($select = null)
    {
        if (isset($this->target->currentModule->parameter_values_delimited) && $this->target->currentModule->parameter_values_delimited) {
            foreach (@explode($this->config->delimiter, $this->target->currentModule->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $dp_params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            $this->view->dp_params = $dp_params;
        }
        
        // Auto-Go
        if (isset($dp_params['dp_autogo_counter']) && $dp_params['dp_autogo_counter']) {
            return $this->_forward('index', 'view', 'page', array('id' => $dp_params['dp_autogo_counter']));
        }
        
        if (isset($this->_allModuleSelect) && $this->_allModuleSelect) {
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $this->_allModuleSelect->__toString() . ')')), array('*'))
            ;
            
            $this->resetMID();
        }
        else {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            
            $select = $this->addProfileSymbolClauses($select);
            
            // Wrap the select
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
        }
        
        if (isset($dp_params['dp_hide_children']) && $dp_params['dp_hide_children']) {
            $select->where('parent_id ISNULL');
        }
            
        $this->_setParam('limit', $this->_getParam('limit', 50));
        
        parent::indexAction($select);
    }
}