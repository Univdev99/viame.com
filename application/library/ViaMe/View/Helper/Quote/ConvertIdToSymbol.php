<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_Quote_ConvertIdToSymbol extends Zend_View_Helper_Abstract
{
    //protected $_fileName = 'partials/_Quote.phtml';
    //protected $_moduleName = 'quote';
    
    protected static $_quoteClass = null;
    
    public static function setQuoteClass($quoteClass)
    {
        self::$_quoteClass = $quoteClass;
    }

    public static function getQuoteClass()
    {
        if (!self::$_quoteClass) {
            self::$_quoteClass = new ViaMe_Vm_Quotes();
        }

        return self::$_quoteClass;
    }

    
    public function Quote_ConvertIdToSymbol($ids = null)
    {
        if ($ids) {
            $quotes = self::getQuoteClass();
            return $quotes->lookupById($ids);
        }
    }
}