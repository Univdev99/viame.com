<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_CodeController extends ViaMe_Controller_Action
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
        //$this->_helper->viewRenderer->setNoRender();
        
        if ($widget = $this->_getParam('widget')) {
            $params = null;
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            #$this->view->params = $params;
            
            if (isset($params['dp_language']) && $params['dp_language'] == 'PHP' && isset($params['dp_code']) && $params['dp_code']) {
            	$params['dp_code'] = preg_replace('/^(\s|(<\?(php)+))*/', '', $params['dp_code']);
            	$params['dp_code'] = preg_replace('/(\s|(\?>))*$/', '', $params['dp_code']);
            	
                eval($params['dp_code']);
            }
        }
        
        $this->_helper->viewRenderer->setNoRender(true);
    }
}