<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_GroupController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction()
    {
        $this->view->headTitle('Contact Manager', 'PREPEND');
        $this->view->headTitle('Group Manager', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* FETCHED FROM SIDEBAR
        // Display Groups
        $select = $this->db->select()
            ->from(array('g' => 'contact_group_groups'),
                array(
                    '*',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('contact_group_groups', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('g.profile_id=?', $this->target->id)
            ->order(array('sort_order', 'g.counter', 'g.name'));
        
        $temp_groups = $temp_sorted_groups = array();
        foreach ($this->db->fetchAll($select) as $group) {
            $temp_groups[$group->sort_order] = $group;
        }
        
        $temp_sorted_keys = array_keys( $temp_groups );
        natsort( $temp_sorted_keys );
        
        foreach ($temp_sorted_keys as $key) {
            $temp_sorted_groups[] = $temp_groups[$key];
        }
        
        $this->view->groups = $temp_sorted_groups;
        */
    }
    
    
    public function createAction()
    {
        $this->view->headTitle('Contact Manager', 'PREPEND');
        $this->view->headTitle('Group Manager', 'PREPEND');
        $this->view->headTitle('Create A New Group', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/groupGroups_form.php';        
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        
        #$form->addElement('Textarea', 'captcha', array('label' => 'Captcha', 'ignore' => true, 'order' => 900, 'value' => 'Captcha Me'));
        #$form->getElement('captcha')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
        
        
        $form->addDisplayGroup(array('description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('parent_id'), 'options_parent', array('legend' => 'Parent Group'));
        
        $form->addDisplayGroup(array('name'), 'main');
        
        $form->addElement('Submit', 'submit_b', array('label' => 'Create Group', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit_b', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            #$form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        
        // Load up the existing groups for possible parent
        $groups[''] = '-- Select A Parent Group --';
        foreach ($this->db->fetchAll("SELECT counter, name, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('contact_group_groups', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM contact_group_groups WHERE active='t' AND profile_id=? ORDER BY sort_order, counter, name", array($this->target->id)) as $group) {
            $temp_groups[$group->sort_order] = $group;
        }
        if (isset($temp_groups)) {
            $temp_sorted_keys = array_keys( $temp_groups );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $groups[$temp_groups[$key]->counter] = (str_repeat(' -- ', substr_count($temp_groups[$key]->sort_order, '-'))) . $temp_groups[$key]->name;
            }
        }
        $form->getElement('parent_id')->setMultiOptions($groups);
        
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'description', 'parent_id');
            $params = (object) $form->getValues();
            foreach ($fields as $field) {
                if (isset($params->$field) && $params->$field !== '') {
                    $data[$field] = $params->$field;
                }
            }
            
            $data['profile_id'] = $this->target->id;
            
            try {
                $contact_group_groups = new contact_models_groupGroups();
                
                if (!$this->db->fetchOne('SELECT 1 FROM contact_group_groups WHERE profile_id=? AND lower(name)=lower(?)', array($this->target->id, $data['name']))) {
                    $result = $contact_group_groups->insert($data);
                }
                else {
                    $this->view->formErrors = array('You cannot create a group with a duplicate name.');
                }
            } catch (Exception $e) {}

            if (!isset($result)) {
                if (!isset($this->view->formErrors)) {
                    $this->view->formErrors = array('That group was not created successfully.');
                }
            }
            else {
                $this->log->ALERT(sprintf('New group (%s) successfully created.', $data['name']));
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
            }
        }
        
        $this->view->form = $form;
    }
    
    
    public function editAction()
    {
        $this->view->headTitle('Contact Manager', 'PREPEND');
        $this->view->headTitle('Group Manager', 'PREPEND');
        $this->view->headTitle('Edit Group', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) || (!$this->_getParam('id'))) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
        }
        
        require dirname(dirname(__FILE__)) . '/models/groupGroups_form.php';        
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        $form->addElement('Hidden', 'id', array('required' => true));

        $form->addDisplayGroup(array('description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('parent_id'), 'options_parent', array('legend' => 'Parent Group'));
        
        $form->addDisplayGroup(array('name'), 'main');
        
        $form->addElement('Submit', 'submit_b', array('label' => 'Update Group', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit_b', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect', 'id'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            #$form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        // Load up the existing groups for possible parent
        $groups[''] = '-- Select A Parent Group --';
        /*
        $select = $this->db->select()
            ->from(array('g' => 'contact_group_groups'),
                array(
                    'counter',
                    'name',
                    'sort_order' => "array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('contact_group_groups', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order"
                )
            )
            ->where('g.profile_id=?', $this->target->id)
            ->where('g.counter<>?', $this->_getParam('id'))
            ->where('g.active=?', 't')
            ->order(array('sort_order', 'g.counter', 'g.name'));
        */
        foreach ($this->db->fetchAll("SELECT counter, name, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('contact_group_groups', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM contact_group_groups WHERE active='t' AND profile_id=? ORDER BY sort_order, counter, name", array($this->target->id)) as $group) {
            $temp_groups[$group->sort_order] = $group;
        }
        if (isset($temp_groups)) {
            $temp_sorted_keys = array_keys( $temp_groups );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $groups[$temp_groups[$key]->counter] = (str_repeat(' -- ', substr_count($temp_groups[$key]->sort_order, '-'))) . $temp_groups[$key]->name;
                ###$element->addMultiOption($object->id, '<span class="' . $otherObject->id . '">' . $object->name) . '</span>';
            }
            $form->getElement('parent_id')->setAttrib('disable', array($this->_getParam('id')));
        }
        $form->getElement('parent_id')->setMultiOptions($groups);
        
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('name', 'description', 'parent_id');
            $values = $form->getValues();
            foreach ($fields as $field) {
                if (isset($values[$field])) {
                    if ($values[$field] === '') { $data[$field] = null; }
                    else { $data[$field] = $values[$field]; }
                }
            }
            
            try {
                $contact_group_groups = new contact_models_groupGroups();
                $contact_group_groups->update($data, array(
                    $contact_group_groups->getAdapter()->quoteInto('profile_id = ?', $this->target->id),
                    $contact_group_groups->getAdapter()->quoteInto('counter=?', $this->_getParam('id'))
                ));
                
                $this->log->ALERT("Group information successfully updated.");
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
                
            } catch (Exception $e) {
                #$profiler = $this->db->getProfiler();
                #$query = $profiler->getLastQueryProfile();
                #echo $query->getQuery();
            
                $this->log->ALERT("Group information update failed!");
                
                $this->view->formErrors = array('Group update failed.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            $form->populate((array) $this->db->fetchRow('SELECT * FROM contact_group_groups WHERE profile_id=? AND counter=?', array($this->target->id, $this->_getParam('id'))));
        }        
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Group';
        $this->_helper->viewRenderer->renderScript('group/create.phtml');
    }
    
    /* MOVED TO MULTI CONTROLLER
    public function addAction()
    {
        if ($this->_getParam('id')) {
            if ($this->_getParam('cid') || $this->_getParam('v')) {
                //$contact_group_members = new contact_models_groupMembers();
                //$contact_group_members->insert(array('profile_id' => $this->target->id, 'group_counter_id' => $this->_getParam('id'), 'member_profile_id' => $this->_getParam('cid')));
                
                // Handle Add To Group
                $which = array();
                if ($this->_getParam('cid')) {
                    foreach ((is_array($this->_getParam('cid')) ? $this->_getParam('cid') : array($this->_getParam('cid'))) as $vc) {
                        if ($vc) {
                            $which[] = $this->db->quoteInto('(contact_profile_id=?)',  $vc);
                        }
                    }
                }
                
                if (count($which) || $this->_getParam('v')) {
                    // To Me
                    $query = 'INSERT INTO contact_group_members (profile_id, group_counter_id, member_profile_id) SELECT ';
                    $query .= $this->db->quoteInto('?, ', $this->target->id);
                    $query .= $this->db->quoteInto('?, contact_profile_id FROM contact_contacts WHERE ', $this->_getParam('id'));
                    
                    $query .= $this->db->quoteInto("profile_id=? AND status='t' AND active='t'", $this->target->id);
                    
                    if (count($which)) {
                        $query .= ' AND (' . implode(' OR ', $which) . ')';
                    }
                    
                    // Avoid Duplicates to avoid failure
                    $query .= $this->db->quoteInto(' AND contact_profile_id NOT IN (SELECT member_profile_id FROM contact_group_members WHERE profile_id=?', $this->target->id);
                    $query .= $this->db->quoteInto(' AND group_counter_id=?)', $this->_getParam('id'));
                    
                    try {
                        $this->db->query($query);
                    } catch (Exception $e) { }
                }
                #print $query;
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');  
            }
            else {
                $select = $this->db->select()
                    ->from(array('g' => 'contact_group_members'))
                    ->where("g.active='t'")
                    ->where('g.group_counter_id=?', $this->_getParam('id'))
                    ->where('g.profile_id=?', $this->target->id)
                    ->join(array('p' => 'profile_profiles'), 'g.member_profile_id = p.id', array('name'))
                    ->where("p.active='t'")
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array());
                
                print '<b>Group Members</b><br>';
                foreach ($this->db->fetchAll($select) as $contact) {
                    print "<a href=\"../../../remove/id/" .$this->_getParam('id'). "/?cid=$contact->member_profile_id\">$contact->name</a><br>";
                }
                
                print '<hr>';
                
                $select = $this->db->select()
                    ->from(array('c' => 'contact_contacts'))
                    ->where("c.status='t' AND c.active='t'")
                    ->where('c.profile_id=?', $this->target->id)
                    ->join(array('p' => 'profile_profiles'), 'c.contact_profile_id = p.id', array('name'))
                    ->where("p.active='t'")
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array());
                
                foreach ($this->db->fetchAll($select) as $contact) {
                    print '<br><a href="?cid=' . $contact->contact_profile_id . "\">$contact->name</a><br>";
                }
            }
        }
        
        
        #$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        #$viewRenderer->setNoRender(true);
	    $this->_helper->viewRenderer->setNoRender();
        #$this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
    
    
    public function removeAction()
    {
        if ($this->_getParam('id')) {
            if ($this->_getParam('cid') || $this->_getParam('v')) {
                $contact_group_members = new contact_models_groupMembers();
                
                // Handle Add To Group
                $which = array();
                if ($this->_getParam('cid')) {
                    foreach ((is_array($this->_getParam('cid')) ? $this->_getParam('cid') : array($this->_getParam('cid'))) as $vc) {
                        if ($vc) {
                            $which[] = $this->db->quoteInto('(member_profile_id=?)',  $vc);
                        }
                    }
                }
                
                $where[] = $contact_group_members->getAdapter()->quoteInto('profile_id=?', $this->target->id);
                $where[] = $contact_group_members->getAdapter()->quoteInto('group_counter_id=?', $this->_getParam('id'));
                
                if (count($which)) {
                    $where[] = '(' . implode(' OR ', $which) . ')';
                }
                
                $contact_group_members->delete($where);
                                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
            }
            else {
                $select = $this->db->select()
                    ->from(array('g' => 'contact_group_members'))
                    ->where("g.active='t'")
                    ->where('g.group_counter_id=?', $this->_getParam('id'))
                    ->where('g.profile_id=?', $this->target->id)
                    ->join(array('p' => 'profile_profiles'), 'g.member_profile_id = p.id', array('name'))
                    ->where("p.active='t'")
                    ->join(array('b' => 'member_members'), 'p.member_id = b.id', array());
                
                print '<b>Group Members</b><br>';
                foreach ($this->db->fetchAll($select) as $contact) {
                    print "<a href=\"?cid=$contact->member_profile_id\">$contact->name</a><br>";
                }
            }
        }
        
        #$viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        #$viewRenderer->setNoRender(true);
	    $this->_helper->viewRenderer->setNoRender();
        #$this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
    */
    
    public function activeAction()
    {
        // Toggle Group Active
        if ($this->_getParam('id')) {
            $this->db->query("UPDATE contact_group_groups SET active=(NOT active) WHERE profile_id=? AND counter=?", Array($this->target->id, $this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
    
    
    public function deleteAction()
    {
        // Delete Profile
        if ($this->_getParam('id')) {
            $this->db->query("DELETE FROM contact_group_groups WHERE profile_id=? AND counter=?", Array($this->target->id, $this->_getParam('id')));
        }
        
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/' . $this->getRequest()->getControllerName() . '/');
    }
}