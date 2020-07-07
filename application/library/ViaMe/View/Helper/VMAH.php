<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_VMAH extends Zend_View_Helper_Abstract
{
    #protected $_fileName = 'partials/_ProfileDisplay.phtml';
    #protected $_moduleName = 'profile';
    
    protected $_VMAH = null;
    
    public function VMAH()
    {
        if (!$this->_VMAH && Zend_Controller_Action_HelperBroker::hasHelper('ViaMe')) {
            $this->_VMAH = Zend_Controller_Action_HelperBroker::getExistingHelper('ViaMe');
        }
        
        return $this->_VMAH;
    }
}