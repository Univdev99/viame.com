<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_EditController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Edit Widget', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        // Check to see if they own the widget
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/matrix_form.php';
        // Remove Elements Early to Reduce Iteration
        unset($form_config['elements']['widget_id']);
        #unset($form_config['elements']['publicly_searchable']);
            
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        
        $form->addDisplayGroup(array('orderby', 'display_url', 'secondary', 'secondary_url', 'widget', 'display_cm'), 'options_display', array('legend' => 'Display'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        
        $form->addDisplayGroup(array('widget_id', 'display'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Widget', 'ignore' => true, 'order' => 998));
        $form->addElement('Submit', 'saveedit', array('label' => 'Save and Continue Editing', 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'saveedit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'wid', array('required' => true));
        $form->addElement('Hidden', 'id', array('required' => true));
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('wid', 'id', 'vmpd_nar', 'redirect'), 'hidden');

        // Not ready or admin only widgets
        if ( $this->widget_widgets[$this->_getParam('wid')]->admin_only && !(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) ) {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Widget Edit Failed',
                'bd' => '<p class="error">An error has occurred. You are not allowed to make any modifications to this widget.</p><p>Please contact the appropriate party to make these changes.  Please hit the <a href="javascript:history.back();">back button</a> on your browser to continue.</p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
            
        // Optional Parameters
        $temp_params = explode($this->config->delimiter, $this->widget_widgets[$this->_getParam('wid')]->parameters_delimited);
        $optional_parameters = null;
        if ($optional_parameters = $this->_helper->ViaMeForms->addParameters($this->widget_widgets[$this->_getParam('wid')]->parameters_delimited)) {
            $form->addElements($optional_parameters);
            $form->getDisplayGroup('main')->setElements(array_merge(
                $form->getDisplayGroup('main')->getElements(),
                $optional_parameters
            ));
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && (($this->_getParam('override')) || $form->isValid($this->_getAllParams()))) {
            $fields = array('orderby' => 'orderby', 'display' => 'display', 'display_url' => 'display_url', 'secondary' => 'secondary', 'secondary_url' => 'secondary_url', 'widget' => 'widget', 'display_cm' => 'display_cm', 'status' => 'active');
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
            
            // Optional Parameters
            $iparams = $this->_helper->ViaMeForms->constructArrayString($temp_params, $form);
            if ($iparams) { $data['parameter_values'] = new Zend_Db_Expr($iparams); }
            elseif (!(isset($params->override) && $params->override)) { $data['parameter_values'] = 'NULL'; }
            
            $update = array();
            foreach ($data as $key => $val) {
                $update[] = "$key=$val";
            }
                
            $where[] = $this->db->quoteInto('widget_id = ?', $this->_getParam('wid'));
            $where[] = $this->db->quoteInto('counter = ?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);
            
            if ($data) {
                try {
                    $query = 'UPDATE widget_matrix SET ' . implode(', ', $update) . ' WHERE ' . implode(' AND ', $where);
                    #Zend_Debug::Dump($query);
                    $this->db->exec($query);
                    
                    $this->log->ALERT("Widget information successfully updated.");
                    
                    if ($this->_getParam('saveedit')) {
                        $this->view->formErrors = array('Widget Saved.');
                    }
                    else {
                        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                    }
                } catch (Exception $e) {
                    $this->log->ALERT("Widget information update failed!");
                    
                    $this->view->formErrors = array('Widget update failed.');
                }
            }
            else {
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
            }
        } elseif (!$this->getRequest()->isPost()) {
            $widget_found = false;
            foreach ($this->target->widgets as $widget) {
                if ($widget->widget_id == $this->_getParam('wid') && $widget->counter == $this->_getParam('id')) {
                    $widget_found = true;
                    $form->populate((array) $widget);
                    $form->getElement('status')->setValue($widget->active);
                    
                    // Optional Parameters
                    if ($widget->parameter_values_delimited) {
                        $temp_values = explode($this->config->delimiter, $widget->parameter_values_delimited);
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
            if (!$widget_found) {
                // Module wasn't found in the target modules list
                return $this->_denied('Widget Error', 'There was an error when requesting that widget.');
            }
        }        
        
        // Code Based Adjust
        if ($this->widget_widgets[$this->_getParam('wid')]->name == 'code' && $form->getElement('dp_language') && $form->getElement('dp_code')) {
        	// Adjust to map to codemirror modes
    		$form->getElement('dp_code')->setAttrib('langmode', $form->getElement('dp_language')->getValue());
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Widget' . (isset($widget_found) && $widget_found && isset($widget) && $widget->display ? ' (' . $widget->display . ')' : '');
        
        $this->renderScript('add/index.phtml');
    }
}