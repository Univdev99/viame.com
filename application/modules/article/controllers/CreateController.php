<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

require_once dirname(__FILE__) . '/../../../library/Other/pubsubhubbub/library/Publisher.php';
use pubsubhubbub\publisher\Publisher;

class Article_CreateController extends ViaMe_Controller_Default_Create
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'article_articles';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Create Article', 'PREPEND');
        
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
        require dirname(dirname(__FILE__)) . '/models/articles_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('heading', 'summary', 'more_link', 'widget_image_url'), 'options_content', array('legend' => 'Content'));
        if ($this->community->meta_stocks) {
            $form->addElement('Text', 'symbols', array('label' => 'Symbols', 'description' => 'Comma Seperated (10 Symbols Max)', 'class' => 'vmfh_acss multiple', 'order' => 100));
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords', 'symbols'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        else {
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        #$form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('title', 'content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Publish Article', 'order' => 996));
        $form->addElement('Submit', 'save', array('label' => 'Save Article', 'ignore' => true, 'order' => 997));
        $form->addElement('Submit', 'saveedit', array('label' => 'Save and Continue Editing', 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('article_form').vivregval_canceled = true;"));
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
            $fields = array('title' => 'title', 'heading' => 'heading', 'summary' => 'summary', 'more_link' => 'more_link', 'widget_image_url' => 'widget_image_url', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'content' => 'content', 'show_on_fail' => 'show_on_fail', 'mid' => 'matrix_counter');
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
            
            // Status Field
            if (!$this->_getParam('submit')) {
                $data['active'] = 'f';
            }
            else {
                $data['published'] = new Zend_Db_Expr('now()');
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            try {
                $article_articles = new article_models_articles();
                $article_articles->insert($data);
                
                // Get insert id for redirect to the edit page with the redirect parameter
                $last_id = $this->db->fetchOne('SELECT MAX(counter) FROM ' . $this->_tableName . ' WHERE ' . strtolower($this->target->type) . '_id' . '=? AND matrix_counter=? AND profile_id=?', array($this->target->id, $data['matrix_counter'], $data['profile_id']));
                
                if (isset($data['active']) && $data['active'] == 'f') {
                    $this->log->ERR('_articleSaved', array('item_counter' => $last_id));
                    #$this->VMAH->log('log message', Zend_Log::EMERG, array('test1', 'test2', 'test3'));
                }
                else {
                    $this->log->ERR('_articlePublished', array('item_counter' => $last_id));
                    
                    # Fat Ping Using PubHubSubbub
                    try {
                        $topic =  'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->community->display ? $this->community->display : $this->community->name) . '/s/system/widget/p/format/atom/no_cache/1/';
                        foreach (array('https://pubsubhubbub.appspot.com', 'https://pubsubhubbub.superfeedr.com') as $hub) {
                            $p = new Publisher($hub);
                            $p->publish_update($topic);
                        }
                    } catch (Exception $e) {}
                }
                
                
                if ($this->_getParam('saveedit') && $last_id) {
                    $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/edit/p/mid/' . $this->_getParam('mid') . '/' . "id/$last_id/?redirect=" . (urlencode($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/')) );
                }
                else {
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                }
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That article was not successfully created.');
            }
        }
        else {
            $form->populate($_GET);
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Create A New Article' . ($this->target->currentModule->display ? ' (' . $this->target->currentModule->display . ')' : '');
    }
}