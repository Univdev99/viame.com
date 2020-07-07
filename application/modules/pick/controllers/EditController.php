<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_EditController extends ViaMe_Controller_Default_Edit
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
        $this->view->headTitle('Edit Pick', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) ||
            ($this->_modObject->close_datestamp) ||
            ($this->_modObject->close_temp_price) ||
            ($this->_modObject->close_price)) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/picks_form.php';
        $form_config['elements']['title'][1]['class'] = 'regula-validation';
        $form_config['elements']['title'][1]['data-constraints'] = '@Required(label="title", message="The {label} field cannot be empty.", groups=[pick_form])';
        unset($form_config['elements']['symbol']);
        unset($form_config['elements']['position']);
        unset($form_config['elements']['allocation']);
        
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('heading', 'summary', 'more_link'), 'options_content', array('legend' => 'Content'));
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        #$form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Pick', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('pick_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');
        
        // Hidden Elements
        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_nar', 'redirect'), 'hidden');

        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array(
                'title' => 'title', 'heading' => 'heading', 'summary' => 'summary', 'more_link' => 'more_link', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'content' => 'content', 'risk' => 'risk', 'target_price' => 'target_price', 'timeframe' => 'timeframe', 'suggested_stop_loss' => 'suggested_stop_loss', 'trailing_stop_loss' => 'trailing_stop_loss', 'trailing_stop_loss_type' => 'trailing_stop_loss_type', 'live_stop_loss' => 'live_stop_loss', 'notes' => 'notes', 'holding' => 'holding', 'disclosure' => 'disclosure', 'show_on_fail' => 'show_on_fail'
            );
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
                else {
                    $data[$val] = null;
                }
            }
            
            // Target Date
            if ($this->_getParam('target_date')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('target_date'));
                $data['target_date'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['target_date'] = null;
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
            
            // Live Stop Loss Activation
            if ($this->_getParam('live_stop_loss') && $this->_getParam('trailing_stop_loss') > 0 && $this->_getParam('trailing_stop_loss_type') >= 0) {
                $data['trailing_stop_loss_activation'] = new Zend_Db_Expr('CASE WHEN (trailing_stop_loss NOTNULL AND trailing_stop_loss_type NOTNULL AND ' . $this->db->quoteInto('trailing_stop_loss=?', $this->_getParam('trailing_stop_loss')) . ' AND ' . $this->db->quoteInto('trailing_stop_loss_type=?', $this->_getParam('trailing_stop_loss_type')) . ') THEN trailing_stop_loss_activation ELSE null END');
            }
            else {
                $data['trailing_stop_loss_activation'] = null;
            }
                    
            // Modified
            $data['modified'] = new Zend_Db_Expr("(CASE WHEN published ISNULL OR (modified ISNULL AND activation > published) THEN null ELSE 'now' END)::timestamp");
            
            $pick_picks = new pick_models_picks();
            
            $where = array();
            $where[] = $pick_picks->getAdapter()->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $pick_picks->getAdapter()->quoteInto('counter=?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);   
            
            try {
                $pick_picks->update(
                    $data,
                    $where
                );
                
                $this->log->ERR('_pickEdited');
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That pick was not successfully editted.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->_modObject);
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($this->_modObject->target_date)) {
                $date->set($this->_modObject->target_date, Zend_Date::ISO_8601);
                $form->getElement('target_date')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'))));
            }
            /*
            if (isset($this->_modObject->activation)) {
                $date->set($this->_modObject->activation, Zend_Date::ISO_8601);
                $form->getElement('activation')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            if (isset($this->_modObject->expiration)) {
                $date->set($this->_modObject->expiration, Zend_Date::ISO_8601);
                $form->getElement('expiration')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            */
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Pick' . ($this->_modObject->title ? ' (' . $this->_modObject->title . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}