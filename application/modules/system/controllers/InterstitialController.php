<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_InterstitialController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = '';
    
    
    public function indexAction()
    {
        //$this->view->headTitle('Advertisement', 'PREPEND');
        
        // Change Layout
        $this->_helper->ViaMe->setLayout('plain');
        
        if ($this->_getParam('id') && is_file($this->view->getScriptPath('interstitial/index-' . $this->_getParam('id') . '.phtml'))) {
            return $this->render('index-' . $this->_getParam('id'));
        }
    }
}
