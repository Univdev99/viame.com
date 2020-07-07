<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Info_IndexController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    public function indexAction()
    {
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/infos_form.php';
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        
        foreach ($form_subforms_config as $name => $config) {
            $form->addSubForm(new Zend_Form_SubForm($config), $name);
        }
        
        
        $form->setMethod('post');

        // Matrix Counter
        #$form->addElement('Hidden', 'mid', array('required' => true));
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Article', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        
        // Redirects
        #$form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        #$form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            print "IS VALID!";
        }
        
        $this->view->form = $form;
    }
}
