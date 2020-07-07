<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_PrintEmailLink extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_PrintEmailLink.phtml';
    protected $_moduleName = 'system';
    
    public function PrintEmailLink($model = null)
    {
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
    }
}