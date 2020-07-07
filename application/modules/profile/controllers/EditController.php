<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_EditController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->member_id;
        }
        else {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit Profile', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) || (!$this->_getParam('id'))) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/profiles_form.php';
        unset($form_config['elements']['status']);
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('editor_preference'), 'options_preferences', array('legend' => 'Preferences'));
        $form->addDisplayGroup(array('page_width', 'page_layout', 'page_sublayout', 'page_theme', 'page_style'), 'options_space', array('legend' => 'Space Layout / Style'));
        #$form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('same_member_priv', 'show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('name'), 'main');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Update Profile', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('profile_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'id', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('id', 'vmpd_nar', 'redirect'), 'hidden');
        
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            #$fields = array('name' => 'name', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'same_member_priv' => 'same_member_priv', 'show_on_fail' => 'show_on_fail', 'editor_preference' => 'editor_preference', 'page_width' => 'page_width', 'page_layout' => 'page_layout', 'page_sublayout' => 'page_sublayout', 'page_theme' => 'page_theme', 'page_style' => 'page_style', 'status' => 'active');
            $fields = array('name' => 'name', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'same_member_priv' => 'same_member_priv', 'show_on_fail' => 'show_on_fail', 'editor_preference' => 'editor_preference', 'page_width' => 'page_width', 'page_layout' => 'page_layout', 'page_sublayout' => 'page_sublayout', 'page_theme' => 'page_theme', 'page_style' => 'page_style');
            /*
            $values = $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($values[$field]) && $values[$field] !== '') {
                    $data[$field] = $values[$field];
                }
                else {
                    $data[$field] = null;
                }
            }
            */
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            
            try {
                $profile_profiles = new profile_models_profiles();
                $profile_profiles->update($data, array(
                    $profile_profiles->getAdapter()->quoteInto('member_id = ?', $this->target->id),
                    $profile_profiles->getAdapter()->quoteInto('id = ?', $this->_getParam('id'))
                ));
                #$this->db->commit();
                
                $this->log->ALERT("Profile information successfully updated.");
                
                #return $this->render('update-success');
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
            } catch (Exception $e) {
                #$this->db->rollBack();
                
                $this->log->ALERT("Profile information update failed!");
                
                #return $this->render('update-failed');
                $this->view->formErrors = array('Profile update failed.');
            }
        #} elseif ($this->getRequest()->isPost()) {
        #    $form->populate($this->_getAllParams());
        } elseif (!$this->getRequest()->isPost()) {
            $profile = $this->db->fetchRow('SELECT * FROM profile_profiles WHERE member_id=? AND id=?', array($this->target->id, $this->_getParam('id')));
            $form->populate((array) $profile);
            $form->getElement('editor_preference')->setValue($profile->editor_preference===null ? '' : $profile->editor_preference);
            #$form->getElement('status')->setValue($profile->active);
        }        
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Profile' . (isset($profile) && $profile->name ? ' (' . $profile->name . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}