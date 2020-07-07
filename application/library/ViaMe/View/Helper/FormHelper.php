<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_FormHelper extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_FormHelper.phtml';
    protected $_moduleName = 'system';
    
    public function FormHelper($model = null)
    {
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
    }
}