<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Mail_FolderController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Mail', 'PREPEND');
        $this->view->headTitle('Folder Manager', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* FETCHED FROM SIDEBAR
        // Display Folders
        $select = $this->db->select()
            ->from(array('f' => 'mail_folder_folders'),
                array(
                    '*',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('mail_folder_folders', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('f.profile_id=?', $this->target->id)
            ->order(array('sort_order', 'f.counter', 'f.name'));
        
        // Natural Sort
        $temp_folders = $temp_sorted_folders = array();
        foreach ($this->db->fetchAll($select) as $folder) {
            $temp_folders[$folder->sort_order] = $folder;
        }
        
        $temp_sorted_keys = array_keys( $temp_folders );
        natsort( $temp_sorted_keys );
        
        foreach ($temp_sorted_keys as $key) {
            $temp_sorted_folders[] = $temp_folders[$key];
        }
        
        $this->view->folders = $temp_sorted_folders;
        */
    }
    
    
    public function createAction()
    {
        $this->view->headTitle('Mail', 'PREPEND');
        $this->view->headTitle('Folder Manager', 'PREPEND');
        $this->view->headTitle('Create A New Folder', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/folderFolders_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        
        $form->addDisplayGroup(array('description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('parent_id'), 'options_parent', array('legend' => 'Parent Group'));
        
        $form->addDisplayGroup(array('name'), 'main');
        
        $form->addElement('Submit', 'submit_b', array('label' => 'Create Folder', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit_b', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            #$form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        
        // Load up the existing folders for possible parent
        $folders[''] = '-- Select A Parent Folder --';
        foreach ($this->db->fetchAll("SELECT counter, name, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('mail_folder_folders', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM mail_folder_folders WHERE active='t' AND profile_id=? ORDER BY sort_order, counter, name", array($this->target->id)) as $folder) {
            $temp_folders[$folder->sort_order] = $folder;
        }
        if (isset($temp_folders)) {
            $temp_sorted_keys = array_keys( $temp_folders );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $folders[$temp_folders[$key]->counter] = (str_repeat(' -- ', substr_count($temp_folders[$key]->sort_order, '-'))) . $temp_folders[$key]->name;
            }
        }
        $form->getElement('parent_id')->setMultiOptions($folders);

        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'description', 'parent_id');
            $params = (object) $form->getValues();
            foreach ($fields as $field) {
                if (isset($params->$field) && $params->$field !== '') {
                    $data[$field] = $params->$field;
                }
            }
            
            $data['profile_id'] = $this->target->id;
            
            try {
                $mail_folder_folders = new mail_models_folderFolders();
                
                if (!$this->db->fetchOne('SELECT 1 FROM mail_folder_folders WHERE profile_id=? AND lower(name)=lower(?)', array($this->target->id, $data['name']))) {
                    $result = $mail_folder_folders->insert($data);
                }
                else {
                    $this->view->formErrors = array('You cannot create a folder with a duplicate name.');
                }
            } catch (Exception $e) {}

            if (!isset($result)) {
                #print $this->db->getProfiler()->getLastQueryProfile()->getQuery();
                if (!isset($this->view->formErrors)) {
                    $this->view->formErrors = array('That folder was not created successfully.');
                }
            }
            else {
                $this->log->ALERT(sprintf('New mail folder (%s) successfully created.', $form->getValue('name')));
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
            }
        }
        
        $this->view->form = $form;
    }
    
    
    public function editAction()
    {
        $this->view->headTitle('Mail', 'PREPEND');
        $this->view->headTitle('Folder Manager', 'PREPEND');
        $this->view->headTitle('Edit Folder', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) || (!$this->_getParam('id'))) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
        }
                
        require dirname(dirname(__FILE__)) . '/models/folderFolders_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        $form->addElement('Hidden', 'id', array('required' => true));
        
        $form->addDisplayGroup(array('description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('parent_id'), 'options_parent', array('legend' => 'Parent Group'));
        
        $form->addDisplayGroup(array('name'), 'main');
        
        $form->addElement('Submit', 'submit_b', array('label' => 'Update Group', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit_b', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect', 'id'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            #$form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        
        // Load up the existing folders for possible parent
        /*
        $folders[''] = '-- Select A Parent Folder --';
        $select = $this->db->select()
            ->from(array('f' => 'mail_folder_folders'),
                array(
                    'counter',
                    'name',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('mail_folder_folders', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('f.profile_id=?', $this->target->id)
            ->where('f.counter<>?', $this->_getParam('id'))
            ->where('f.active=?', 't')
            ->order(array('sort_order', 'f.counter', 'f.name'));
        foreach ($this->db->fetchAll($select) as $folder) {
            $temp_folders[$folder->sort_order] = $folder;
        }
        if (isset($temp_folders)) {
            $temp_sorted_keys = array_keys( $temp_folders );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $folders[$temp_folders[$key]->counter] = (str_repeat(' -- ', substr_count($temp_folders[$key]->sort_order, '-'))) . $temp_folders[$key]->name;
            }
        }
        $form->getElement('parent_id')->setMultiOptions($folders);
        */
        
        // Load up the existing folders for possible parent
        $folders[''] = '-- Select A Parent Folder --';
        foreach ($this->db->fetchAll("SELECT counter, name, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('mail_folder_folders', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM mail_folder_folders WHERE active='t' AND profile_id=? ORDER BY sort_order, counter, name", array($this->target->id)) as $folder) {
            $temp_folders[$folder->sort_order] = $folder;
        }
        if (isset($temp_folders)) {
            $temp_sorted_keys = array_keys( $temp_folders );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $folders[$temp_folders[$key]->counter] = (str_repeat(' -- ', substr_count($temp_folders[$key]->sort_order, '-'))) . $temp_folders[$key]->name;
            }
            $form->getElement('parent_id')->setAttrib('disable', array($this->_getParam('id')));
        }
        $form->getElement('parent_id')->setMultiOptions($folders);
        

        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'description', 'parent_id');
            $values = $form->getValues();
            foreach ($fields as $field) {
                if (isset($values[$field])) {
                    if ($values[$field] === '') { $data[$field] = null; }
                    else { $data[$field] = $values[$field]; }
                }
            }
            
            try {
                $mail_folder_folders = new mail_models_folderFolders();
                $mail_folder_folders->update($data, array(
                    $mail_folder_folders->getAdapter()->quoteInto('profile_id = ?', $this->target->id),
                    $mail_folder_folders->getAdapter()->quoteInto('counter=?', $this->_getParam('id'))
                ));
                
                $this->log->ALERT("Folder information successfully updated.");
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
                
            } catch (Exception $e) {
                #$profiler = $this->db->getProfiler();
                #$query = $profiler->getLastQueryProfile();
                #echo $query->getQuery();
            
                $this->log->ALERT("Folder information update failed!");
                
                $this->view->formErrors = array('Folder update failed.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            $form->populate((array) $this->db->fetchRow('SELECT * FROM mail_folder_folders WHERE profile_id=? AND counter=?', array($this->target->id, $this->_getParam('id'))));
        }        
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Folder';
        $this->_helper->viewRenderer->renderScript('folder/create.phtml');
    }
    
    
    public function activeAction()
    {
        // Toggle Profile Active
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE mail_folder_folders SET active=(NOT active) WHERE profile_id=? AND counter=?", Array($this->target->id, $this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
    }
    
    
    public function deleteAction()
    {
        // Delete Profile
        if ($this->_getParam('id')) {
            $this->db->query("DELETE FROM mail_folder_folders WHERE profile_id=? AND counter=?", Array($this->target->id, $this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . $this->getRequest()->getControllerName() . '/');
    }
}