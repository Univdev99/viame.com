<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_CreateController extends ViaMe_Controller_Action
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
    
    
    public function indexAction() {
        $this->view->headTitle('Contact Manager', 'PREPEND');
        $this->view->headTitle('Add A New Contact', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /*
            Moved this to the beginning to bypass loading of the form and other ancillary
            functions.  Also, didn't use cancel->isChecked(), as that requires form load.
        */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        /*
            Need to check and see if contact is already formed in reverse and auto-reciprocate is set.  If
            so, no need for a message and status is automatically true
            Also, probably need to check if contact is already set or is forbidden.
            Also, check the ACL on the target
        */
               
        require dirname(dirname(__FILE__)) . '/models/contacts_form.php';        
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        /*
        $form->addElement('Textarea', 'name', array('label' => 'Profile Name', 'order' => 1, 'value' => 'Profile Name'));
        $form->getElement('name')
            ->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN)
            ->setDecorators( array('ViewHelper', array('HtmlTag', array('tag' => 'dd', 'style' => 'font-weight: bold;')), array('Label', array('tag' => 'dt'))) );
        */
        
        $form->addDisplayGroup(array('display', 'description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('auto_reciprocate'), 'options_reciprocate', array('legend' => 'Auto-Reciprocate'));
        
        $form->addDisplayGroup(array('contact', 'message'), 'main');
        
        $form->addElement('Submit', 'submit_b', array('label' => 'Create Contact', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit_b', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect', 'contact_profile_id'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            $form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $contact_contacts = new contact_models_contacts();
            $fields = array('contact_profile_id', 'display', 'description', 'message', 'auto_reciprocate');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
            }
            
            $contact_ids = $this->_getParam('contact_ids');
            $data['contact_profile_id'] = preg_replace('/^.-/', '', $contact_ids[0]);

            $data['profile_id'] = $this->target->id;
            
            try {
                $contact_contacts->insert($data);
                
                try {
                    // Have to backwards selected the contact for the email
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
                    $select1->where('obj.id=?', $data['profile_id'])
                        ->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $data['contact_profile_id']),
                            array(
                                'vc_status' => 'status',
                                'vc_display' => 'display'
                            )
                        );
                    if ($tp = $this->db->fetchRow($select1)) {
                        $select2 = clone $select;
                        $select2->where('obj.id=?', $data['contact_profile_id']);
                        
                        if ($tf = $this->db->fetchRow($select2)) {
                            $partial_array = array('profile' => $tp, 'tf' => $tf, 'data' => $data, 'internal' => $this->internal);                
                            $this->_helper->ViaMe->sendEmail(
                                $tf->b_email,
                                $tf->p_name,
                                ((isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) ? $this->via->name : $this->member->profile->name) . ' Wants To Add You As A Contact On ' . $this->community->display,
                                $this->view->partial('create/emails/request.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display
                            );
                        }
                    }
                } catch (Exception $e) { }
                        
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That contact was not successfully created.');
            }
            
        }
        elseif (!$this->getRequest()->isPost()) {
            if ($this->_getParam('id')) {
                // Sync This Select With the Widget
                $select = $this->VMAH->getVSelectFromList(array('V-' . $this->_getParam('id')), false);
                if ($contact = $this->db->fetchRow($select, null, Zend_Db::FETCH_ASSOC)) {
                    $this->view->inlineScript()->appendScript(
                        'var contact_preload_data = [' . Zend_Json::encode($contact) . '];' . "\n"
                    );
                }
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Add A New Contact';
    }
}