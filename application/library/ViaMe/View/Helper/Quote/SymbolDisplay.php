<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_Quote_SymbolDisplay extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_SymbolDisplay.phtml';
    protected $_moduleName = 'quote';
    
    public function Quote_SymbolDisplay($model = null)
    {
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
    }
}