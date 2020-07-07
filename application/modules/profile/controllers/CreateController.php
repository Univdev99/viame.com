<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_CreateController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->member_id;
        }
        elseif (isset($this->member)) {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Create A New Profile', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/profiles_form.php';
        unset($form_config['elements']['status']);
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        $form->addElement('Captcha', 'captcha', array('label'=>'Verification', 'order' => 910, 'captcha' => array('captcha' => 'ReCaptcha', 'pubkey' => $this->config->recaptcha->pubkey, 'privkey' => $this->config->recaptcha->privkey)));
        $form->getElement('captcha')->getCaptcha()->getService()->setOption('theme', 'clean');
        
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('editor_preference'), 'options_preferences', array('legend' => 'Preferences'));
        $form->addDisplayGroup(array('page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style'), 'options_space', array('legend' => 'Space Layout / Style'));
        $form->addDisplayGroup(array('same_member_priv', 'show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('name', 'captcha'), 'main');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Profile', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('profile_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $profile_profiles = new profile_models_profiles();
            
            $data['member_id'] = $this->target->id;
            
            $fields = array('name', 'meta_title', 'meta_description', 'meta_keywords', 'same_member_priv', 'show_on_fail', 'editor_preference', 'page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
            }
            
            if (isset($this->community->id)) {
                $data['community_id'] = $this->community->id;
            }
            try {
                if (!$this->db->fetchOne('SELECT 1 FROM profile_profiles WHERE member_id=? AND lower(name)=lower(?)', array($this->target->id, $data['name']))) {
                    $result = $profile_profiles->insert($data);
                }
                else {
                    $this->view->formErrors = array('You cannot create a profile with the same name that you have already created and/or deleted.');
                }
            } catch (Exception $e) {}
            
            if (!isset($result)) {
                if (!isset($this->view->formErrors)) {
                    $this->view->formErrors = array('That profile was not created successfully.');
                }
            }
            else {
                $this->log->ALERT(sprintf('New profile (%s) successfully created.', $form->getValue('name')));
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
            }
        }
        
        
        $this->view->form = $form;
        $this->view->page_title = 'Create A New Profile';
    }
}