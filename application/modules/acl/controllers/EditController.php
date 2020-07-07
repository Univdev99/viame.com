<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Acl_EditController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        /*
            Moved this to the beginning to bypass loading of the form and other ancillary
            functions.  Also, didn't use cancel->isChecked(), as that requires form load.
        */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        
        // Load up the form
        require dirname(dirname(__FILE__)) . '/models/acls_form.php';
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        $form->setMethod('post');
        
        
        /*
            Things that need to be done outside of the cache due to parameters
        */
            // Load Groups
            $temparray = array();
            foreach ($this->db->fetchAll("SELECT * FROM contact_group_groups WHERE profile_id=? AND active='t'", $this->member->profile->id) as $temp) {
                $temparray[$temp->counter] = $temp->name;
            }
            $form->getElement('w_groups')->setMultiOptions($temparray);
            
            // Load Profiles
            $temparray = array();
            $select = $this->db->select()
                ->from(array('c' => 'contact_contacts'))
                ->where('c.profile_id=?', $this->member->profile->id)
                ->where('c.active NOTNULL')
                ->join(array('p' => 'profile_profiles'), 'c.contact_profile_id = p.id', array('name' => 'name'))
                ->where('p.active=?', 't')
                ->join(array('p2' => 'profile_profiles'), 'c.profile_id = p2.id', array())
                ->where('p2.active=?', 't')
                ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                ->where('b.active=?', 't')
                ->join(array('b2' => 'member_members'), 'p2.member_id = b2.id', array())
                ->where('b2.active=?', 't')
                ->order(array('status', 'name'));
            foreach ($this->db->fetchAll($select) as $temp) {
                $temparray[$temp->contact_profile_id] = ($temp->display ? $temp->display : $temp->name);
            }
            $form->getElement('w_contact_profiles')->setMultiOptions($temparray);
            
            // Set mod (module_id), mid (matrix_counter), and/or iid (item_counter)
            foreach (array('mod', 'mid', 'iid', 'id') as $temp) {
                if ($this->_getParam($temp)) {
                    $form->addElement('Hidden', $temp, array('value' => $this->_getParam($temp)));
                        $form->getElement($temp)->setDecorators( array('ViewHelper') );
                }
            }
            
            // Redirects
            $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
                $form->getElement('vmpd_nar')->setDecorators( array('ViewHelper') );
            $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
                $form->getElement('redirect')->setDecorators( array('ViewHelper') );
            
            // Submission and Cancellation
            $form->addElement('Submit', 'submit', array('label' => 'Update ACL', 'ignore' => true, 'order' => 999));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));

        
        // If posted, validate, and write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            try {
                $acl_acls = new acl_models_acls();
                $rowset = $acl_acls->find(
                    ($this->target->type == 'COM' ? $this->target->id : 0),
                    ($this->target->type == 'NET' ? $this->target->id : 0),
                    ($this->target->type == 'VIA' ? $this->target->id : 0),
                    $this->_getParam('mod', 0),
                    $this->_getParam('mid', 0),
                    $this->_getParam('iid', 0),
                    $this->_getParam('id')
                );
                $row = $rowset->current();
                
                $fields = array('title', 'display', 'orderby', 'w_registration_status', 'w_dos', 'w_password', 'w_member_initial_amount', 'w_member_trial_amount', 'w_member_trial_quantity', 'w_member_trial_interval', 'w_member_amount', 'w_member_quantity', 'w_member_interval', 'w_member_auto_renew', 'privilege', 'filter', 'description', 'recursive', 'invisible', 'w_arb_profiles', 'w_arb_profiles_ex');
                $params = (object) $form->getValues();
                foreach ($fields as $key) {
                    if (isset($params->$key) && $params->$key !== '') {
                        $row->$key = $params->$key;
                    }
                    else {
                        $row->$key = NULL;
                    }
                }
                
                // Need to manually set arrays w_groups and w_contact_profiles
                foreach (array('w_groups', 'w_contact_profiles') as $key) {
                    if ($this->_getParam($key)) {
                        $row->$key = '{' . implode(',', $this->_getParam($key)) . '}';
                    }
                    else {
                        $row->$key = NULL;
                    }
                }
                
                if ($this->_getParam('expiration')) {
                    $date = new Zend_Date();
                    if (isset($this->member)) {
                        $date->setTimezone($this->internal->member->timezone);
                    }
                    $date->set($this->_getParam('expiration'));
                    $row->{'expiration'} = $date->get(Zend_Date::ISO_8601);
                }
                else {
                    $row->{'expiration'} = null;
                }
                
                if ($this->_getParam('w_member_start')) {
                    $date = new Zend_Date();
                    if (isset($this->member)) {
                        $date->setTimezone($this->internal->member->timezone);
                    }
                    $date->set($this->_getParam('w_member_start'));
                    $row->{'w_member_start'} = $date->get(Zend_Date::ISO_8601);
                }
                else {
                    $row->{'w_member_start'} = null;
                }
                
                $row->save();
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That ACL was not updated successfully.');
                
                $profiler = $this->db->getProfiler();
                $query = $profiler->getLastQueryProfile();
                echo $query->getQuery();
            }
            
        }
        elseif ($this->getRequest()->isPost()) {
            $this->view->formErrors = array('Please fix validation errors!');
        }
        elseif (!$this->getRequest()->isPost()) {
            $acl_acls = new acl_models_acls();
            $rowset = $acl_acls->find(
                ($this->target->type == 'COM' ? $this->target->id : 0),
                ($this->target->type == 'NET' ? $this->target->id : 0),
                ($this->target->type == 'VIA' ? $this->target->id : 0),
                $this->_getParam('mod', 0),
                $this->_getParam('mid', 0),
                $this->_getParam('iid', 0),
                $this->_getParam('id')
            );
            $row = $rowset->current();
            
            // Need to manually set arrays w_groups and w_contact_profiles
            foreach (array('w_groups', 'w_contact_profiles') as $key) {
                if ($row->$key) {
                    $row->$key = explode(',', preg_replace('/^\{(.*)\}$/', '${1}', $row->$key));
                }
            }
            
            $form->populate($rowset->current()->toArray());
            
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($row->w_member_start) && $row->w_member_start) {
                $date->set($row->w_member_start, Zend_Date::ISO_8601);
                $form->getElement('w_member_start')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'))));
            }
            if (isset($row->expiration) && $row->expiration) {
                $date->set($row->expiration, Zend_Date::ISO_8601);
                $form->getElement('expiration')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'))));
            }
        }
            
        $this->view->form = $form;
        
        $this->renderScript('form.phtml');
    }
}