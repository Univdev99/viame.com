<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_CM extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_CM.phtml';
    protected $_moduleName = 'system';
    
    public function CM($model = null, $alterFile = null)
    {
    	
    	
    	
        if ($alterFile) {
            $this->_fileName = 'partials/_CM' . $alterFile . '.phtml';
        }
        
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
    }
}