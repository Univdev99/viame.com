<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_CreateController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Create A New Network', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/networks_form.php';        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        $form->setMethod('post');
        
        $form->addElement('Captcha', 'captcha', array('label'=>'Verification', 'order' => 900, 'captcha' => array('captcha' => 'ReCaptcha', 'pubkey' => $this->config->recaptcha->pubkey, 'privkey' => $this->config->recaptcha->privkey)));
        $form->getElement('captcha')->getCaptcha()->getService()->setOption('theme', 'clean');
        
        
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
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Network', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('network_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'meta_title', 'meta_description', 'meta_keywords', 'category', 'parent_id', 'public', 'open', 'password', 'allow_requests', 'show_on_fail', 'page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data['community_id'] = $this->community->id;
            if ($this->target->type == 'NET' && !$this->_getParam('parent_id')) {
                $data['parent_id'] = $this->target->id;
            }
            
            try {
                $network_networks = new network_models_networks();
                
                if (!$this->db->fetchOne('SELECT 1 FROM network_networks WHERE lower(name)=lower(?)', $data['name'])) {
                    $result = $network_networks->insert($data);
                }
                else {
                    $this->view->formErrors = array('You cannot create a network with a duplicate name.');
                }
            } catch (Exception $e) {}
            
            if (!isset($result)) {
                if (!isset($this->view->formErrors)) {
                    $this->view->formErrors = array('That network was not created successfully.');
                }
            }
            else {
                $this->log->ALERT(sprintf('New network (%s) successfully created.', $form->getValue('name')));
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
            }
        }
        
        
        $this->view->form = $form;
        $this->view->page_title = 'Create A New Network';
    }
}