<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_ViewController extends ViaMe_Controller_Default_View
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
    
    
    public function indexAction()
    {
        parent::indexAction(); // Only set Meta.  No paginator.
        
        // Get the symbol
        $this->view->symbol = $this->db->fetchRow("SELECT * FROM quote_view_symbol_matrix WHERE id=?", $this->view->object->symbol_id);
        
        if (!$this->view->object->close_datestamp) {
            if ($this->view->symbol->seconds_since_data_updated > $this->config->quote->data_refresh) {
                $quotes = new ViaMe_Vm_Quotes();
                $temp_data = $quotes->fetch($this->view->symbol->delayed_symbol, 'sl1ba');
            }
            
            $this->view->quote_data = $this->db->fetchRow("SELECT * FROM quote_view_data WHERE symbol_id=?", $this->view->object->symbol_id);
        }
    }
}