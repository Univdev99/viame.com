<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_ArticleController extends ViaMe_Controller_Action
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
            $this->view->headTitle('Article Spinner', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    public function indexAction()
    {
        $this->view->headTitle('Spin A New Article', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'article_form',
                'method' => 'post',
                'id' => 'article_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.article_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'symbols' => array('Text', array(
                    'label' => 'Symbols',
                    'description' => 'Comma Seperated (10 Symbols Max)',
                    'class' => 'vmfh_acss multiple',
                    'order' => 10
                )),
                'pr' => array('Checkbox', array(
                    'label' => 'Press Release',
                    'description' => 'Style article like a press release.',
                    'value' => 1,
                    'order' => 20
                ))
            )
        ));
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Spin Article', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('article_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            echo 'Need to search and list Articles.  Then, on unsub, confirm it, then unsub it and send email to member.';    
        }
        else {
            $form->populate($this->_getAllParams());
            echo $form;
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}