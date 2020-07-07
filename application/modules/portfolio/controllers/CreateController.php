<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Portfolio_CreateController extends ViaMe_Controller_Default_Create
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
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/portfolios_form.php';
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        
        $form->setMethod('post');

        // Matrix Counter
        $form->addElement('Hidden', 'mid', array('required' => true));
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Portfolio', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        
        #$form->addDisplayGroup(array('first_name', 'middle_name', 'last_name', 'gender', 'dob', 'postal_code'), 'personal', array('legend' => 'Personal Information'));
        #$form->addDisplayGroup(array('email', 'password', 'password_confirm'), 'login', array('legend' => 'Login Information'));
        #$form->addDisplayGroup(array('timezone', 'country', 'language'), 'locale', array('legend' => 'Locale Settings'));
        #$form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'control', array('legend' => 'Save / Reset / Cancel'));
        $form->addDisplayGroup(array('mid', 'vmpd_nar', 'redirect'), 'hidden', array('legend' => 'Hidden Fields', 'style' => 'display: none;'));
        
        
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
        $form->getElement('currency_code')->setValue($this->member->currency);
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('title' => 'title', 'heading' => 'heading', 'summary' => 'summary', 'more_link' => 'more_link', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'currency_code' => 'currency_code', 'show_on_fail' => 'show_on_fail', 'mid' => 'matrix_counter');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
            }
            
            // Cash Field
            if ($this->_getParam('cash')) {
                $data['cash'] = Zend_Locale_Format::getNumber($this->_getParam('cash'));
            }
            
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
            }
            // Expiration Field
            if ($this->_getParam('expiration')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('expiration'));
                $data['expiration'] = $date->get(Zend_Date::ISO_8601);
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            try {
                $portfolio_portfolios = new portfolio_models_portfolios();
                $portfolio_portfolios->insert($data);
                
                $this->log->INFO('New portfolio created.');
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That portfolio was not successfully created.');
            }
        }
        else {
            $form->populate($_GET);
        }
        
        $this->view->form = $form;
    }
}