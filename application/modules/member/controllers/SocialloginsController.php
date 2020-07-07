<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Member_SocialloginsController extends ViaMe_Controller_Action
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
        elseif (isset($this->member)) {
            $this->target->id = $this->member->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
        
        $this->view->headTitle('Member', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
    }
    
    
    public function indexAction()
    {
        // Social Logins
        // Menu Displayed In View Script
        $this->view->headTitle('Social Logins', 'PREPEND');
        
        $select = $this->db->select()->from(array('sli' => 'member_social_logins'))
            ->where('sli.member_id=?', $this->member->id)
            ->order(array(new Zend_Db_Expr("sli.provider='openid'"), 'sli.provider'));
        
        $this->view->slis = $this->db->fetchAll($select);
    }
    
    
    public function deleteAction()
    {
        if ($this->_getParam('provider') && $this->_getParam('id')) {
            $query = 'DELETE FROM member_social_logins WHERE member_id=? AND provider=? AND id=?';
        
            $this->db->query($query, Array($this->member->id, $this->_getParam('provider'), $this->_getParam('id')));
        }
        
        $this->_autoredirect('/member/sociallogins/');
    }
}
