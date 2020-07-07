<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Quote_QrirrmiController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction() {
        if ($this->_getParam('id') && $this->_getParam('qrire') && strstr($this->_getParam('qrire'), '@') && $this->_getParam('qrirn') && $this->_getParam('qrirfq')) {
            $this->_helper->ViaMe->sendEmail(
                'jmangubat@levelogic.com',
                'Joji Mangubat',
                'Request For More Company Information (' . $this->_getParam('id') . ')',
                'Name: ' . $this->_getParam('qrirn') . "<br />\n<br />\n" . 
                  'E-Mail: <a href="mailto:' . $this->_getParam('qrire') . '">' . $this->_getParam('qrire') . "</a><br />\n<br />\n" . 
                  'Phone #: ' . $this->_getParam('qrirp') . "<br />\n<br />\n" . 
                  'Message: ' . $this->_getParam('qrirm', 'No message') . "<br />\n<br />\n",
                null,
                $this->_getParam('qrire'),
                $this->_getParam('qrirn')
            );
            
            echo 'Thanks!  Somebody will get back with your shortly.';
        }
        else {
            echo 'There were errors with the information you submitted.';
        }
        
        $this->_helper->viewRenderer->setNoRender();
    }
}