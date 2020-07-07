<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Blog_EditController extends ViaMe_Controller_Default_Edit
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'blog_blogs';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit Blog Post', 'PREPEND');
        
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
        require dirname(dirname(__FILE__)) . '/models/blogs_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);

        $form->addDisplayGroup(array('heading', 'summary', 'more_link'), 'options_content', array('legend' => 'Content'));
        if ($this->community->meta_stocks || $this->_modObject->symbols) {
            $form->addElement('Text', 'symbols', array('label' => 'Symbols', 'description' => 'Comma Seperated (10 Symbols Max)', 'class' => 'vmfh_acss multiple', 'order' => 100));
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords', 'symbols'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        else {
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('title', 'content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Publish Blog Post', 'order' => 996));
        $form->addElement('Submit', 'save', array('label' => 'Save Blog Post', 'ignore' => true, 'order' => 997));
        $form->addElement('Submit', 'saveedit', array('label' => 'Save and Continue Editing', 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('blog_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'save', 'saveedit', 'reset', 'cancel'), 'buttons');
        
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
            $fields = array('title' => 'title', 'heading' => 'heading', 'summary' => 'summary', 'more_link' => 'more_link', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'content' => 'content', 'status' => 'active', 'show_on_fail' => 'show_on_fail');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
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
            
            // Modified
            $data['modified'] = new Zend_Db_Expr("(CASE WHEN published ISNULL OR (modified ISNULL AND activation > published) THEN null ELSE 'now' END)::timestamp");
            
            // Status Field
            if ($this->_getParam('submit') || $this->_getParam('status')) {
                $data['active'] = 't';
                $data['published'] = new Zend_Db_Expr("COALESCE(published, 'now')");
            }
            
            $blog_blogs = new blog_models_blogs();
            
            $where = array();
            $where[] = $blog_blogs->getAdapter()->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $blog_blogs->getAdapter()->quoteInto('counter=?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);   
            
            try {
                $blog_blogs->update(
                    $data,
                    $where
                );
                
                $this->log->ERR('_blogEdited', array('item_counter' => $this->_getParam('id')));
                
                if ($this->_getParam('saveedit')) {
                    $this->view->formErrors = array('Blog Post Saved.');
                }
                else {
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                }
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That blog post was not successfully editted.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->_modObject);
            $form->getElement('status')->setValue($this->_modObject->active);
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
        $this->view->page_title = 'Edit Blog Post' . ($this->_modObject->title ? ' (' . $this->_modObject->title . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}
