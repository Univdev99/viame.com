<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_ActiveController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function indexAction() {
        // Toggle Profile Active
        if ($this->_getParam('mid') && $this->_getParam('id')) {
            $query = "UPDATE module_matrix SET active=(NOT active) WHERE module_id=? AND counter=?";
            switch($this->target->type) {
                case 'VIA':
                    $query .= ' AND via_id=?';
                    break;
                case 'NET':
                    $query .= ' AND net_id=?';
                    break;
                default:
                    $query .= ' AND com_id=?';
                    break;
            }
        
            $this->db->query($query, Array($this->_getParam('mid'), $this->_getParam('id'), $this->target->id));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}
