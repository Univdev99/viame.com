<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_AjaxController extends ViaMe_Controller_Default_Ajax
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
    
    
    public function init()
    {
        parent::init();
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $contextSwitch
            ->addActionContext('checkemail', array())
            ->addActionContext('checkemailverify', array())
            ->setAutoJsonSerialization(false)
            ->initContext();
            
        #$this->_helper->viewRenderer->setNoRender(false);
        #$this->_helper->layout->enableLayout();
    }
    
    
    public function checkemailAction()
    {
        #$contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        #if ($contextSwitch->getCurrentContext() == 'json') { }
        
        if ($this->_getParam('email') && $this->db->fetchOne("SELECT 1 FROM member_members WHERE lower(email)=lower(?)", array($this->_getParam('email')))) {
            echo 'true';
        }
        else {
            echo 'false';
        }
    }
    
    
    public function checkemailverifyAction()
    {
        #$contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        #if ($contextSwitch->getCurrentContext() == 'json') { }
        
        if ($this->_getParam('email')) {
            $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
            #$SMTP_Validator->debug = true;
            $results = $SMTP_Validator->validate(array($this->_getParam('email')), $this->community->email);
            
            if (isset($results[$this->_getParam('email')]) && ($results[$this->_getParam('email')][0] === false)) {
                // Came back false
                echo 'false';
            }
            else {
                echo 'true';
            }
        }
        else {
            echo 'false';
        }
    }
}