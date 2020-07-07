<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_SEO_Urlify extends Zend_View_Helper_Abstract
{
    #protected $_fileName = 'partials/_CM.phtml';
    #protected $_moduleName = 'system';
    
    public function SEO_Urlify($content = null, $separator = '-')
    {
        if ($content) {
            $content = preg_replace(Array("/'/", '/\.+/', '/[^\w\d\s]+/'), Array('', ' ', ' '), trim($content));
            $content = preg_replace('/\s+/', $separator, trim($content));
        }
        
        return $content;
    }
}