<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_TextAds extends Zend_View_Helper_Abstract
{
    //protected $_fileName = 'partials/_TextAds.phtml';
    //protected $_moduleName = 'system';
    
    public function TextAds($model = null, $alterFile = null)
    {
        /*
        if ($alterFile) {
            $this->_fileName = 'partials/_CM' . $alterFile . '.phtml';
        }
        
        return $this->view->partial($this->_fileName, $this->_moduleName, $model);
        */
        
        return;
        
        /* Infolinks */
        /*   SCN's Infolinks il_ad_id=276851 */
        /*
        if (isset($this->view->internal->community->il_ad_id) && $this->view->internal->community->il_ad_id) {
            $this->view->inlineScript()->appendScript('var infolink_pid=' . $this->view->internal->community->il_ad_id . ";\nvar infolink_wsid=0;\n");
            $this->view->inlineScript()->offsetSetFile(100, 'https://resources.infolinks.com/js/infolinks_main.js');
        }
        */
        
        
        /* Vibrant Media */
        /*   SCN's Vibrant Media il_ad_id=22921 */
        if (isset($this->view->internal->community->il_ad_id) && $this->view->internal->community->il_ad_id) {
            $this->view->inlineScript()->offsetSetFile(100, 'https://levelogic.us.intellitxt.com/intellitxt/front.asp?ipid=' . $this->view->internal->community->il_ad_id);
        }
    }
}
