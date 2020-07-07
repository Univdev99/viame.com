<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class System_GridController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        #if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
        #    $this->target->id = $this->via->id;
        #}
        #else {
        #    $this->target->id = $this->member->profile->id;
        #    $this->_minPrivilege = null;
        #    $this->target->acl->owner = true;
        #    $this->target->space = $this->member->profile;
        #}
        
        $this->_helper->contextSwitch()
            ->addActionContext('add', 'json')
            ->addActionContext('move', 'json')
            ->addActionContext('delete', 'json')
            ->setAutoJsonSerialization(false)
            ->initContext();

        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function addAction() {
        switch($this->target->type) {
            case 'VIA':
                $table = 'profile_profiles';
                break;
            case 'NET':
                $table = 'network_networks';
                break;
            case 'COM':
                $table = 'system_communities';
                break;
        }
        
        // Add a New Widget To the Grid
        if ($this->_getParam('id') && $this->target->id) {
            $section = $this->_getParam('section', 'c1');
            $position = $this->_getParam('position');
            $query = "UPDATE $table SET grid_$section = (";
            
            if ($position > 0) {
                $query .= "grid_$section [array_lower(grid_$section, 1):($position-1)]";
            }
            else {
                $query .= "grid_$section";
            }
            $query .= $this->db->quoteInto(" || ARRAY[?]", $this->_getParam('id'));
            if ($position > 0) {
                $query .= " || grid_$section [$position:array_upper(grid_$section, 1)]";
            }
            
            $query .= $this->db->quoteInto(") WHERE id=?", $this->target->id);
            try { $this->db->getConnection()->exec($query); } catch (Exception $e) { }
        }
    }
    
    
    public function moveAction() {
        // Move a Widget In the Grid
        if ($this->_getParam('id') && $this->_getParam('section') && $this->target->id) {
            $this->deleteAction();
            $this->addAction();
        }
    }
    
    
    public function deleteAction() {
        switch($this->target->type) {
            case 'VIA':
                $table = 'profile_profiles';
                break;
            case 'NET':
                $table = 'network_networks';
                break;
            case 'COM':
                $table = 'system_communities';
                break;
        }
        
        // Delete a Widget From the Grid
        // NOTE: Have to construct manually as array_except function re-orders the array
        
        if ($this->_getParam('id')&& $this->target->id) {
            $to_update = array();
            
            foreach (array('hd', 'ft', 'cx', 'c1', 'c2', 'c3', 'c4') as $section) {
                if (isset($this->target->space->{"grid_$section"}) && ($this->target->space->{"grid_$section"}) && (strpos($this->target->space->{"grid_$section"}, $this->_getParam('id')))) {
                    $to_update{$section} = '{' . implode(',', array_diff(explode(',', preg_replace('/^\{(.*)\}$/', '${1}', $this->target->space->{"grid_$section"})), array($this->_getParam('id')))) . '}';
                }
            }
            
            if ($to_update) {
                $parts = array();
                foreach ($to_update as $section => $val) {
                    $parts[] = $this->db->quoteInto("grid_$section = ?", $val);
                }
                
                $query = "UPDATE $table SET " . implode(',', $parts) . $this->db->quoteInto(" WHERE id=?", $this->target->id);
                
                try { $this->db->getConnection()->exec($query); } catch (Exception $e) { }
            }
        }
    }
    
    
    public function removeAction() {
        // Remove from the grid
        #$this->deleteAction();
        
        list($type, $spec, $counter) = explode('-', $this->_getParam('id'));
        
        if (strtolower($type) == 'w' && isset($this->view->internal->widget_widgets[$spec])) {
            #return $this->_forward('index', 'active', 'widget', array('wid' => $spec, 'id' => $counter));
            return $this->_forward('index', 'edit', 'widget', array('wid' => $spec, 'id' => $counter, 'widget' => false, 'override' => true));
        }
        elseif (strtolower($type) == 'm' && isset($this->view->internal->module_modules[$spec])) {
            return $this->_forward('index', 'edit', 'module', array('mid' => $spec, 'id' => $counter, 'widget' => false, 'override' => true));
        }
    }
    
    
    #public function shadeAction() {
    #    // Toggle Widget Shade
    #    
    #}
    
    
    public function postDispatch()
    {
        if (!$this->_getParam('format')) {
            #$this->_autoredirect($this->target->pre . '/');
        }
    }
}