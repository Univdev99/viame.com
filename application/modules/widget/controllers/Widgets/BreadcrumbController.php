<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_BreadcrumbController extends ViaMe_Controller_Action
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
        
        if (($widget = $this->_getParam('widget')) && ($this->breadcrumbs)) {
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            
            $no_titles = (isset($params['dp_no_titles']) && $params['dp_no_titles']);
            $separator = $this->view->escape(isset($params['dp_seperator']) ? $params['dp_seperator'] : ' : ');
            $link_last = (isset($params['dp_link_last']) && $params['dp_link_last']);
            
            $final = array();
            
            echo '<ul class="breadcrumbs straight" xmlns:v="http://rdf.data-vocabulary.org/#">';
            for ($i = 0; $i < count($this->breadcrumbs); $i++) {
                echo '<li class="';
                if ($i == 0) { echo 'first '; }
                elseif ($i == (count($this->breadcrumbs) - 1)) { echo 'last '; }
                if ($i % 2) { echo 'odd'; }
                else { echo 'even'; }
                if ($i == (count($this->breadcrumbs) - 1)) { echo ' current'; }
                echo '" typeof="v:Breadcrumb">';
                
                #if ($i > 0) {
                #    echo '<span>' . $separator . '</span>';
                #}
                
                $link = $this->breadcrumbs[$i]['url'];
                $title = $this->view->escape((!$no_titles && $this->breadcrumbs[$i]['title'] ? $this->breadcrumbs[$i]['title'] : $this->breadcrumbs[$i]['simple']));
                
                if ($link_last || ($i < (count($this->breadcrumbs) - 1))) {
                    echo "<a property=\"v:title\" rel=\"v:url\" href=\"$link\" title=\"$title\">$title</a>";
                }
                else {
                    // Last One
                    echo $title;
                }
                
                if ($i < (count($this->breadcrumbs) - 1)) {
                    echo '<span class="bc_separator">' . $separator . '</span>';
                }
                
                echo '</li>';
            }
            
            echo '</ul>';
        }
    }
}
