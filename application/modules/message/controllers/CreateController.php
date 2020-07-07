<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_CreateController extends ViaMe_Controller_Default_Create
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_INTERACT;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'message_messages';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Post Message', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        
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
        if ($this->community->meta_stocks) {
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
            $fields = array('title' => 'title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'content' => 'content', 'show_on_fail' => 'show_on_fail', 'mid' => 'matrix_counter');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
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
            // Expiration Field
            if ($this->_getParam('expiration')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('expiration'));
                $data['expiration'] = $date->get(Zend_Date::ISO_8601);
            }
            */
            
            // Parent Counter?
            if ($this->_getParam('pid')) {
                $data['parent_counter'] = $this->_getParam('pid');
            }
            
            // Moderated?
            if ((!$this->internal->target->currentModule->moderated) || $this->internal->target->acl->owner || ($this->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE)) {
                $data['status'] = 't';
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            try {
                $message_messages = new message_models_messages();
                $message_messages->insert($data);
                
                $this->log->ERR('_messagePublished');
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That message was not successfully posted.');
            }
        }
        else {
            $form->populate($_GET);

            // Load the subject if pid supplied
            if ($form->getElement('pid')->getValue() && !$this->_getParam('title')) {
                $title = $this->db->fetchOne("SELECT title FROM message_messages WHERE " . strtolower($this->target->type) . "_id=? AND matrix_counter=? AND counter=?", array(
                    $this->target->id, $this->_getParam('mid'), $form->getElement('pid')->getValue()
                ));
                
                if ($title) {
                    if (!preg_match('/Re:/i', $title)) { $title = "Re: $title"; }
                    $form->getElement('title')->setValue($title);
                }
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Post A New Message' . ($this->target->currentModule->display ? ' (' . $this->target->currentModule->display . ')' : '');
    }
}