<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_SystemController extends ViaMe_Controller_Action
{
    /*
        Controllers called from the action view helper will:
            - Not trigger any plugins
            - Not have params from the original request: only those passed in.
                To access the originals, use $this->params->paramname
            - Will intiate correctly the ViaMe_Action class but remember above when modifying that class.
        
        System Controllers are called via actionstack so the TYPE is already set.  No need for
            preDispatch checks unless member status is needed
    */
    
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch() { }
    
    
    public function statusAction()
    {   
        if (isset($this->member)) {
            $this->view->member = $this->member;
        }
        else {
            // Load the Form
            require dirname(dirname(__FILE__)) . '/models/members_form.php';
            // Remove Elements Early to Reduce Iteration
            unset($form_config['elements']['first_name']);
            unset($form_config['elements']['middle_name']);
            unset($form_config['elements']['last_name']);
            unset($form_config['elements']['gender']);
            unset($form_config['elements']['dob']);
            unset($form_config['elements']['postal_code']);
            unset($form_config['elements']['password_confirm']);
            unset($form_config['elements']['timezone']);
            unset($form_config['elements']['country']);
            unset($form_config['elements']['language']);
            
            $form = new Zend_Form();
            $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
            $form->setOptions($form_config);
            $form->setMethod('post');
            $form->setAction('/member/login/');
            
            $form->setElementFilters(array('StringTrim'));
            
            // Formwide Settings Should Be Done Early
            $form->removeDecorator('Errors');
            #$form->removeDecorator('HtmlTag');
            
            // Invidual Element Additions
            if (isset($this->cookie->{$this->config->auth->cookie_name->login})) {
                $form->getElement('email')->setValue($this->cookie->email);
                if (isset($this->cookie->persist) && $this->cookie->persist) {
                    $form->addElement('hidden', 'persistent', array('value' => 1));
                    $form->getElement('persistent')->setDecorators( array('ViewHelper') );
                }
            }
            else {
                $form->addElement('Checkbox', 'persistent', array('label' => $this->translate->_('Persistent'), 'order' => 995));
                $form->getElement('persistent')->setLabel($this->translate->_('Keep me signed in for 2 weeks') . '.')->setDecorators(array('ViewHelper', array('Label', array('placement' => 'append')), array('HtmlTag', array('style' => 'font-size: xx-small;'))));
            }
            $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
            $form->getElement('vmpd_npr')->setDecorators( array('ViewHelper') );
            $form->addElement('Submit', 'submit', array('label' => $this->translate->_('Sign In'), 'order' => 1000));
            
            // Individual Element Overrides
            $form->getElement('email')->setAttrib('style', 'width: 98%;');
            $form->getElement('password')->setAttrib('style', 'width: 98%;');
            $form->getElement('submit')->setDecorators(array('ViewHelper', array('HtmlTag', array('style' => 'text-align: center;'))));
            
            $this->view->form = $form;
        } 
    }
}
