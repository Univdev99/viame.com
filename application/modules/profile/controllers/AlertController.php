<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_AlertController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    // Alert types - Sync all Around with the index on table profile_alerts type column
    protected $_alertTypes = array(
        1 => array(
            'name' => 'E-Mail',
            'code' => 'email',
            'description' => 'E-Mail alert to another email address.',
            'id_description' => 'Your email address.'
        ),
        2 => array(
            'name' => 'SMS',
            'code' => 'sms',
            'description' => 'Text alert to your cell phone.',
            'id_description' => 'Full international phone number including country code (ie. 12135551212 (US))'
        ),
        3 => array(
            'name' => 'Voice',
            'code' => 'voice',
            'description' => 'Voice alert.',
            'id_description' => 'Full international phone number including country code (ie. 12135551212 (US))'
        )
    );
    
    
    public function init()
    {
        parent::init();
        
        // This is so admins can control other spaces
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
        
        $this->view->alertTypes = $this->_alertTypes;
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Manage Profile Alerts', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        $select = $this->db->select()->from(array('pa' => 'profile_alerts'))
            ->where('pa.active NOTNULL')
            ->where('pa.profile_id=?', $this->target->id)
            ->order(array('pa.type', 'pa.counter'));
        
        
        $this->view->alerts = $this->db->fetchAll($select);
    }
    
    
    public function addAction()
    {
        $this->view->headTitle('Profile Alert Manager', 'PREPEND');
        $this->view->headTitle('Add A New Alert', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
        }
        
        $form = new Zend_Form();
        //$form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'alert_form',
                'method' => 'post',
                'action' => '?',
                'id' => 'alert_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.alert_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'type' => array('Select', array(
                    'label' => 'Alert Type',
                    'description' => 'Select the type of alert you would like to receive.',
                    'required' => true,
                    'order' => 5,
                    'multiOptions' => array(
                        #'1'  => 'E-Mail',
                        '2'  => 'SMS Text Message',
                        #'3'  => 'Voice',
                        
                        #'4'  => 'Google Chat',
        		        #'5'  => 'Yahoo!',
        		        #'6'  => 'AIM',
        		        #'7'  => 'ICQ',
        		        #'8'  => 'Facebook',
        		        #'9'  => 'Jabber'
                    )
                )),
                'identifier' => array('Text', array(
                    'label' => 'Identifier',
                    'description' => 'Enter your phone number, username, or other id.  <span class="netdown">Phone numbers for SMS and voice alerts must include <strong><u>Full Country Code AND Area Code</u></strong> (ie. 16195551212 (US))</span>',
                    'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="identifier", message="The {label} field cannot be empty.", groups=[alert_form])',
                    #'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
                    #'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
                    'order' => 10,
                    'validators' => array(
                        array('StringLength', false, array(0, 256))
                    )
                ))
            )
        ));
        $form->getElement('identifier')->getDecorator('description')->setEscape(false);
        
        $form->addDisplayGroup(array('type', 'identifier'), 'main');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Alert', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('alert_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Do LIVE email check
        if ($this->_getParam('type') == 1 && $this->_getParam('identifier')) {
            $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
            #$SMTP_Validator->debug = true;
            $results = $SMTP_Validator->validate(array($this->_getParam('identifier')), $this->community->email);
            
            if (isset($results[$this->_getParam('identifier')]) && ($results[$this->_getParam('identifier')][0] === false)) {
                // Came back false
                if (isset($results[$this->_getParam('identifier')][1]) && $results[$this->_getParam('identifier')][1]) {
                    $this->view->formErrors = array('Invalid Email Address: ' . $this->_getParam('identifier') . ' (' . $results[$this->_getParam('identifier')][1] . ') - Please correct or select an alternate email address.');
                }
                else {
                    $this->view->formErrors = array('Invalid Email Address: ' . $this->_getParam('identifier'));
                }
                $this->_setParam('identifier', '');
            }
            elseif (!isset($results[$this->_getParam('identifier')])) {
                $this->view->formErrors = array('Invalid Email Address: ' . $this->_getParam('identifier'));
                $this->_setParam('identifier', '');
            }
        }
        
        
        // If posted, validate, write to db and redirect to confirm
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $profile_alerts = new profile_models_profileAlerts();
            
            $data['profile_id'] = $this->target->id;
            $data['type'] = $this->_getParam('type');
            $data['confirm_code'] = sprintf("%06s", mt_rand(0,999999));
            
            if ($this->_getParam('type') == 2 || $this->_getParam('type') == 3) {
                // SMS Message or Voice
                $data['identifier'] = preg_replace(array('/\D/', '/^0+1*/'), '', $this->_getParam('identifier'));
                #Zend_Debug::Dump($data['identifier']);
            }
            else {
                $data['identifier'] = $this->_getParam('identifier');
            }
            
            if ($data['identifier']) {
                try {
                    if (!$this->db->fetchOne('SELECT 1 FROM profile_alerts WHERE active AND profile_id=? AND type=? AND identifier=?', array($this->target->id, $data['type'], $data['identifier']))) {
                        $result = $profile_alerts->insert($data);
                    }
                    else {
                        $this->view->formErrors = array('You cannot create a duplicate alert.');
                    }
                } catch (Exception $e) {}
                
                if (!isset($result)) {
                    if (!isset($this->view->formErrors)) {
                        $this->view->formErrors = array('That alert was not created successfully.');
                    }
                }
                else {
                    $this->log->ALERT(sprintf('New alert (%s) successfully created.', $this->_alertTypes[$this->_getParam('type')]['code']));
                    $maxCounter = $this->db->fetchOne('SELECT MAX(counter) FROM profile_alerts WHERE profile_id=? AND type=? AND identifier=?', array($this->target->id, $data['type'], $data['identifier']));
                    #$this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/confirm/?vmpd_nar=1&type='.$data['type']."&counter=$maxCounter&sendcode=1".($this->_getParam('redirect') ? '&redirect='.urlencode($this->_getParam('redirect')) : ''));
                    $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/confirm/type/' . $data['type'] . '/id/' . $maxCounter . "/?vmpd_nar=1&sendcode=1".($this->_getParam('redirect') ? '&redirect='.urlencode($this->_getParam('redirect')) : ''));
                }
            }
            else {
                $this->view->formErrors = array('Filtered identifier is invalid.');
            }
        }
        else {
            $form->populate($this->_getAllParams());
        }
        
        $this->view->form = $form;
        
        echo '<h1>Add A New Alert (<span class="netdown">BETA</span>)</h1>';
        if ($this->view->formErrors) {
            print '<div class="errors"><ul>';
            foreach ($this->view->formErrors as $message) {
                print "<li>$message</li>\n";
            }
            print '</ul></div>';
        }
        echo $form;
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function confirmAction()
    {
        $this->view->headTitle('Profile Alert Manager', 'PREPEND');
        $this->view->headTitle('Confirm A New Alert Type', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
        }
        
        // Retrieve the Alert - Resend Code If Necessary
        if ($this->_getParam('type') && $this->_getParam('id') && $this->_getParam('sendcode')) {
            # Create the Code
            $new_code = sprintf("%06s", mt_rand(0,999999));
            // Update
            if ($res = $this->db->update('profile_alerts', array('confirm_code' => $new_code), array(
                'confirmed ISNULL',
                $this->db->quoteInto('profile_id = ?', $this->target->id),
                $this->db->quoteInto('type = ?', $this->_getParam('type')),
                $this->db->quoteInto('counter = ?', $this->_getParam('id'))
            ))) {
                // Confirm Code Updated - Now Notify
                if ($newAlert = $this->db->fetchRow('SELECT * FROM profile_alerts pa, profile_profiles pp WHERE pa.profile_id=pp.id AND profile_id=? AND type=? and counter=?', array($this->target->id, $this->_getParam('type'), $this->_getParam('id')))) {
                    if ($this->_getParam('type') == 1) {
                        // Email
                        
                        $this->_helper->ViaMe->sendEmail($newAlert->identifier, null, 'New Alert Confirmation Code', "Confirmation Code: $new_code", null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                        
                        $this->view->formErrors = array('A new confirmation code has E-Mailed to you.  Please enter it below.');
                    }
                    elseif ($this->_getParam('type') == 2) {
                        // SMS Text Message
                        $sms = new ViaMe_Vm_Im_Plivo();
                        $response = $sms->send_message(array(array('key' => $newAlert->member_id, 'id' => $newAlert->identifier)), $new_code);
                        
                        if (isset($response[0]['response']['error'])) {
                            $this->view->formErrors = array('Your confirmation code may not have been successfully sent.  You may want to delete this alert and try your request again. ' . $response[0]['response']['error']);
                        }
                        else {
                            $this->view->formErrors = array('A new confirmation code has been sent to you.  Please enter it below.');
                        }
                    }
                    elseif ($this->_getParam('type') == 3) {
                        // Voice
                        $voice = new ViaMe_Vm_Im_Plivo();
                        
                        $response = $voice->make_call(array(array('key' => $newAlert->member_id, 'id' => $newAlert->identifier)), array(
                            'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak loop="2">Your confirmation code is: ' . implode(',', str_split($new_code)) . '<%2FSpeak><%2FResponse>',
                            'answer_method' => 'GET',
                            'time_limit' => '60'
                        ));
                        
                        if (isset($response[0]['response']['error'])) {
                            $this->view->formErrors = array('Your confirmation code may not have been successfully received.  You may want to delete this alert and try your request again. ' . $response[0]['response']['error']);
                        }
                        else {
                            $this->view->formErrors = array('A voice call was initiated with the confirmation code.  Please enter it below.');
                        }
                    }
                }
            }
            else {
                $this->view->formErrors = array('That alert could not be located.  Please try your request again.');
            }
        }
        
        $form = new Zend_Form();
        //$form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'alert_form',
                'method' => 'post',
                'action' => '?',
                'id' => 'alert_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.alert_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'confirmation_code' => array('Text', array(
                    'label' => 'Confirmation Code',
                    'description' => 'Enter the confirmation code you received to activate this alert type.',
                    'maxlength' => 32,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="confirmation code", message="The {label} field cannot be empty.", groups=[alert_form])',
                    #'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
                    #'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
                    'order' => 5,
                    'validators' => array(
                        array('StringLength', false, array(0, 32))
                    )
                )),
                'resend_code' => array('Button', array(
                    'label' => 'Re-Send Confirmation Code',
                    'description' => 'Re-send the confirmation code to your selected alert type.',
                    'onclick' => "location='?vmpd_nar=1&sendcode=1" .($this->_getParam('redirect') ? '&redirect='.urlencode($this->_getParam('redirect')) : ''). "';",
                    'order' => 10
                ))
            )
        ));
        
        $form->addDisplayGroup(array('confirmation_code', 'resend_code'), 'main');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Confirm Alert', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('alert_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        
        // If posted, validate, write to db and redirect to confirm
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            // Update
            if ($res = $this->db->update('profile_alerts', array('confirmed' => 't'), array(
                'confirmed ISNULL',
                $this->db->quoteInto('profile_id = ?', $this->target->id),
                $this->db->quoteInto('type = ?', $this->_getParam('type')),
                $this->db->quoteInto('counter = ?', $this->_getParam('id')),
                $this->db->quoteInto('confirm_code = ?', $this->_getParam('confirmation_code')),
            ))) {
                // Completed
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
            }
            else {
                $this->view->formErrors = array('That alert could not be confirmed.  Please try again.');
            }
        }
        #else {
        #    $form->populate($this->_getAllParams());
        #}
        
        echo '<h1>Confirm A New Alert Type (<span class="netdown">BETA</span>)</h1>';
        if ($this->view->formErrors) {
            print '<div class="errors"><ul>';
            foreach ($this->view->formErrors as $message) {
                print "<li>$message</li>\n";
            }
            print '</ul></div>';
        }
        echo $form;
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function activeAction()
    {
        // Toggle Alert Active
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE profile_alerts SET active=(NOT active) WHERE profile_id=? AND type=? AND counter=?", Array($this->target->id, $this->_getParam('type'), $this->_getParam('id')));
        }
        
        // Must redirect to cause a user reload
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
    
    
    public function deleteAction()
    {
        // Delete Alert
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE profile_alerts SET active=NULL WHERE profile_id=? AND type=? AND counter=?", Array($this->target->id, $this->_getParam('type'), $this->_getParam('id')));
        }
        
        // Must redirect to cause a user reload
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
}