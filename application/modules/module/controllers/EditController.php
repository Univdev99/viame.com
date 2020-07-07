<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_EditController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Edit Module', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        // Check to see if they own the module
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/matrix_form.php';
        // Remove Elements Early to Reduce Iteration
        unset($form_config['elements']['module_id']);
        #unset($form_config['elements']['publicly_searchable']);
            
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('orderby', 'display_orderby', 'display_stack', 'secondary', 'secondary_url', 'widget', 'widget_hide_summary', 'widget_max'), 'options_display', array('legend' => 'Display'));
        $form->addDisplayGroup(array('publicly_searchable'), 'options_search', array('legend' => 'Search'));
        $form->addDisplayGroup(array('interactive', 'moderated'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('module_id', 'display', 'content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Module', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'id', array('required' => true));
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'id', 'vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // Load up the existing modules for display stack
        $display_stack[''] = 'Default';
        $display_stack['none'] = 'Hidden';
        foreach ($this->target->modules as $module) {
            if ($module->module_id == $this->_getParam('mid') && $module->counter == $this->_getParam('id')) { continue; }
            $display_stack[$module->module_id . '-' . $module->counter] = ($module->display ? $module->display : $module->m_display);
        }
        $form->getElement('display_stack')->setMultiOptions($display_stack);
        
        // Optional Parameters
        $temp_params = explode($this->config->delimiter, $this->module_modules[$this->_getParam('mid')]->parameters_delimited);
        $optional_parameters = null;
        if ($optional_parameters = $this->_helper->ViaMeForms->addParameters($this->module_modules[$this->_getParam('mid')]->parameters_delimited)) {
            $form->addElements($optional_parameters);
            $form->getDisplayGroup('main')->setElements(array_merge(
                $form->getDisplayGroup('main')->getElements(),
                $optional_parameters
            ));
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && ((isset($params->override) && $params->override) || $form->isValid($this->_getAllParams()))) {
            $fields = array('orderby' => 'orderby', 'display' => 'display', 'content' => 'content', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'display_orderby' => 'display_orderby', 'display_stack' => 'display_stack', 'secondary' => 'secondary', 'secondary_url' => 'secondary_url', 'widget' => 'widget', 'widget_hide_summary' => 'widget_hide_summary', 'widget_max' => 'widget_max', 'publicly_searchable' => 'publicly_searchable', 'interactive' => 'interactive', 'moderated' => 'moderated', 'status' => 'active', 'show_on_fail' => 'show_on_fail');
            $params = (object) $this->_getAllParams();
            foreach ($fields as $key => $val) {
                if (isset($params->$key)) {
                    if ((isset($params->$key) && $params->$key !== '') || (isset($params->$key) && isset($params->override) && $params->override)) {
                        // Something has to be set
                        if ($params->$key === true) { $data[$val] = $this->db->quote('1'); }
                        elseif ($params->$key === false) { $data[$val] = $this->db->quote('0'); }
                        else { $data[$val] = $this->db->quote($params->$key); }
                    }
                    elseif (!(isset($params->override) && $params->override)) {
                        $data[$val] = 'NULL';
                    }
                }
                elseif (!(isset($params->override) && $params->override)) {
                    $data[$val] = 'NULL';
                }
            }
            /*
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key)) {
                    if ($params->$key) {
                        $data[$key] = $this->db->quote($params->$key);
                    }
                    elseif (!(isset($params->override) && $params->override)) {
                        $data[$key] = 'NULL';
                    }
                }
            }
            */
            
            // Optional Parameters
            $iparams = $this->_helper->ViaMeForms->constructArrayString($temp_params, $form);
            if ($iparams) { $data['parameter_values'] = new Zend_Db_Expr($iparams); }
            elseif (!(isset($params->override) && $params->override))  { $data['parameter_values'] = 'NULL'; }
            
            $update = array();
            foreach ($data as $key => $val) {
                $update[] = "$key=$val";
            }
            
            $where[] = $this->db->quoteInto('module_id = ?', $this->_getParam('mid'));
            $where[] = $this->db->quoteInto('counter = ?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);
            
            if ($data) {
                try {
                    $query = 'UPDATE module_matrix SET ' . implode(', ', $update) . ' WHERE ' . implode(' AND ', $where);
                    $this->db->exec($query);
                    
                    $this->log->ALERT("Module information successfully updated.");
                    
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                } catch (Exception $e) {
                    
                    $this->log->ALERT("Module information update failed!");
                    
                    $this->view->formErrors = array('Module update failed.');
                }
            }
            else {
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
            }
        } elseif (!$this->getRequest()->isPost()) {
            $module_found = false;
            foreach ($this->target->modules as $module) {
                if ($module->module_id == $this->_getParam('mid') && $module->counter == $this->_getParam('id')) {
                    $module_found = true;
                    $form->populate((array) $module);
                    $form->getElement('status')->setValue($module->active);
                    
                    // Optional Parameters
                    if ($module->parameter_values_delimited) {
                        $temp_values = explode($this->config->delimiter, $module->parameter_values_delimited);
                        foreach ($temp_values as $param) {
                            $tokens = preg_split('/:/', $param, 2);
                            $name = (isset($tokens[0]) && $tokens[0] ? $tokens[0] : null);
                            $value = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null);
                            if ($form->getElement($name)) { $form->getElement($name)->setValue($value); }
                        }
                    }
                    
                    break;
                }
            }
            if (!$module_found) {
                // Module wasn't found in the target modules list
                return $this->_denied('Module Error', 'There was an error when requesting that module.');
            }
        }        
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Module' . (isset($module_found) && $module_found && isset($module) && $module->display ? ' (' . $module->display . ')' : '');
        
        $this->renderScript('add/index.phtml');
    }
}