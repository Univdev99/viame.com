<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Acl_PasswordController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/');
        }
        
        // Load the Form
        $form_config = array(
            'attribs' => array(
                'name' => 'acl_password_form',
                'id' => 'acl_password_form',
                'class' => 'form'
            ),
            'elements' => array(
                'acl_password' => array('Password', array(
                    'label' => 'Password',
                    'description' => 'Secret Password',
                    'required' => true,
                    'order' => 5
                )),
                // Hidden Fields
                'type' => array('Hidden', array('required' => true)),
                'id' => array('Hidden', array('required' => true)),
                'mod' => array('Hidden', array('required' => true)),
                'mid' => array('Hidden', array('required' => true)),
                'iid' => array('Hidden', array('required' => true)),
                'counter' => array('Hidden', array('required' => true))
            )
        );
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        
        $form->setMethod('post');
        
        $form->addElement('Submit', 'submit', array('label' => 'Submit', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect'))); 
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            switch($this->_getParam('type')) {
                case 'VIA':
                    $which = 'via_id';
                    break;
                case 'NET':
                    $which = 'net_id';
                    break;
                default:
                    $which = 'com_id';
                    break;
            }
            
            $query = "INSERT INTO acl_passwords ($which, module_id, matrix_counter, item_counter, acl_counter, password, profile_id) SELECT $which, module_id, matrix_counter, item_counter, counter, w_password, ? FROM acl_acls WHERE $which=? AND module_id=? AND matrix_counter=? AND item_counter=? and counter=? AND w_password=?";
            
            $this->db->query($query, array($this->member->profile->id, $this->_getParam('id'), $this->_getParam('mod'), $this->_getParam('mid'), $this->_getParam('iid'), $this->_getParam('counter'), $this->_getParam('acl_password')));
            
            $this->_autoredirect('/');
        }
        
        $form->populate($this->_getAllParams());
        $this->view->form = $form;
    }
}
