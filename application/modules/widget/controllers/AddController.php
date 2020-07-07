<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_AddController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Add Widget', 'PREPEND');
        
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
        $form->setOptions($form_config);
        
        
        $form->addDisplayGroup(array('orderby', 'display_url', 'secondary', 'secondary_url', 'widget', 'display_cm'), 'options_display', array('legend' => 'Display'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        
        $form->addDisplayGroup(array('widget_id', 'display'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Add Widget', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        
        // Load the available widgets
        #Zend_Debug::Dump($this->widget_widgets);
        $widgets_list = array();
        foreach ($this->widget_widgets as $widget) {
            // Some widgets status is false to prevent adding.  Bypassed for administrators.  No need to check in the add as it is no big deal.  Only get to add a broken widget.
            if ((!$widget->status || $widget->admin_only) && !(isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))) {
                continue;
            }
            if (!$widget->allow_multiple) {
                $widget_exists = false;
                foreach ($this->target->widgets as $target_widget) {
                    if ($target_widget->widget_id == $widget->id) {
                        $widget_exists = true;
                        break;
                    }
                }
                if ($widget_exists) { continue; }
            }
            $widgets_list[$widget->id] = $widget->display;
        }
        asort($widgets_list);
        $form->getElement('widget_id')->setMultiOptions($widgets_list);

        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('orderby' => 'orderby', 'widget_id' => 'widget_id', 'display' => 'display', 'display_url' => 'display_url', 'secondary' => 'secondary', 'secondary_url' => 'secondary_url', 'widget' => 'widget', 'display_cm' => 'display_cm', 'status' => 'active');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
            }
            
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;
            $data['profile_id'] = $this->member->profile->id;
            
            try {
                $widget_matrix = new widget_models_matrix();
                $widget_matrix->insert($data);
                
                if ($this->_getParam('saveedit')) {
                    // Get insert id and redirect to the edit page with the redirect parameter
                    $last_id = $this->db->fetchOne('SELECT MAX(counter) FROM widget_matrix WHERE ' . strtolower($this->target->type) . '_id' . '=? AND widget_id=?', array($this->target->id, $data['widget_id']));
                }
                
                if ($this->_getParam('saveedit') && $last_id) {
                    $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/edit/p/mid/' . $this->_getParam('widget_id') . '/' . "id/$last_id/?redirect=" . (urlencode($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->target->pre . '/' . $this->getRequest()->getModuleName())) );
                }
                else {
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName());
                }
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That widget was not successfully added.');
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Add A New Widget';
    }
}
