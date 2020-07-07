<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_CarouselviewController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        if ($widget = $this->_getParam('widget')) {
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            #Number Visible, Reveal Amount, Circular, Vertical, Autoplay Interval, Content
            
            if ($params['dp_widgets']) {
                $this->view->widget = $widget;
                $this->view->params = $params;
            }
            else {
                return $this->_helper->viewRenderer->setNoRender();
            }
        }
    }
}