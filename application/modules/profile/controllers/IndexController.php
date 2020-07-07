<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_IndexController extends ViaMe_Controller_Action
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
    }
    
    
    public function indexAction() {
        $this->view->headTitle('Manage Your Profiles', 'PREPEND');
        
        $form = new Zend_Form(array(
            'attribs' => array(
                'method' => 'get',
                'class' => 'form',
                'action' => ((isset($this->target->pre) && $this->target->pre ? $this->target->pre : '') . '/profile/create/')
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Create New Profile', 'ignore' => true, 'order' => 999));
        $form->addDisplayGroup(array('submit'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        $this->view->form = $form;
        
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Display Profiles
        $select = $this->db->select()
            ->from(array('p' => 'profile_profiles'))
            ->where('p.member_id=?', $this->target->id)
            ->where('p.active NOTNULL')
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id', array('community' => 'display', 'community_name' => 'name'))
            ->order(array('base DESC', 'name'));
        $this->view->profiles = $this->db->fetchAll($select);
    }
}