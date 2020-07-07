<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Portfolio_ViewController extends ViaMe_Controller_Default_View
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
        parent::indexAction();
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'portfolio_view_form',
                'id' => 'portfolio_view_form',
                'class' => 'form',
                'method' => 'get'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'portfolio_value' => array('Text', array(
                    'label' => 'Starting Portfolio Value',
                    'description' => 'Total starting portfolio value',
                    'value' => $this->_getParam('portfolio_value'),
                    'order' => 5
                )),
                'currency_code' => array('Select', array(
                    'label' => 'Currency',
                    'description' => 'Use a differenct currency',
                    'value' => 'USD',
                    'order' => 10
                ))
            )
        ));
        
        #$form->setMethod('get');

        $form->addElement('Submit', 'submit', array('label' => 'Display', 'ignore' => true, 'order' => 999));
        
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
                
        // Need to pull all the positions and any conversion data if available
        $select = $this->db->select()->from(array('obj' => 'portfolio_positions'))
            ->join(array('q' => 'quote_view_symbol_matrix'), 'obj.symbol_id=q.id',
                array(
                    'q_name' => 'name',
                    'q_internal_symbol' => 'internal_symbol'
                )
            )
            ->where('obj.' . strtolower($this->target->type) . '_id=?', $this->target->id)
            ->where('obj.matrix_counter=?', $this->_getParam('mid'))
            ->where('obj.item_counter=?', $this->_getParam('id'))
            ->where('obj.active=?', 't');
        $this->view->positions = $this->db->fetchAll($select);
        
        // Get Total and Quote Data
        $this->view->initial_portfolio_total_value = $this->view->object->cash;
        $symbols = array();
        foreach ($this->view->positions as $position) {
            $symbols[] = $position->q_internal_symbol;
            $this->view->initial_portfolio_total_value += $position->shares * $position->price;
        }
        
        // Currency Conversions
        $this->view->currency_conversion_us_to_default = 1;
        $this->view->currency_conversion_default_to_selected = 1;
        
        if ($this->_getParam('currency_code') && ($this->_getParam('currency_code') != $this->view->object->currency_code)) {
            // Get the conversion to user selected currency
            $symbols[] = $this->view->object->currency_code . $this->_getParam('currency_code') . '=X';
        }
        if ($this->view->object->currency_code != 'USD') {
            // Get the conversion from dollar to default currency
            $symbols[] = 'USD' . $this->view->object->currency_code . '=X';
        }
        
        $quotes = new ViaMe_Vm_Quotes();
        $quote_data = $quotes->fetch($symbols, 'sl1d1c1p2');
        
        // Pop off currency conversions
        if ($this->view->object->currency_code != 'USD') {
            $temp = array_pop($quote_data);
            if ($temp[1] && $temp[1] != 'N/A') {
                $this->view->currency_conversion_us_to_default = $temp[1];
                $form->getElement('currency_code')->setValue($this->view->object->currency_code);
            }
        }
        if ($this->_getParam('currency_code') && ($this->_getParam('currency_code') != $this->view->object->currency_code)) {
            $temp = array_pop($quote_data);
            if ($temp[1] && $temp[1] != 'N/A') {
                $this->view->currency_conversion_default_to_selected = $temp[1];
                $form->getElement('currency_code')->setValue($this->_getParam('currency_code'));
            }
        }
        
        
        $this->view->data = array();
        foreach ($quote_data as $data) {
            $this->view->data[$data[0]] = $data;
        }
        
        // Get Total and Quote Data
        $this->view->current_portfolio_total_value = $this->view->object->cash;
        foreach ($this->view->positions as $position) {
            $this->view->current_portfolio_total_value += ($position->shares * $position->price) +
                ($position->shares * ($this->view->data[$position->q_internal_symbol][1] * $this->view->currency_conversion_us_to_default - $position->price) * $position->position);
        }
        
        // Hypothetical Start Value
        $this->view->initial_portfolio_value = ($this->_getParam('portfolio_value') ? $this->_getParam('portfolio_value') / $this->view->currency_conversion_default_to_selected : $this->view->initial_portfolio_total_value);
        
        $this->view->form = $form;
    }
}