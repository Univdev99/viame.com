<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class File_CreateController extends ViaMe_Controller_Default_Create
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'file_files';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Upload File', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . ($this->_getParam('mid') ? '/p/mid/' . $this->_getParam('mid') . '/' : ''));
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/files_form.php';
        $form = new Zend_Form();
        $form->setOptions($form_config);
        $form->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);
        
        #$form->getElement('file_upload')->setDestination($this->config->upload->directory);
        if (isset($this->config->upload->max_filesize)) {
            $form->getElement('file_upload')->setMaxFileSize($this->config->upload->max_filesize);
            $form->getElement('file_upload')->addValidator('Size', false, $this->config->upload->max_filesize);
        }    
        if (isset($this->config->upload->max_num) && ($this->config->upload->max_num > 1)) {
            $form->getElement('file_upload')->setMultiFile($this->config->upload->max_num);
            $form->getElement('file_upload')->addValidator('Count', false, array('min' => 1, 'max' => $this->config->upload->max_num));
        }
        else {
            $form->getElement('file_upload')->setRequired(true);
        }
        
        if ($this->community->meta_stocks) {
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
        
        $form->addElement('Submit', 'submit', array('label' => 'Upload File(s)', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('file_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');

        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');
        
        /*
        // Other Stuff
        $form->addElement('Checkbox', 'tos', array(
            'label' => 'Usage Authorization',
            'order' => 905, 'required' => true,
            'description' => 'I have read the <a href="javascript:void(null);" title="Usage Authorization">Usage Authorization</a> and certify that I have rights to use the uploaded file(s).',
            'value' => true,
            'validators' => array(
                #array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'You must agree to to the Terms of Service & Privacy Policy.')))
                array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'You must certify you have usage rights.')))
            )
        ));
        $form->getElement('tos')->getDecorator('description')->setEscape(false);
            
        $form->addElement('Captcha', 'captcha', array('label'=>'Verification', 'order' => 910, 'captcha' => array('captcha' => 'ReCaptcha', 'pubkey' => $this->config->recaptcha->pubkey, 'privkey' => $this->config->recaptcha->privkey)));
        $form->getElement('captcha')->getCaptcha()->getService()->setOption('theme', 'clean');
        
        $form->addDisplayGroup(array('captcha', 'tos', ), 'verify', array('legend' => 'Authorization & Verification'));
        */
        
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $upload = $form->getElement('file_upload');
            $adapter = $upload->getTransferAdapter();
            
            // Prepare the data object
            $fields = array('title' => 'title', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'status' => 'active', 'show_on_fail' => 'show_on_fail', 'public_location' => 'public_location', 'mid' => 'matrix_counter');
            foreach ($fields as $key => $val) {
                if ($this->_getParam($key) !== '') {
                    $data[$val] = $this->_getParam($key);
                }
            }
            // Manually set title if not set
            if (!isset($data['title'])) {
                $data['title'] = '';
            }
            
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
            
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
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
            
            // Status Field
            if ($this->_getParam('status')) {
                $data['published'] = new Zend_Db_Expr('now()');
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            //$dir_checked = false;
            //$directory = null;
            $file_files = new file_models_files();
            $this->view->success = array();
            
            foreach ($upload->getFileInfo() as $file => $info) {
                if ($adapter->isUploaded($file)) {
                    //if (!$dir_checked || !$directory) {
                        // Check for the appropriate directory structure; create if not there
                        $date = date('Y/m/d');
                        $data['file_dir'] = $date;
                        
                        $directory = $this->config->upload->directory . "/$date";
                        if (!is_dir($directory)) {
                            if (!mkdir($directory, 0755, true)) {
                                //$dir_checked = true;
                            //}
                            //else {
                                // Failed creating the directory
                                throw new Exception('Correct directory structure not created.');
                            }
                        }
                        
                        // Create public location symlink?
                        if ($this->_getParam('public_location')) {
                            $public_directory = $this->config->upload->public_directory . "/$date";
                            if (!is_dir($public_directory)) {
                                if (!mkdir($public_directory, 0755, true)) {
                                    //$dir_checked = true;
                                //}
                                //else {
                                    // Failed creating the directory
                                    throw new Exception('Correct public directory structure not created.');
                                }
                            }
                        }
                    //}
                    
                    do {
                        $filename = md5(uniqid(rand(), true));
                    } while (is_file("$directory/$filename"));
                    
                    $adapter->addFilter('Rename', array(
                        #'source' => $adapter->getFileName($file),
                        'target' => "$directory/$filename",
                        'overwrite' => false
                    ), $file);
                    $adapter->receive($file);
                    
                    if ($adapter->isReceived($file)) {
                        // Create entry in database
                        $this_file = array();
                        $this_file['file_id'] = $filename;
                        if ($info['name']) { $this_file['file_name'] = $info['name']; }
                        if ($info['type']) { $this_file['file_type'] = $info['type']; }
                        if ($info['size']) { $this_file['file_size'] = $info['size']; }
                        if (!$data['title']) { $this_file['title'] = $info['name']; }
                        
                        $file_files->insert(array_merge($data, $this_file));
                        
                        // Get insert id for redirect to the edit page with the redirect parameter
                        $last_id = $this->db->fetchOne('SELECT MAX(counter) FROM ' . $this->_tableName . ' WHERE ' . strtolower($this->target->type) . '_id' . '=? AND matrix_counter=? AND profile_id=?', array($this->target->id, $data['matrix_counter'], $data['profile_id']));
                        
                        if (isset($data['published']) && $data['published']) {
                            $this->log->ERR('_filePublished', array('item_counter' => $last_id));
                            #$this->VMAH->log('log message', Zend_Log::EMERG, array('test1', 'test2', 'test3'));
                        }
                        else {
                            $this->log->ERR('_fileSaved', array('item_counter' => $last_id));
                        }
                        
                        // Create public location symlink?
                        if ($this->_getParam('public_location')) {
                            $ext = preg_replace('/.*?(\.[^\.]*)$/', '${1}', $info['name']);
                            #symlink("$directory/$filename", "$public_directory/$filename$ext");
                            symlink($this->config->upload->relative_public_to_private_path . '/' . $date . '/' . $filename, "$public_directory/$filename$ext");
                            
                            // Push the view and display URL for public files only.  Non-public files require
                            // database insertion first to get the counter id
                            $this->view->success[] = array('filename' => $info['name'], 'location' => $this->config->upload->public_server . ($data['title'] ? '/' . $this->view->escape($this->view->SEO_Urlify($data['title'])) : '/No-Title') . '/' . $data['file_dir'] . '/' . $this_file['file_id'] . '/' . urlencode($this_file['file_name']));
                        }
                        else {
                            $this->view->success[] = array('filename' => $info['name']);
                        }
                    }
                }
                #Zend_Debug::Dump($info);
            }
            
            #if ($this->_getParam('public_location')) {
            #    return $this->render('success');
            #}
            #else {
            #    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . ($this->_getParam('mid') ? '/p/mid/' . $this->_getParam('mid') . '/' : ''));
            #}
            
            return $this->render('success');
        }
        else {
            $form->populate($_GET);
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Upload File' . ($this->target->currentModule->display ? ' (' . $this->target->currentModule->display . ')' : '');
    }
}
