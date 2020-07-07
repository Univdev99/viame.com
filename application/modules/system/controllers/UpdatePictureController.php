<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_UpdatePictureController extends ViaMe_Controller_Default_Create
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    protected $_moduleInMatrix = false;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    #protected $_tableName = '';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Update Picture', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        $justRemoved = false;
        if ($this->getRequest()->isPost() && ($this->_getParam('remove_picture') == 'Remove Picture')) {
            switch (strtolower($this->target->type)) {
                case 'com':
                    $updateTable = new system_models_communities();
                    break;
                case 'net':
                    $updateTable = new network_models_networks();
                    break;
                case 'via':
                    $updateTable = new profile_models_profiles();
                    break;
            }
            
            if (isset($updateTable)) {
                try {
                    $updateTable->update(array('picture_url' => null), $this->db->quoteInto('id = ?', $this->target->id));
                    
                    /* DO WE NEED TO ACTUALLY REMOVE THE LOCAL FILE?? IF SO, HAVE TO CHECK WHEN SUBMITTING NEW URL AND PREVIOUS IS LOCAL UPLOADED COPY */
                    //if (!preg_match('#http://#i', $this->target->space->picture_url) && is_file($this->config->upload->picture_directory . '/' . $this->target->space->picture_url)) {
                    //    unlink($this->config->upload->picture_directory . '/' . $this->target->space->picture_url);
                    //}
                    
                    $this->target->space->picture_url = null;
                    $this->_setParam('image_url', null);
                    $this->log->ERR('_pictureRemoved');
                    
                    $this->view->formErrors = array('The picture has been removed.  You can upload a new one.');
                } catch (Exception $e) {
                    $this->view->formErrors = array('The picture was not successfully removed.  Please try again.');
                }
            }

            $justRemoved = true;
        }
        
        // Load the Form
        $form_config = array(
            'attribs' => array(
                'action' => '?',
                'name' => 'update_picture_form',
                'method' => 'post',
                'id' => 'update_picture_form',
                'class' => 'form',
                'onsubmit' => 'this.file_upload.disabled=false; return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.update_picture_form] }) && YAHOO.viame.dubsub.check(this));' // Set in the form view script
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'update_method' => array('Select', array(
                    'label' => 'Update Method',
                    'description' => "Select the method you'd like to use to update your picture",
                    'required' => true,
                    'MultiOptions' => array(
                        '0' => 'Upload Image',
                        '1' => 'Enter URL'
                    ),
                    'value' => 0,
                    'onchange' => 'if (this.value == 0) { YAHOO.util.Dom.get("update_picture_form").file_upload.disabled=false; YAHOO.util.Dom.get("update_picture_form").image_url.value = ""; YAHOO.util.Dom.get("update_picture_form").image_url.disabled = true; } else { YAHOO.util.Dom.get("update_picture_form").image_url.disabled=false; YAHOO.util.Dom.get("update_picture_form").file_upload.value = ""; YAHOO.util.Dom.get("update_picture_form").file_upload.disabled = true; YAHOO.util.Dom.get("update_picture_form").image_url.select(); }',
                    'order' => 5
                )),
                'file_upload' => array('File', array(
                    'label' => 'Select File(s)',
                    'description' => 'Maximium size of 512K. Image-file formats: JPEG, GIF, BMP or PNG.',
                    'validators' => array(
                        array('IsImage', false),
                        array('Extension', false, array('jpg', 'jpeg', 'gif', 'bmp', 'png')),
                        array('Count', false, array('min' => 1, 'messages' => array(Zend_Validate_File_Count::TOO_FEW => 'You must select an image to upload'))),
                        array('Size', false, 524288)
                    ),
                    'order' => 10
                )),
                'image_url' => array('Text', array(
                    'label' => 'Picture URL',
                    'description' => 'Enter the full URL of where the image resides.  Image will be served from URL location and no local copy will be stored.',
                    'maxlength' => 512,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Pattern(regex=/^http:\/\/.+/, flags="i", label="url", message="That does not seem to be a valid {label}", ignoreEmpty=true, groups=[update_picture_form])',
                    'filters' => array(
                        array('HTMLPurify')
                    ),
                    'order' => 15,
                    'validators' => array(
                        array('Regex', false, array(
                            'pattern' => '/^http:\/\/.+/i',
                            'messages' => array(
                                Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted URL'
                            )
                        ))
                    )
                ))
            )
        );

        $form = new Zend_Form();
        $form->setOptions($form_config);
        $form->setAttrib('enctype', Zend_Form::ENCTYPE_MULTIPART);
        
        #$form->getElement('file_upload')->setDestination($this->config->upload->directory);
        
        // Set to 512K Max Size
        $form->getElement('file_upload')->setMaxFileSize(524288);
        #$form->getElement('file_upload')->addValidator('Size', false, 524288);
        
        $form->addDisplayGroup(array('update_method', 'file_upload', 'image_url'), 'main', array('legend' => 'Update Source'));
        
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
        
        $form->addElement('Submit', 'submit', array('label' => 'Save', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('update_picture_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');

        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');
        
        // Add some last minute validators
        if (!$justRemoved && $this->getRequest()->isPost()) {
            if ($this->_getParam('update_method')) {
                $form->getElement('image_url')->setRequired(true);
                $form->getElement('file_upload')->removeValidator('Count');
                $form->getElement('file_upload')->setAttrib('disabled', 'disabled');
            }
            else {
                $form->getElement('image_url')->setAttrib('disabled', 'disabled');
            }
        }
        
        if (!$justRemoved && $this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $final_picture_url = null;
            
            if ($this->_getParam('update_method') && $this->_getParam('image_url')) {
                $final_picture_url = $this->_getParam('image_url');
            }
            else {
                // Save the file first and construct the $final_picture_url
                $upload = $form->getElement('file_upload');
                $adapter = $upload->getTransferAdapter();
                
                foreach ($upload->getFileInfo() as $file => $info) {
                    if ($adapter->isUploaded($file)) {
                        // Check for the appropriate directory structure; create if not there
                        $path = date('Y/md') . '/' . strtolower($this->target->type) . '-' . $this->target->id;
                        
                        $directory = $this->config->upload->picture_directory . "/$path";
                        
                        if (!is_dir($directory)) {
                            if (!mkdir($directory, 0755, true)) {
                                throw new Exception('Correct directory structure not created.');
                            }
                        }
                        
                        $adapter->addFilter('Rename', array(
                            #'source' => $adapter->getFileName($file),
                            'target' => "$directory/" . $adapter->getFileName($file, false),
                            'overwrite' => true
                        ), $file);
                        $adapter->receive($file);
                        
                        if ($adapter->isReceived($file)) {
                            $this->log->ERR('_pictureUploaded');
                            
                            $final_picture_url = "$path/" . $adapter->getFileName($file, false);
                        }
                    }
                    
                    // Only go through one time
                    break;
                }
            }
            
            if ($final_picture_url) {
                if ($final_picture_url != $this->target->space->picture_url) {
                    switch (strtolower($this->target->type)) {
                        case 'com':
                            $updateTable = new system_models_communities();
                            break;
                        case 'net':
                            $updateTable = new network_models_networks();
                            break;
                        case 'via':
                            $updateTable = new profile_models_profiles();
                            break;
                    }
                    
                    if (isset($updateTable)) {
                        try {
                            $updateTable->update(array('picture_url' => $final_picture_url), $this->db->quoteInto('id = ?', $this->target->id));
                            
                            $this->target->space->picture_url = $final_picture_url;
                            
                            $this->log->ERR('_pictureUpdated');
                            
                            $this->_autoredirect($this->target->pre . '/');
                        } catch (Exception $e) {
                            $this->view->formErrors = array('The picture was not successfully updated.  Please try again.');
                        }
                    }
                }
                else {
                    $this->view->formErrors = array('The URL has not changed and the picture was not updated.  Please try again.');
                }
            }
        }
        else {
            if (
                isset($this->target->space->picture_url) && $this->target->space->picture_url &&
                preg_match('#http://#i', $this->target->space->picture_url)
            ) {
                $form->getElement('update_method')->setValue(1);
                $form->getElement('image_url')->setValue($this->target->space->picture_url);
                $form->getElement('file_upload')->setAttrib('disabled', 'disabled');
            }
            else {
                $form->getElement('image_url')->setAttrib('disabled', 'disabled');
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Update Picture';
    }
}
