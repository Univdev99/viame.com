<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_AddthisController extends ViaMe_Controller_Action
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
        $this->_helper->viewRenderer->setNoRender();
        
        if ($widget = $this->_getParam('widget')) {
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            
            $pubid = (isset($params['dp_publisher_id']) && $params['dp_publisher_id'] ? $params['dp_publisher_id'] : 'none');
            
            echo <<<EOM
<script>
var addthis_disable_flash = true;
var addthis_config = { data_use_flash: false };
</script>
<div style="text-align: center;"><a href="http://www.addthis.com/bookmark.php?v=250&pub=$pubid" onmouseover="return addthis_open(this, '', '[URL]', '[TITLE]')" onmouseout="addthis_close()" onclick="return addthis_sendto()" rel="nofollow"><img src="https://s7.addthis.com/static/btn/lg-share-en.gif" width="125" height="16" alt="Bookmark and Share" style="border:0"/></a><script type="text/javascript" src="https://s7.addthis.com/js/250/addthis_widget.js?pub=$pubid"></script></div>
EOM;
        }
    }
}
