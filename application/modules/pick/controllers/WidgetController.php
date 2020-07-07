<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_WidgetController extends ViaMe_Controller_Default_Widget
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
            
            /*
            $select->join(array('qx' => 'quote_view_symbol_matrix'), 'obj.symbol_id=qx.id',
                array(
                    'qx_symbol' => 'symbol',
                    'qx_name' => 'name',
                    'qx_type' => 'type',
                    'qx_typedisp' => 'typedisp',
                    'qx_typedisps' => 'typedisps',
                    'qx_exch' => 'exch',
                    'qx_exchdisp' => 'exchdisp',
                    'qx_internal_symbol' => 'internal_symbol',
                    'qx_delayed_symbol' => 'delayed_symbol',
                    'qx_realtime_symbol' => 'realtime_symbol',
                    'qx_seconds_since_data_updated' => 'seconds_since_data_updated'
                )
            );
            */
            
            $select = $this->addProfileSymbolClauses($select);
            
            $select->where('partial_close_parent_id ISNULL');
            
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
            
            // Set the following to tell the base class to return... We have more to do and will
            //   check for displayInCm at the end here...
            $this->setParam('delayDisplayInCmCheck', 1);
            parent::indexAction($select);
            
            if (isset($this->view->objects) && count($this->view->objects)) {
                $symbols = $wheres = $ids = array();
                
                foreach ($this->view->objects as $pick) {
                    $where[] = $this->db->quoteInto('id=?', $pick->symbol_id);
                    $ids[] = $pick->symbol_id;
                }
                
                $this->view->symbols_data = $this->view->Quote_ConvertIdToSymbol( $ids );
                
                foreach ($this->view->symbols_data as $temp) {
                    if ($temp->seconds_since_data_updated > $this->config->quote->data_refresh) {
                        $symbols[] = $temp->delayed_symbol;
                    }
                }
                
                if (count($symbols)) {
                    $quotes = new ViaMe_Vm_Quotes();
                    $temp_data = $quotes->fetch($symbols, 'sl1ba');
                }
                
                # Load Data From Database
                $this->view->quote_data = array();

                $query = "SELECT * FROM quote_view_data WHERE " . implode(' OR ', $where);
                
                foreach ($this->db->fetchAll($query) as $temp) {
                    $this->view->quote_data[$temp->symbol_id] = $temp;
                }
            }
            
            if ($this->_getParam('displayInCm') && $widget) { return $this->displayInCm($widget); }
        }
        else {
            $this->_helper->viewRenderer->setNoRender();
        }
    }
}