<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_IndexController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Manage Widgets', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        $select = $this->db->select()->from(array('x' => 'widget_matrix'))
            ->where('x.active NOTNULL')
            // Placeholder
            ->join(array('w' => 'widget_widgets'), 'x.widget_id = w.id', array('id' => 'id', 'description' => 'description', 'w_display' => 'display'))
            ->where('w.active NOTNULL')
            ->where('x.' . strtolower($this->target->type) . '_id=?', $this->target->id)
            ->order(array('w.display', 'x.display', 'w.name', 'x.counter', 'w.id'));
        
        $this->view->widgets = $this->db->fetchAll($select);
    }
}