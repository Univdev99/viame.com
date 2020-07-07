<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_EditController extends ViaMe_Controller_Default_Edit
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'message_messages';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit Message', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/messages_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);

        #$form->addDisplayGroup(array('heading', 'summary', 'more_link'), 'options_content', array('legend' => 'Content'));
        if ($this->community->meta_stocks || $this->_modObject->symbols) {
            $form->addElement('Text', 'symbols', array('label' => 'Symbols', 'description' => 'Comma Seperated (10 Symbols Max)', 'class' => 'vmfh_acss multiple', 'order' => 100));
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords', 'symbols'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        else {
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        #$form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        #$form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        #$form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('title', 'content'), 'main');
        
        $form->addElement('Submit', 'submit', array('label' => 'Post Message', 'order' => 996));
        #$form->addElement('Submit', 'save', array('label' => 'Save Article', 'ignore' => true, 'order' => 997));
        #$form->addElement('Submit', 'saveedit', array('label' => 'Save and Continue Editing', 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('message_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');

        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'pid', array('value' => $this->_getParam('pid'), 'filters' => array('Int')));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'pid', 'vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('title', 'meta_description', 'meta_keywords', 'content', 'show_on_fail');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            
            // Symbols
            if ($this->_getParam('symbols')) {
                $quotes = new ViaMe_Vm_Quotes();
                $temp_symbs = preg_split('/[,\s]+/', $this->_getParam('symbols'));
                $temp_symbs = array_splice($temp_symbs, 0, 10);
                $symbols = array();
                foreach ($quotes->verify($temp_symbs) as $symbol) {
                    $symbols[] = $symbol->id;
                }
                $data['symbols'] = $this->VMAH->iPgArray($symbols);
            }
            else {
                $data['symbols'] = null;
            }
            
            /*
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['activation'] = null;
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
            else {
                $data['expiration'] = null;
            }
            */
            
            // Modified
            $data['modified'] = new Zend_Db_Expr("(CASE WHEN published ISNULL OR (modified ISNULL AND activation > published) THEN null ELSE 'now' END)::timestamp");
            
            $message_messages = new message_models_messages();
            
            $where = array();
            $where[] = $message_messages->getAdapter()->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $message_messages->getAdapter()->quoteInto('counter=?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);   
            
            try {
                $message_messages->update(
                    $data,
                    $where
                );
                
                $this->log->ERR('_messageEdited');
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That message was not successfully modified.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->_modObject);
            
            /*
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($this->_modObject->activation)) {
                $date->set($this->_modObject->activation, Zend_Date::ISO_8601);
                $form->getElement('activation')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            if (isset($this->_modObject->expiration)) {
                $date->set($this->_modObject->expiration, Zend_Date::ISO_8601);
                $form->getElement('expiration')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            */
            // Set the symbols
            if ($this->_modObject->symbols) {
                $quotes = new ViaMe_Vm_Quotes();
                $data = $quotes->lookupById($this->VMAH->ePgArray($this->_modObject->symbols));
                
                foreach ($data as $symbol) {
                    $loader[] = $symbol->internal_symbol;
                }
                
                $form->getElement('symbols')->setValue(implode(', ', $loader));
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Message' . ($this->_modObject->title ? ' (' . $this->_modObject->title . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}