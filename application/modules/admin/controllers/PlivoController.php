<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_PlivoController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch()
    {
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) { return $this->_denied(); }
        else {
            $this->view->headTitle('Admin', 'PREPEND');
            $this->view->headTitle('Plivo', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    public function outboundcallAction()
    {
        $this->view->headTitle('Make An Outbound Call', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        $origins = array(
            '16194923225' => 'Elite Opportunity'
        );
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'plivo_make_outbound_call',
                'method' => 'post',
                'id' => 'plivo_make_outbound_call',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.plivo_make_outbound_call] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'origin' => array('Select', array(
                    'label' => 'Origin Number',
                    'description' => 'The phone number you would like it to appear you are calling from.',
                    'required' => true,
                    'order' => 5
                )),
                'mynum' => array('Text', array(
                    'label' => 'Your Phone Number',
                    'description' => 'Your complete phone number including the country code.',
                    'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="my number", message="The {label} field cannot be empty.", groups=[plivo_make_outbound_call])',
                    'order' => 10,
                    'validators' => array(
                        array('StringLength', false, array(0, 256))
                    )
                )),
                'destination' => array('Text', array(
                    'label' => 'Destination Phone Number',
                    'description' => 'Complete destination phone number including the country code.',
                    'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="phone number", message="The {label} field cannot be empty.", groups=[plivo_make_outbound_call])',
                    'order' => 15,
                    'validators' => array(
                        array('StringLength', false, array(0, 256))
                    )
                ))
            )
        ));
        $form->getElement('origin')->setMultiOptions($origins);
        
        $form->addElement('Submit', 'submit', array('label' => 'Make Call', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('plivo_make_outbound_call').vivregval_canceled = true;"));
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, make call
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            echo '<p class="netup"><strong>Connecting your call... Please answer your phone...</strong></p>';
            
            $plivo = new ViaMe_Vm_Im_Plivo();
            $response = $plivo->getApi()->make_call(array(
                'from' => $this->_getParam('origin'),
                'to' => $this->_getParam('mynum'),
                'ring_timeout' => '10',
                'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak language="en-GB">Your call is now being connected to '.implode(' ,', str_split($this->_getParam('destination'))).'.<%2FSpeak><Wait length="2" %2F><Dial callerId="'.$this->_getParam('origin').'" callerName="'.(isset($origins[$this->_getParam('origin')]) && $origins[$this->_getParam('origin')] ? $origins[$this->_getParam('origin')] : 'Levelogic').'"><Number>'.$this->_getParam('destination').'<%2FNumber><%2FDial><%2FResponse>',
                'answer_method' => 'GET'
            ));
            #Zend_Debug::Dump($response);
        }
        
        echo $form;
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    public function listrecordingsAction()
    {
        $this->view->headTitle('List Recordings', 'PREPEND');
        
        $plivo = new ViaMe_Vm_Im_Plivo();
        
        if ($this->_getParam('delete_recording_id')) {
            $response = $plivo->delete_recording(array('recording_id' => $this->_getParam('delete_recording_id')));
        }
        
        $response = $plivo->get_recordings();
        
        foreach ($response['response']['objects'] as $recording) {
            echo '<p>' . $recording['add_time'] . ' : <a href="' . $recording['recording_url'] . '" target="_blank">Listen</a> - <a href="?delete_recording_id=' . $recording['recording_id'] . '" onclick="return confirm(' . "'Are you sure you want to delete this recording?'" . ');">Delete</a></p>';
        }
        
        echo '<p><a href="/admin/">Continue...</a></p>';
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    public function deleterecordingsAction()
    {
        $this->view->headTitle('Delete Recordings', 'PREPEND');
        
        $plivo = new ViaMe_Vm_Im_Plivo();
        
        $response = $plivo->get_recordings();
        
        foreach ($response['response']['objects'] as $recording) {
            $res2 = $plivo->delete_recording(array('recording_id' => $recording['recording_id']));
        }
        
        echo '<p><a href="/admin/">Continue...</a></p>';
        return $this->_helper->viewRenderer->setNoRender();
    }
}