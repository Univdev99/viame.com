<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Form_Decorator_ViewHelper extends Zend_Form_Decorator_ViewHelper
{
    const ATTRIB_NAME               = 'vm_display_modifier'; // Name of the attrib to look for
    const VALUE                     = 'value';               // Display the value only
    const VALUE_HIDDEN              = 'value_hidden';        // Display the value and a hidden variable
    const VALUE_ISSET               = 'value_isset';         // Display the value only if it is set
    const VALUE_ISSET_HIDDEN        = 'value_isset_hidden';  // Display the value and a hidden variable only if it is set
    
    public function render($content)
    {
        $element = $this->getElement();

        $view = $element->getView();
        if (null === $view) {
            require_once 'Zend/Form/Decorator/Exception.php';
            throw new Zend_Form_Decorator_Exception('ViewHelper decorator cannot render without a registered view object');
        }

        if (method_exists($element, 'getMultiOptions')) {
            $element->getMultiOptions();
        }

        $helper    = $this->getHelper();
        $separator = $this->getSeparator();
        $value     = $this->getValue($element);
        
        $attribs = $this->getElementAttribs();
        
        if (isset($attribs[self::ATTRIB_NAME]) &&
            (($attribs[self::ATTRIB_NAME] == self::VALUE || $attribs[self::ATTRIB_NAME] == self::VALUE_HIDDEN) || ($value !== '' && $value !== null))) {
            $elementContent = $value;
            
            if ($attribs[self::ATTRIB_NAME] == self::VALUE_HIDDEN || $attribs[self::ATTRIB_NAME] == self::VALUE_ISSET_HIDDEN) {
                unset($attribs[self::ATTRIB_NAME]);
                $elementContent .= $view->formHidden($this->getName(), $value, $attribs, $element->options);
            }
            
            unset($attribs[self::ATTRIB_NAME]);
        }
        else {
            unset($attribs[self::ATTRIB_NAME]);
            $elementContent = $view->$helper($this->getName(), $value, $attribs, $element->options);
        }
        
        switch ($this->getPlacement()) {
            case self::APPEND:
                return $content . $separator . $elementContent;
            case self::PREPEND:
                return $elementContent . $separator . $content;
            default:
                return $elementContent;
        }
    }
}
