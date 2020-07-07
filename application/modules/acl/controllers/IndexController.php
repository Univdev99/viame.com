<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Acl_IndexController extends ViaMe_Controller_Action
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
        // Display ACLs
        switch($this->target->type) {
            case 'VIA' :
                $selector = 'via_id';
                $this->view->status = $this->via->acl;
                break;
            case 'NET' :
                $selector = 'net_id';
                $this->view->status = $this->network->acl;
                break;
            default :
                $selector = 'com_id';
                $this->view->status = $this->community->acl;
                break;
        }
        
        $select = $this->db->select()
            ->from(array('a' => 'acl_acls'), array('a.module_id', 'a.matrix_counter', 'a.item_counter', 'count(*) AS count'))
            ->where("a.$selector=?", (int) $this->target->id)
            ->where("a.active NOTNULL")
            ->group(array('a.module_id', 'a.matrix_counter', 'a.item_counter'));
        
        $statement = $this->db->query($select);
        $acls = array();
        while ($row = $statement->fetch()) {
            $acls[$row->module_id][$row->matrix_counter][$row->item_counter] = $row->count;
        }
        $this->view->acls = $acls;
    }
}