<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class File_EditController extends ViaMe_Controller_Default_Edit
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit File', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/files_form.php';
        // Remove Elements Early to Reduce Iteration
        unset($form_config['elements']['file_upload']);
        #$form_config['elements']['file_upload'][1]['description'] = 'To replace existing file.  Cannot be undone.';
        
        $form = new Zend_Form();
        $form->setOptions($form_config);
        $form->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);
        
        /*
        if (isset($this->config->upload->max_filesize)) {
            $form->getElement('file_upload')->setMaxFileSize($this->config->upload->max_filesize);
            $form->getElement('file_upload')->addValidator('Size', false, $this->config->upload->max_filesize);
        } 
        */
        
        if ($this->community->meta_stocks || $this->_modObject->symbols) {
            $form->addElement('Text', 'symbols', array('label' => 'Symbols', 'description' => 'Comma Seperated (10 Symbols Max)', 'class' => 'vmfh_acss multiple', 'order' => 100));
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords', 'symbols'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        else {
            $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        }
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('public_location', 'show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('title', 'file_upload'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Edit File', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('file_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');

        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');

        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && (($this->_getParam('override')) || $form->isValid($this->_getAllParams()))) {
            $fields = array('title' => 'title', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'status' => 'active', 'show_on_fail' => 'show_on_fail', 'public_location' => 'public_location');
            $params = (object) $this->_getAllParams();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            /*
            foreach ($fields as $key => $val) {
                if (isset($params->$key)) {
                    if ($params->$key || (isset($params->override) && $params->override)) {
                        // Something has to be set
                        if ($params->$key === true) { $data[$val] = $this->db->quote('1'); }
                        elseif ($params->$key === false) { $data[$val] = $this->db->quote('0'); }
                        else { $data[$val] = $params->$key; }
                    }
                    elseif (!(isset($params->override) && $params->override)) {
                        $data[$val] = null;
                    }
                }
                elseif (!(isset($params->override) && $params->override)) {
                    $data[$val] = null;
                }
            }
            */
            
            // Symbols
            if ($this->_getParam('symbols')) {
                $quotes = new ViaMe_Vm_Quotes();
                $temp_symbs = preg_split('/[,\s]+/', $this->_getParam('symbols'));
                $temp_symbs = array_splice($temp_symbs, 0, 10);
                $symbols = array();
                foreach ($quotes->verify($temp_symbs) as $symbol) {
                    $symbols[] = $symbol->id;
                }
                $data['symbols'] = $this->VMAH->iPgArray($symbols);
            }
            else {
                $data['symbols'] = null;
            }
            
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['activation'] = null;
            }
            // Expiration Field
            if ($this->_getParam('expiration')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('expiration'));
                $data['expiration'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['expiration'] = null;
            }
            
            // Published Field
            if ($this->_getParam('status')) {
                $data['published'] = new Zend_Db_Expr("COALESCE(published, 'now')");
            }
            
            // Modified
            $data['modified'] = new Zend_Db_Expr("(CASE WHEN published ISNULL OR (modified ISNULL AND activation > published) THEN null ELSE 'now' END)::timestamp");
            
            $file_files = new file_models_files();
            
            $where = array();
            $where[] = $file_files->getAdapter()->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $file_files->getAdapter()->quoteInto('counter=?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);  
            
            if ($data) {
                try {
                    $this_file = array();
                    /*
                    // Replace file?
                    if ($form->getElement('file_upload')->isUploaded()) {
                        $upload = $form->getElement('file_upload');
                        $adapter = $upload->getTransferAdapter();
                        
                        foreach ($upload->getFileInfo() as $file => $info) {
                            if ($adapter->isUploaded($file)) {
                                $adapter->addFilter('Rename', array(
                                    'target' => $this->config->upload->directory . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id,
                                    'overwrite' => true
                                ), $file);
                                $adapter->receive($file);
                                
                                if ($adapter->isReceived($file)) {
                                    if ($info['name']) { $this_file['file_name'] = $info['name']; }
                                    if ($info['type']) { $this_file['file_type'] = $info['type']; }
                                    if ($info['size']) { $this_file['file_size'] = $info['size']; }
                                }
                            }
                            break;
                        }
                    }
                    */
                    
                    $file_files->update(
                        array_merge($data, $this_file),
                        $where
                    );
                    
                    // Create or Delete Symlink
                    if ($this->_modObject->public_location && !$this->_getParam('public_location')) {
                        // Delete Symlink
                        $ext = preg_replace('/.*?(\.[^\.]*)$/', '${1}', $this->_modObject->file_name);
                        unlink($this->config->upload->public_directory . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id . "$ext");
                    }
                    elseif (!$this->_modObject->public_location && $this->_getParam('public_location')) {
                        // Create Symlink
                        $public_directory = $this->config->upload->public_directory . '/' . $this->_modObject->file_dir;
                        if (!is_dir($public_directory)) {
                            if (!mkdir($public_directory, 0755, true)) {
                                throw new Exception('Correct public directory structure not created.');
                            }
                        }
                        $ext = preg_replace('/.*?(\.[^\.]*)$/', '${1}', $this->_modObject->file_name);
                        symlink($this->config->upload->relative_public_to_private_path . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id,
                            $public_directory . '/' . $this->_modObject->file_id . "$ext");
                    }
                    
                    $this->log->ERR('_fileEdited');
                    
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                    
                } catch (Exception $e) {
                    $this->view->formErrors = array('That file was not successfully edited.');
                }
            }
            else {
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->_modObject);
            $form->getElement('status')->setValue($this->_modObject->active);
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($this->_modObject->activation)) {
                $date->set($this->_modObject->activation, Zend_Date::ISO_8601);
                $form->getElement('activation')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            if (isset($this->_modObject->expiration)) {
                $date->set($this->_modObject->expiration, Zend_Date::ISO_8601);
                $form->getElement('expiration')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            // Set the symbols
            if ($this->_modObject->symbols) {
                $quotes = new ViaMe_Vm_Quotes();
                $data = $quotes->lookupById($this->VMAH->ePgArray($this->_modObject->symbols));
                
                foreach ($data as $symbol) {
                    $loader[] = $symbol->internal_symbol;
                }
                
                $form->getElement('symbols')->setValue(implode(', ', $loader));
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit File' . ($this->_modObject->title ? ' (' . $this->_modObject->title . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}
