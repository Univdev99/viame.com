<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_AddController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function indexAction()
    {
        $this->view->headTitle('Add Module', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/matrix_form.php';
        // Remove Elements Early to Reduce Iteration
        #unset($form_config['elements']['publicly_searchable']);
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('orderby', 'display_orderby', 'display_stack', 'secondary', 'secondary_url', 'widget', 'widget_hide_summary', 'widget_max'), 'options_display', array('legend' => 'Display'));
        $form->addDisplayGroup(array('publicly_searchable'), 'options_search', array('legend' => 'Search'));
        $form->addDisplayGroup(array('interactive', 'moderated'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('module_id', 'display', 'content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Add Module', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // Load the available modules
        $modules_list = array();
        foreach ($this->module_modules as $module) {
            // Some modules status is false to prevent adding.  Bypassed for administrators.  No need to check in the add as it is no big deal.  Only get to add a broken module.
            if (!$module->status && !(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))) {
                continue;
            }
            if ($module->system) { continue; }
            if ($this->target->type != 'VIA' && $module->profile_only) { continue; }
            
            $modules_list[$module->id] = $module->display;
        }
        asort($modules_list);
        $form->getElement('module_id')->setMultiOptions($modules_list);
        
        // Load up the existing modules for display stack
        $display_stack[''] = 'Default';
        $display_stack['none'] = 'Hidden';
        foreach ($this->target->modules as $module) {
            $display_stack[$module->module_id . '-' . $module->counter] = ($module->display ? $module->display : $module->m_display);
        }
        $form->getElement('display_stack')->setMultiOptions($display_stack);
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('orderby' => 'orderby', 'module_id' => 'module_id', 'display' => 'display', 'content' => 'content', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'display_orderby' => 'display_orderby', 'display_stack' => 'display_stack', 'secondary' => 'secondary', 'secondary_url' => 'secondary_url', 'widget' => 'widget', 'widget_hide_summary' => 'widget_hide_summary', 'widget_max' => 'widget_max', 'publicly_searchable' => 'publicly_searchable', 'interactive' => 'interactive', 'moderated' => 'moderated', 'status' => 'active', 'show_on_fail' => 'show_on_fail');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
            }
            
            
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;
            $data['profile_id'] = $this->member->profile->id;
            
            try {
                $module_matrix = new module_models_matrix();
                $module_matrix->insert($data);
                
                $this->log->ALERT('New content module successfully added.');
                
                if ($this->_getParam('saveedit') || $this->_getParam('next_action') == 'publish') {
                    // Get insert id and redirect to the edit page with the redirect parameter
                    $last_id = $this->db->fetchOne('SELECT MAX(counter) FROM module_matrix WHERE ' . strtolower($this->target->type) . '_id' . '=? AND module_id=?', array($this->target->id, $data['module_id']));
                }
                
                if ($this->_getParam('saveedit') && $last_id) {
                    $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/edit/p/mid/' . $this->_getParam('module_id') . '/' . "id/$last_id/?redirect=" . (urlencode($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/' . $this->getRequest()->getModuleName())) );
                }
                elseif ($this->_getParam('next_action') == 'publish' && $last_id) {
                    $this->_redirect('/' . strtolower($this->target->type) . '/' . $this->target->id . '/' . $this->module_modules[$this->_getParam('module_id')]->name . '/create/p/mid/' . $last_id . '/' . '?redirect=' . ($this->_getParam('redirect') ? preg_replace('/&/', '%26', $this->_getParam('redirect')) : '') . ($this->_getParam('extra_params') ? '&' . $this->_getParam('extra_params') : ''));
                }
                else {
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName());
                }
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That module was not successfully added.');
                
                #$profiler = $this->db->getProfiler();
                #$query = $profiler->getLastQueryProfile();
                #echo $query->getQuery();
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Add A New Module';
    }
}
