<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_EmailController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_WRITE;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
        
    public function indexAction()
    {
        $this->view->headTitle('Email Page', 'PREPEND');
        
        
        // Link and redirect url is SELF with no to_email=1 paramater
        if ($this->_getParam('url')) {
            $link = $this->_getParam('url');
        }
        else {
            $link = $this->getRequest()->getServer('SCRIPT_URI');
            $qs = $this->getRequest()->getServer('QUERY_STRING');
            $qs = preg_replace(array('/&*to_email=[^&]*/', '/&*redirect=[^&]*/', '/&&/'), array('', '', '&'), $qs);
            if ($qs) {
                $link .= "?$qs";
            }
        }
        
        $from = $this->member->first_name . ' ' . ($this->member->middle_name ? $this->member->middle_name . ' ' : '') . $this->member->last_name;
        
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            return $this->_autoredirect($link);
        }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'system_email_form',
                'id' => 'system_email_form',
                'class' => 'form condensed',
                'onsubmit' => 'return YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.system_email_form] });' // Set in the form view script
            ),
            'elements' => array(
                'from' => array('Text', array(
                    'label' => "From",
                    'ignore' => true,
                    'order' => 5
                )),
                'email' => array('Text', array(
                    'label' => "To",
                    'required' => true,
                    'description' => "Email address of person you are sending this to",
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="to", message="The {label} field cannot be empty.", groups=[system_email_form])',
                    'order' => 10
                )),
                'subject' => array('Text', array(
                    'label' => "Subject",
                    'required' => true,
                    'value' => 'Check Out This Content',
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="subject", message="The {label} field cannot be empty.", groups=[system_email_form])',
                    'order' => 15
                )),
                'message' => array('Textarea', array(
                    'label' => 'Message',
                    'class' => 'vmfh_simple_textarea',
                    'value' => "<p>Here's a link to some content that I wanted to share with you...</p><p><a href=\"$link\">$link</a></p><p>$from</p>",
                    'order' => 20
                )),
                'link' => array('Text', array(
                    'label' => 'Link',
                    'ignore' => true,
                    'order' => 25
                )),
                'submit' => array('Submit', array(
                    'label' => 'Send Email',
                    'description' => 'Send Email',
                    'ignore' => true,
                    'order' => 30
                )),
                'cancel' => array('Submit', array(
                    'label' => 'Cancel',
                    'description' => 'Cancel',
                    'onClick' => "document.getElementById('system_email_form').vivregval_canceled = true;",
                    'order' => 35
                ))
            )
        ));
        
        $form->setMethod('post');
        #$form->setAction($this->target->pre . '/member/update/email/');
        
        #$form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        $form->getElement('from')->setValue("\"$from\" &lt;" . $this->member->email . '&gt;')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN);
        $form->getElement('link')->setValue($link)->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN);
        
        // Set Some Validators
        $form->getElement('email')->addValidator('EmailAddress');
        
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        $form->addDisplayGroup(array('from', 'email', 'subject', 'message', 'link'), 'email_group', array('legend' => 'Email'));
        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
        
        // If posted, validate and update db 
        if ($this->getRequest()->isPost()&& ($form->isValid($this->_getAllParams()))) {
            // We use _getAllParams so we can include key
            $values = $this->_getAllParams();
            
            $this->_helper->ViaMe->sendEmail($values['email'], null, $values['subject'], $values['message'] . "<p>Link: $link</p>", null, $this->member->email, $from);
            
            return $this->_autoredirect($link);
        }
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        echo $this->view->CM(array(
            'class' => 'cm decorated padded',
            'hd' => 'Email Link',
            'hd2' => 'Share content with people you know',
            'bd' => $form
        ));
        return $this->_helper->viewRenderer->setNoRender();
    }
}