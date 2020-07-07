<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Network_IndexController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        $this->view->headTitle('Manage Your Networks', 'PREPEND');
        
        $form = new Zend_Form(array(
            'attribs' => array(
                'method' => 'get',
                'class' => 'form',
                'action' => ((isset($this->target->pre) && $this->target->pre ? $this->target->pre : '') . '/network/create/')
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Create New Network', 'ignore' => true, 'order' => 999));
        $form->addDisplayGroup(array('submit'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        $this->view->form = $form;
        
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        
        // Display Networks
        $select = $this->db->select()
            ->from(array('n' => 'network_networks'),
                array(
                    '*',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('network_networks', 'parent_id', 'id', n.id, 't') AS a)), '-') AS sort_order",
                    'long_category' => "(SELECT array_to_string(array_accum(category), ' : ') FROM network_networks WHERE id IN (SELECT * from recursive_find('network_networks', 'parent_id', 'id', n.id, 't')))"
                )
            )
            ->where('n.active NOTNULL')
            ->join(array('p' => 'profile_profiles'), 'n.profile_id = p.id', array())
            ->where('p.active=?', 't')
            ->order(array('sort_order', 'n.id', 'n.name'));
        
        switch($this->target->type) {
            case 'VIA':
                #$select->where('n.public <> ?', 't');
                $select->where('p.id=?', $this->target->id);
                break;
            case 'NET':
                #$select->where('p.commmunity_id=?', $this->community->id);
                $select->where('n.parent_id=?', $this->target->id);
                break;
            default:
                #$select->where('n.public=?', 't');
                $select->where('n.community_id=?', $this->target->id);
                break;
        }
        
        $this->view->networks = $this->db->fetchAll($select);
    }
}