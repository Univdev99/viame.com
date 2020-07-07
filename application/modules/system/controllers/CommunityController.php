<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class System_CommunityController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    public function preDispatch() { }
    
    
    public function listAction()
    {   
        #$system_models_communities = new system_models_communities();
        if ($communities = $this->db->fetchAll("SELECT * FROM system_communities WHERE name <> 'default' AND active='t' AND parent_id=? ORDER BY display", $this->community->id)) {
            $this->view->communities = $communities;
        }
        else {
            $this->_helper->viewRenderer->setNoRender();
            return;
        }
    }
}