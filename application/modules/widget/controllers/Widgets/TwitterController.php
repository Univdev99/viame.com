<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_TwitterController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        $this->_helper->viewRenderer->setNoRender();
        
        print 'Twitter Widget';
    }
}