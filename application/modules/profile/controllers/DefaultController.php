<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_DefaultController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    protected $_mustBeOwner = true;
    
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->member_id;
        }
        else {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        // Set Default Profile
        if ($this->_getParam('id')) {
            $this->db->beginTransaction();
            try {
                $result = $this->db->query("UPDATE profile_profiles SET default_profile='t' WHERE active='t' AND member_id=? AND id=?", Array($this->target->id, $this->_getParam('id')));
                if ($result->rowCount()) {
                    $this->db->query("UPDATE profile_profiles SET default_profile='f' WHERE member_id=? AND id<>?", Array($this->target->id, $this->_getParam('id')));
                }
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
            }
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}