<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_ProfileDisplay extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_ProfileDisplay.phtml';
    protected $_moduleName = 'profile';
    
    public function ProfileDisplay($model = null)
    {
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
    }
}