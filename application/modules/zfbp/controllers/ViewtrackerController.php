<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Zfbp_ViewtrackerController extends ViaMe_Controller_Action
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
    
    
    public function init()
    {
        // Load up the registry variables
        $this->registryLoader($this);
        
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
    }
    
    public function preDispatch() { }
    
    public function indexAction()
    {
        $this->_updateCounter($this->_getParam('t'), $this->_getParam('i'), $this->_getParam('m'), $this->_getParam('x'), $this->_getParam('c'));
        
        return $this->getResponse()->setHeader('X-Accel-Redirect', '/spacer.gif');
    }
    
    
    private function _updateCounter($t = null, $i = null, $m = null, $x = null, $c = null)
    {
        // Synced with file/controllers/ViewController.php
        
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