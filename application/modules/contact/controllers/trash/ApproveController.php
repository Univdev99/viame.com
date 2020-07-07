<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_ApproveController extends ViaMe_Controller_Action
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
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction() {
        $this->view->headTitle('Manage Your Contacts', 'PREPEND');
        $this->view->headTitle('Approve Contact Request', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Approve Contact Request
        if ($this->_getParam('id')) {
            
            $contact_contacts = new contact_models_contacts();
            
            $data = array(
                'status' => 't',
                'message' => NULL
            );
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->target->id);
            $where[] = $contact_contacts->getAdapter()->quoteInto("status ISNULL");
            
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->_getParam('id'));
            
            if ($contact_contacts->update($data, $where)) {
                // Reciprocate Add Contact - No Customization
                if ($this->_getParam('r')) {
                    try {
                        $this->db->query("INSERT INTO contact_contacts (profile_id, contact_profile_id, status, active) SELECT contact_profile_id, profile_id, NULLIF(auto_reciprocate, 'f'::bool), active FROM contact_contacts WHERE contact_profile_id=? AND profile_id=?", array($this->target->id, $this->_getParam('id')));
                    } catch (Exception $e) { }
                }
                
                try {
                    // Have to backwards selected the contact
                    $select = $this->db->select()
                        ->from(array('obj' => 'profile_profiles'), array(
                                'p_id' => 'id',
                                'p_name' => 'name',
                                'p_site_admin' => 'site_admin',
                                'p_active' => 'active'
                            ))
                        
                        ->join(array('b' => 'member_members'), 'obj.member_id = b.id',
                            array(
                                'b_email' => 'email',
                                'b_site_admin' => 'site_admin',
                                'b_active' => 'active',
                            )
                        )
                        
                        ->join(array('c' => 'system_communities'), 'obj.community_id = c.id',
                            array(
                                'c_id' => 'id',
                                'c_name' => 'name',
                                'c_hostname' => 'hostname'
                            )
                        );
                        
                    $select1 = clone $select;
                    $select1->where('obj.id=?', $this->target->id)
                        ->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->_getParam('id')),
                            array(
                                'vc_status' => 'status',
                                'vc_display' => 'display'
                            )
                        );
                    if ($tp = $this->db->fetchRow($select1)) {
                        $select2 = clone $select;
                        $select2->where('obj.id=?', $this->_getParam('id'));
                        
                        if ($tf = $this->db->fetchRow($select2)) {
                            $partial_array = array('profile' => $tp, 'tf' => $tf, 'data' => $data, 'internal' => $this->internal);                
                            $this->_helper->ViaMe->sendEmail(
                                $tf->b_email,
                                $tf->p_name,
                                ((isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) ? $this->via->name : $this->member->profile->name) . ' Has Approved Your Contact Request On ' . $this->community->display,
                                $this->view->partial('approve/emails/approve.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display
                            );
                        }
                    }
                } catch (Exception $e) { }
            }
        }
        
        // Force redirect (not autoredirect) as we will more than like be coming from the view controller
        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
    }
}