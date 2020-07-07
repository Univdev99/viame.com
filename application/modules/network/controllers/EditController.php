<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_EditController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit Network', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) || (!$this->_getParam('id'))) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
                
        require dirname(dirname(__FILE__)) . '/models/networks_form.php';        
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        #$form->setMethod('post');
        
        $form->addElement('Hidden', 'id', array('required' => true));
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Network', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('network_form').vivregval_canceled = true;"));
        
        // Load up the existing networks for possible parent
        $networks[''] = '-- Select A Parent Network --';
        $select = $this->db->select()
            ->from(array('n' => 'network_networks'),
                array(
                    'id',
                    'name',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('network_networks', 'parent_id', 'id', id, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('n.id<>?', $this->_getParam('id'))
            ->where('n.active=?', 't')
            ->order(array('sort_order', 'n.id', 'n.name'));
        switch($this->target->type) {
            case 'VIA':
                $select->where('n.profile_id=?', $this->target->id);
                break;
            case 'NET':
                $select->where('n.parent_id=?', $this->target->id);
                break;
            case 'COM':
                $select->where('n.community_id=?', $this->target->id);
                break;
        }
        foreach ($this->db->fetchAll($select) as $network) {
            $temp_networks[$network->sort_order] = $network;
        }
        if (isset($temp_networks)) {
            $temp_sorted_keys = array_keys( $temp_networks );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $networks[$temp_networks[$key]->id] = (str_repeat(' -- ', substr_count($temp_networks[$key]->sort_order, '-'))) . $temp_networks[$key]->name;
            }
        }
        $form->getElement('parent_id')->setMultiOptions($networks);
        
        
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('category', 'parent_id'), 'options_organize', array('legend' => 'Organize'));
        $form->addDisplayGroup(array('page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style'), 'options_space', array('legend' => 'Space Layout / Style'));
        $form->addDisplayGroup(array('public', 'open', 'password', 'allow_requests', 'show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('name', 'captcha'), 'main');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Network', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('network_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'meta_title', 'meta_description', 'meta_keywords', 'category', 'parent_id', 'public', 'open', 'password', 'allow_requests', 'show_on_fail', 'page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style');
            $values = $form->getValues();
            foreach ($fields as $field) {
                if (isset($values[$field])) {
                    if ($values[$field]) {
                        $data[$field] = $values[$field];
                    }
                    else {
                        $data[$field] = null;
                    }
                }
            }
            
            try {
                $network_networks = new network_models_networks();
                $network_networks->update($data, $network_networks->getAdapter()->quoteInto('id = ?', $this->_getParam('id')));
                $this->log->ALERT("Network information successfully updated.");
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                
            } catch (Exception $e) {
                $this->log->ALERT("Network information update failed!");
                
                $this->view->formErrors = array('Network update failed.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            $network = $this->db->fetchRow('SELECT * FROM network_networks WHERE id=?', $this->_getParam('id'));
            $form->populate((array) $network);
            #$form->getElement('parent_id')->setValue($network->parent_id);
            #$form->getElement('editor_preference')->setValue($network->editor_preference===null ? '' : $network->editor_preference);
            #$form->getElement('status')->setValue($network->active);
            
        }        
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Network' . (isset($profile) && $network->name ? ' (' . $network->name . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}