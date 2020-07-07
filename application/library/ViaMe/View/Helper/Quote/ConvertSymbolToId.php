<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_Quote_ConvertSymbolToId extends Zend_View_Helper_Abstract
{
    //protected $_fileName = 'partials/_Quote.phtml';
    //protected $_moduleName = 'quote';
    
    public function Quote_ConvertSymbolToId($symbols = null)
    {
        if ($symbols) {
            $quotes = new ViaMe_Vm_Quotes();
            return $quotes->verify($symbols);
        }
    }
}