<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_TestController extends ViaMe_Controller_Default_Index
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
    
    
    public function indexAction($select = null)
    {
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'pick_index_form',
                'method' => 'get',
                'id' => 'pick_index_form',
                'class' => 'form'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'simulator' => array('Select', array(
                    'label' => 'Simulation Style',
                    #'description' => 'Simulation style to display picks',
                    #'value' => $this->_getParam('simulator'),
                    'multioptions' => array(
                        'default' => 'Even Dollar Investing'
                    ),
                    'order' => 5
                )),
                'start_value' => array('Text', array(
                    'label' => 'Enter Amount',
                    #'description' => 'Starting value',
                    #'value' => $this->_getParam('start_value'),
                    'validators' => array(
                        array('Float')
                    ),
                    'order' => 10
                )),
                'currency_code' => array('Select', array(
                    'label' => 'Currency',
                    #'description' => 'Use a differenct currency',
                    'value' => 'USD',
                    'order' => 15
                )),
                'multiplier' => array('Text', array(
                    'label' => 'Multiplier',
                    #'description' => 'Multiplier',
                    #'value' => $this->_getParam('multiplier'),
                    'validators' => array(
                        array('Float')
                    ),
                    'order' => 20
                )),
                'fractional' => array('Checkbox', array(
                    'label' => 'Fractional Shares',
                    #'description' => 'Allow fractional shares',
                    #'value' => $this->_getParam('fractional'),
                    'order' => 25
                )),
                'status' => array('Select', array(
                    'label' => 'Pick Status',
                    #'description' => 'Select whether to view open or closed picks.',
                    'value' => $this->_getParam('status'),
                    'multioptions' => array(
                        '0' => 'Open',
                        '-1' => 'Closed',
                        '1' => 'All'
                    ),
                    'order' => 30
                )),
                'start_date' => array('Text', array(
                    'label' => 'Start Date',
                    #'description' => 'Start Date',
                    'class' => 'vmfh_date',
                    #'value' => $this->_getParam('start_date'),
                    'validators' => array(
                        array('Date')
                    ),
                    'order' => 35
                )),
                'end_date' => array('Text', array(
                    'label' => 'End Date',
                    #'description' => 'End Date',
                    'class' => 'vmfh_date',
                    #'value' => $this->_getParam('end_date'),
                    'validators' => array(
                        array('Date')
                    ),
                    'order' => 40
                )),
                #'limit' => array('Text', array(
                #    'label' => 'Max # Picks',
                #    'description' => 'How many picks to display (max. 1000)',
                #    'value' => $this->_getParam('limit'),
                #    'validators' => array(
                #        'Int'
                #    ),
                #    'order' => 45
                #))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Simulate', 'ignore' => true, 'order' => 999));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');
        
        // Load Currency Choices - Sync this code up with the edit controller as well as the member controllers
        // Load Up Currencies
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT currency, code FROM system_currencies WHERE active='t' ORDER BY orderby, currency") as $temp) {
            $multioptions[$temp->code] = $temp->currency;
        }
        // Translation
        // Translation of Currencies TAKING TOO LONG!
        if (0) {
        $multioptions_nt = $multioptions;
        foreach ($multioptions as $key => $val) {
            $key = strtoupper($key);
            $ZC = new ViaMe_Currency($key, (isset($this->vars->language) && isset($language_to_locale[$this->vars->language]) ? $language_to_locale[$this->vars->language] : null));
            try {
                $output = $ZC->getName();
                if (is_string($output) && $val != $output) {
                    $multioptions[$key] = "$val ($output)";
                }
            } catch (Exception $e) {}
        }
        }
        $form->getElement('currency_code')->setMultiOptions($multioptions);
        
        $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
        
        
        // Join the Symbol
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
        
        // Status
        if ($this->_getParam('status') == -1) {
            // Closed
            $select->where('obj.close_datestamp NOTNULL');
        }
        elseif ($this->_getParam('status') == 0) {
            // Open
            $select->where('obj.close_datestamp ISNULL');
        }
        // Date Range
        if ($this->_getParam('start_date')) {
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            $date->set($this->_getParam('start_date'));
            
            $select->where('date(obj.open_datestamp) >= ?', $date->get(Zend_Date::ISO_8601));
        }
        if ($this->_getParam('end_date')) {
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            $date->set($this->_getParam('end_date'));
            
            $select->where('date(obj.open_datestamp) <= ?', $date->get(Zend_Date::ISO_8601));
        }
        // Limit
        if ($this->_getParam('limit')) {
            $select->limit(($this->_getParam('limit') && $this->_getParam('limit') <= 500) ? $this->_getParam('limit') : 500);
        }
        // Order
        $start_dir = 'ASC';
        if (!is_null($this->internal->target->currentModule->allow_community_inflow) ||
            !is_null($this->internal->target->currentModule->allow_network_inflow) ||
            !is_null($this->internal->target->currentModule->allow_profile_inflow)) {
            $start_dir = 'DESC';
        }
        $select->order(array("obj.open_datestamp $start_dir", 'obj.counter ASC'));
        
        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $paginator->setItemCountPerPage(($this->_getParam('limit') && $this->_getParam('limit') <= 500) ? $this->_getParam('limit') : 500);
        $paginator->setPageRange(11);

        $this->view->paginator = $paginator;
        if (($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter)) {
            $this->view->masked = $this->target->currentModule->counter;
        }
        
        if (count($this->view->paginator)) {
            $symbols = $wheres = $ids = array();
            
            foreach ($this->view->paginator as $pick) {
                $where[] = $this->db->quoteInto('id=?', $pick->symbol_id);
                $ids[] = $pick->symbol_id;
            }
            
            $this->view->symbols_data = $this->view->Quote_ConvertIdToSymbol( $ids );
            
            foreach ($this->view->symbols_data as $temp) {
                if ($temp->seconds_since_data_updated > $this->config->quote->data_refresh) {
                    $symbols[] = $temp->delayed_symbol;
                }
            }
            
            // Currency Conversions
            $this->view->currency_conversion = 1;
            
            if ($this->_getParam('currency_code') && $this->_getParam('currency_code') != 'USD') {
                // Get the conversion from dollar to default currency
                $symbols[] = 'USD' . $this->_getParam('currency_code') . '=X';
            }
            
            if (count($symbols)) {
                $quotes = new ViaMe_Vm_Quotes();
                $quote_data = $quotes->fetch($symbols, 'sl1ba');
                
                // Pop off currency conversions
                if ($this->_getParam('currency_code') && $this->_getParam('currency_code') != 'USD') {
                    $temp = array_pop($quote_data);
                    if ($temp[1] && $temp[1] != 'N/A') {
                        $this->view->currency_conversion = $temp[1];
                        $form->getElement('currency_code')->setValue($this->_getParam('currency_code'));
                    }
                }
            }
            
            # Load Data From Database
            $this->view->quote_data = array();

            $query = "SELECT * FROM quote_view_data WHERE " . implode(' OR ', $where);
            
            foreach ($this->db->fetchAll($query) as $temp) {
                $this->view->quote_data[$temp->symbol_id] = $temp;
            }
        }
        
        #if ($this->getRequest()->isPost()) {
            $form->isValidPartial($this->_getAllParams());
        #}
        
        $this->view->form = $form;
        
        $view = 'index';
        if ($this->_getParam('simulator') && $this->_getParam('simulator') != 'default') {
            $view = $this->_getParam('simulator');
        }
        $this->render($view);
    }
}