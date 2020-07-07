<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class File_ViewController extends ViaMe_Controller_Default_View
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
    
    
    /*
        All Done in the Default
        
        library/ViaMe/Controller/Default/View
     */
    
    
    public function checkAction()
    {
        if ($this->_modObject->public_location) {
            #$this->_redirect('http://' . $this->config->static_host . '/public_files' . ($this->_modObject->title ? '/' . $this->view->escape($this->view->SEO_Urlify($this->_modObject->title)) : '/No-Title') . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id . '/' . urlencode($this->_modObject->file_name));
            $this->_redirect($this->config->upload->public_server . ($this->_modObject->title ? '/' . $this->view->escape($this->view->SEO_Urlify($this->_modObject->title)) : '/No-Title') . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id . '/' . urlencode($this->_modObject->file_name));
        }
        else {
            $this->_updateCounter(strtolower($this->target->type), $this->target->id, $this->target->currentModule->module_id, $this->target->currentModule->counter, $this->_modObject->counter);
             
            $this->_helper->layout->disableLayout();
            $this->_helper->viewRenderer->setNoRender();
            $this->getResponse()->clearBody();
            $this->getResponse()->clearHeaders();
            $this->getResponse()
                ->setHeader('X-Accel-Redirect', '/files/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id)
                ->setHeader('Content-Type', (isset($this->_modObject->file_type) && $this->_modObject->file_type ? $this->_modObject->file_type : ''))
                ->setHeader('Accept-Ranges', 'none ')
                ->setHeader('Content-Disposition', '')
                ->setHeader('Cache-Control', '')
                ->setHeader('Expires', '');
        }
    }
    
    
    public function postDispatch() {
        // Do this to clear the view image setting - on regular view
        //  Manual update on check action only
    }
    
    
    private function _updateCounter($t = null, $i = null, $m = null, $x = null, $c = null)
    {
        //  Sync this up with zfbp/controllers/ViewtrackerController.php
        
        $params = array('t' => 'type', 'i' => 'type_id', 'm' => 'module_id', 'x' => 'matrix_counter', 'c' => 'item_counter');
        
        if ($t && $i && $m && $x && $c) {
            $view_counter_table = 'module_views_counts';
            
            try {
                // Insert
                $data = array();
                $data[strtolower($t) . '_id'] = $i;
                foreach (array('m', 'x', 'c') as $key) {
                    $data[$params[$key]] = ${$key};
                }                
                    
                if (isset($this->member->profile->id)) { $data['profile_id'] = $this->member->profile->id; }
                if (isset($_SERVER['REMOTE_ADDR'])) { $data['ip_address'] = $_SERVER['REMOTE_ADDR']; }
                
                $this->db->insert($view_counter_table, $data);
            } catch (Exception $e) { }
        }
    }
}