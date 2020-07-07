<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Acl_ActiveController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        // Toggle ACL Active
        if ($this->_getParam('id')) {
            $acl_acls = new acl_models_acls();
            $row = $acl_acls->find(
                    ($this->target->type == 'COM' ? $this->target->id : 0),
                    ($this->target->type == 'NET' ? $this->target->id : 0),
                    ($this->target->type == 'VIA' ? $this->target->id : 0),
                    $this->_getParam('mod', 0),
                    $this->_getParam('mid', 0),
                    $this->_getParam('iid', 0),
                    $this->_getParam('id')
                )->current();
                
            $row->active = ($row->active ? 'f' : 't');
            $row->save();
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
    
    
    public function targetAction() {
        // Toggle ACL On/Off For Target
        if ($this->_getParam('iid') && $this->_getParam('mid') && $this->_getParam('mod')) {
            // Single Item
            foreach ($this->module_modules as $module) {
                if ($module->id == $this->_getParam('mod')) {
                    $table_name = $module->name.'_models_'.$module->name.'s';
                    $table = new $table_name();
                    
                    $row = $table->find(
                        ($this->target->type == 'COM' ? $this->target->id : 0),
                        ($this->target->type == 'NET' ? $this->target->id : 0),
                        ($this->target->type == 'VIA' ? $this->target->id : 0),
                        $this->_getParam('mid'),
                        $this->_getParam('iid')
                    )->current();
                    
                    break;
                }
            }
        }
        elseif ($this->_getParam('mid') && $this->_getParam('mod')) {
            // Single Module
            $table = new module_models_matrix();
            
            $row = $table->find(
                ($this->target->type == 'COM' ? $this->target->id : 0),
                ($this->target->type == 'NET' ? $this->target->id : 0),
                ($this->target->type == 'VIA' ? $this->target->id : 0),
                $this->_getParam('mod'),
                $this->_getParam('mid')
            )->current();
        }
        else {
            // Entire Space
            if ($this->target->type == 'VIA') { $table = new profile_models_profiles(); }
            elseif ($this->target->type == 'NET') { $table =  new network_models_networks(); }
            elseif ($this->target->type == 'COM') { $table = new system_models_communities(); }
            
            $row = $table->find($this->target->id)->current();
        }
        
        if (isset($row)) {
            switch ($this->_getParam('status')) {
                case 'off' :
                    $row->acl = null;
                    break;
                case 'ont' :
                    $row->acl = 't';
                    break;
                case 'onf' : 
                    $row->acl = 'f';
                    break;
            }
            
            $row->save();
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}