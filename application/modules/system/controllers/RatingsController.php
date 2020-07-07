<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_RatingsController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_subModule = null;
    protected $_masked = null;
    protected $_masked_type = null;
    protected $_masked_which = null;
    protected $_masked_counter = null;
    
    
    // Already Checked Prior to Getting Called
    public function preDispatch() {
        if (!isset($this->internal->subModule)) {
            return $this->_denied();
        }
        else {
            $this->_subModule = $this->internal->subModule;
        }
        
        if (($this->_subModule->community_mask || $this->_subModule->network_mask || $this->_subModule->profile_mask) && ($this->_subModule->mask_counter)) {
            $this->_masked = true;
            
            if ($this->_subModule->community_mask) {
                $this->_masked_type = 'COM';
                $this->_masked_which = $this->_subModule->community_mask;
            }
            elseif ($this->_subModule->network_mask) {
                $this->_masked_type = 'NET';
                $this->_masked_which = $this->_subModule->network_mask;
            }
            elseif ($this->_subModule->profile_mask) {
                $this->_masked_type = 'VIA';
                $this->_masked_which = $this->_subModule->profile_mask;
            }
            
            $this->_masked_counter = $this->_subModule->masked_counter;
        }
    }
    
    
    public function rateAction()
    {
        if (!$this->internal->subModule->_modObject->allow_ratings) {
            return $this->_denied();
        }
        
        // Already Have _subModule loaded per preDispatch
        $system_ratings = new system_models_ratings();
        $data = array();
            
        if ($this->_masked) {
            if ($this->_subModule->community_mask) {
                $data['com_id'] = $this->_subModule->community_mask;
            }
            elseif ($this->_subModule->network_mask) {
                $data['net_id'] = $this->_subModule->network_mask;
            }
            elseif ($this->_subModule->profile_mask) {
                $data['via_id'] = $this->_subModule->profile_mask;
            }
            
            $data['matrix_counter'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->_subModule->mask_counter);
        }
        else {
            if ($this->_subModule->com_id) {
                $data['com_id'] = $this->_subModule->com_id;
            }
            elseif ($this->_subModule->net_id) {
                $data['net_id'] = $this->_subModule->com_id;
            }
            elseif ($this->_subModule->via_id) {
                $data['via_id'] = $this->_subModule->via_id;
            }
            
            $data['matrix_counter'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->_subModule->counter);
        }
        
        $data['module_id'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->_subModule->module_id);
            
        $data['item_counter'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->internal->params->id);
        $data['rating'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->internal->params->rating);
        $data['profile_id'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->member->profile->id);
        
        if (isset($this->internal->params->ronc) && $this->internal->params->ronc) { // Rating on Comment
            $data['table_name'] = 'system_comments';
            $data['counter'] = $this->internal->params->ronc;
        }
        elseif (isset($this->internal->params->table_name) && $this->internal->params->table_name && isset($this->internal->params->counter) && $this->internal->params->counter) {
            $data['table_name'] = $system_ratings->getAdapter()->quoteInto('?', $this->internal->params->table_name);
            $data['counter'] = $system_ratings->getAdapter()->quoteInto('?', $this->internal->params->counter);
        }
        
        /*
        if ($this->target->type && $this->target->id) {
            $system_ratings = new system_models_ratings();
            
            $data = array();
            
            switch($this->target->type) {
                case 'VIA':
                    $data['via_id'] = $this->target->id;
                    break;
                case 'NET':
                    $data['net_id'] = $this->target->id;
                    break;
                default:
                    $data['com_id'] = $this->target->id;
                    break;
            }
            
            $data['module_id'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->_subModule->module_id);
            $data['matrix_counter'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->internal->params->mid);
            $data['item_counter'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->internal->params->id);
            $data['rating'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->internal->params->rating);
            $data['profile_id'] = $system_ratings->getAdapter()->quoteInto('?', (int) $this->member->profile->id);
            
            if ($this->internal->params->ronc) { // Rating on Comment
                $data['table_name'] = 'system_comments';
                $data['counter'] = $this->internal->params->ronc;
            }
            elseif ($this->internal->params->table_name && $this->internal->params->counter) {
                $data['table_name'] = $system_ratings->getAdapter()->quoteInto('?', $this->internal->params->table_name);
                $data['counter'] = $system_ratings->getAdapter()->quoteInto('?', $this->internal->params->counter);
            }
            
            try {
                $system_ratings->insert($data);
            } catch (Exception $e) { }
        }
        */
        
        // Insert the rating
        try {
            $system_ratings->insert($data);
        } catch (Exception $e) { }
    
        $this->_helper->viewRenderer->setNoRender();
        
        $this->_autoredirect($this->target->pre . '/');
    }
}