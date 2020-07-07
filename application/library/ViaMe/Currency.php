<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Currency extends Zend_Currency
{
    public function __construct($options = null, $locale = null)
    {
        if (is_array($options)) {
            $this->setFormat($options);
        } else if (Zend_Locale::isLocale($options, false, false)) {
            $temp    = $locale;
            $locale  = $options;
            $options = $temp;
        }
        
        // To fallback to locale with a region
        $locale = Zend_Locale::findLocale($locale);
        if (!strpos($locale, '_')) {
            foreach (Zend_Locale::getOrder() as $key => $val) {
                if (strpos($key, '_')) {
                    $locale = $key;
                    break;
                }
            }
        }

        $this->setLocale($locale);
        
        // Get currency details
        if (!isset($options['currency']) || !is_array($options)) {
            $this->_options['currency'] = self::getShortName($options, $this->_options['locale']);
        }

        if (!isset($this->_options['name']) || !is_array($options)) {
            $this->_options['name']     = self::getName($options, $this->_options['locale']);
        }

        if (!isset($this->_options['symbol']) || !is_array($options)) {
            $this->_options['symbol']   = self::getSymbol($options, $this->_options['locale']);
        }

        if (($this->_options['currency'] === null) and ($this->_options['name'] === null)) {
            // require_once 'Zend/Currency/Exception.php';
            throw new Zend_Currency_Exception("Currency '$options' not found");
        }

        // Get the format
        if (!empty($this->_options['symbol'])) {
            $this->_options['display'] = self::USE_SYMBOL;
        } else if (!empty($this->_options['currency'])) {
            $this->_options['display'] = self::USE_SHORTNAME;
        }
    }
}