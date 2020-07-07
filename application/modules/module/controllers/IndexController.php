<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Module_IndexController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function indexAction()
    {
        $this->view->headTitle('Manage Modules', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        $select = $this->db->select()->from(array('x' => 'module_matrix'))
            ->where('x.active NOTNULL')
            // Placeholder
            ->join(array('m' => 'module_modules'), 'x.module_id = m.id', array('id' => 'id', 'm_display' => 'display', 'm_description' => 'description'))
            ->where('m.active NOTNULL')
            ->order(array('m.display', 'x.display', 'm.name', 'x.counter', 'm.id'));
        
        switch($this->target->type) {
            case 'VIA':
                $select->where('x.via_id=?', $this->target->id);
                break;
            case 'NET':
                $select->where('x.net_id=?', $this->target->id);
                break;
            default:
                $select->where('x.com_id=?', $this->target->id);
                break;
        }
        
        $this->view->modules = $this->db->fetchAll($select);
    }
}