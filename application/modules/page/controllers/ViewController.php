<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Page_ViewController extends ViaMe_Controller_Default_View
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction()
    {
        parent::indexAction();
        
        /*
        // Load up all of the pages
        $which_space = strtolower($this->target->type) . '_id';
        
        $refs = $list = array();
        foreach ($this->db->fetchAll("SELECT title, counter, parent_id FROM page_pages WHERE active='t' AND $which_space=? AND matrix_counter=? ORDER BY counter, title", array($this->target->id, $this->_getParam('mid'))) as $data) {
            $thisref = &$refs[ $data->counter ];
        
            $thisref['data'] = $data;
            
            if (!$data->parent_id) {
                $list[ $data->counter ] = &$thisref;
            } else {
                $refs[ $data->parent_id ]['children'][ $data->counter ] = &$thisref;
            }
        }
        
        $this->view->page_links = $list;
        */
        if (isset($this->_modObject->page_code) && $this->_modObject->page_code) {
            if ($this->_modObject->page_code == 'PHP') {
                $this->_modObject->content = preg_replace('/^(\s|(<\?(php)+))*/', '', $this->_modObject->content);
            	$this->_modObject->content = preg_replace('/(\s|(\?>))*$/', '', $this->_modObject->content);
            	
                ob_start();
                eval($this->_modObject->content);                
                $this->_modObject->content = ob_get_contents();
                ob_end_clean();
            }
        }
        
        if ($this->_modObject->disable_layouts) {
            Zend_Layout::getMvcInstance()->disableLayout();
            echo $this->_modObject->content;
            return $this->_helper->viewRenderer->setNoRender();
        }
        
        if ($this->_modObject->disable_sublayouts) {
            $this->_helper->ViaMe->setSubLayout('default');
        }
        
        if ($this->_modObject->disable_cm) {
            echo $this->_modObject->content;
            return $this->_helper->viewRenderer->setNoRender();
        }
    }
}