<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Mail_WriteController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
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
        $this->view->headTitle('Mail', 'PREPEND');
        $this->view->headTitle('Write A New Mail Message', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/mails_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('priority'), 'options_priority', array('legend' => 'Priority'));
        $form->addDisplayGroup(array('self_destruct'), 'options_expiration', array('legend' => 'Expiration'));
        
        $form->getElement('to')->setDescription("<a href=\"javascript:void(null);\" onclick=\"YAHOO.util.Dom.setStyle(['cc-label', 'cc-element'], 'display', 'block'); YAHOO.util.Dom.setStyle(this, 'display', 'none');\">Show CC</a> <a href=\"javascript:void(null);\" onclick=\"YAHOO.util.Dom.setStyle(['bcc-label', 'bcc-element'], 'display', 'block'); YAHOO.util.Dom.setStyle(this, 'display', 'none');\">Show BCC</a>")->getDecorator('description')->setEscape(false);
        
        $form->addDisplayGroup(array('to', 'cc', 'bcc', 'subject', 'content'), 'main');
        
        $form->addElement('Submit', 'submit', array('label' => 'Send Mail', 'ignore' => true, 'order' => 997));
        $form->addElement('Submit', 'draft', array('label' => 'Save As Draft', 'ignore' => false, 'order' => 998));
        $form->addElement('Submit', 'template', array('label' => 'Save As Template', 'ignore' => false, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'draft', 'template', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect', 'contact_profile_id'), 'hidden');

        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('subject')->removeFilter('HTMLPurify');
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        
        if ((!$this->getRequest()->isPost() && $this->_getParam('cid')) || ($this->_getParam('function') == 'mailwrite')) {
            $cids = $this->_getParam('cid');
            if ($cids && !is_array($cids)) {
                $cids = array($cids);
            }
            
            if (count($cids)) {
                $select = $this->VMAH->getVSelectFromList($cids, false);
                if ($contacts = $this->db->fetchAll($select, null, Zend_Db::FETCH_ASSOC)) {
                    $this->view->inlineScript()->appendScript(
                        'var to_preload_data = ' . Zend_Json::encode($contacts) . ';' . "\n"
                    );
                }
            }
            
            $form->populate($this->_getAllParams());
        }
        // If posted, validate, write to db
        elseif ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('priority', 'subject', 'content');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
            }
            
            // Self-Destruct Field
            if ($this->_getParam('self_destruct')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('self_destruct'));
                $data['self_destruct'] = $date->get(Zend_Date::ISO_8601);
            }
            
            $temp_perm_ids = $valid_profiles = array();
            foreach (array('to', 'cc', 'bcc') as $send_type) {
                // Only send to valid users
                if ($this->_getParam($send_type . '_ids')) {
                    $select = $this->VMAH->getVSelectFromList($this->_getParam($send_type . '_ids'), true);
                    if ($profiles = $this->db->fetchAll($select)) {
                        $temp_ids = array();
                        
                        foreach ($profiles as $tempprof) {
                            $temp_ids[] = $tempprof->p_id;
                            $temp_perm_ids[] = $tempprof->p_id;
                            $valid_profiles[$tempprof->p_id] = $tempprof;
                        }
                        
                        $data["${send_type}_profile_id"] = $this->VMAH->iPgArray($temp_ids);
                    }
                }
            }
            
            $data['profile_id'] = $this->target->id;
            
            if ($this->_getParam('draft')) {
                $data['template_status'] = 'f';
            }
            elseif ($this->_getParam('template')) {
                $data['template_status'] = 't';
            }
            
            $this->db->beginTransaction();
            try {
                $mail_mails = new mail_models_mails();
                $mail_mails->insert($data);
                
                $this->log->ALERT('New mail message created.');
                
                if ($this->_getParam('isdraft')) {
                    // Delete the draft
                    $this->db->update('mail_mails', array('active' => null), array(
                        "template_status='f'",
                        $this->db->quoteInto('profile_id=?', $this->target->id),
                        $this->db->quoteInto('counter=?', $this->_getParam('isdraft'))
                    ));
                }
                
                $this->db->commit();
                
                if (!isset($data['template_status'])) {
                    try {
                        $new_message_counter = $this->db->fetchOne("SELECT MAX(counter) FROM mail_mails WHERE profile_id=?", $this->target->id);
                        
                        // Have to backwards selected the contact for the email
                        $select = $this->db->select()
                            ->from(array('obj' => 'profile_profiles'), array(
                                    'p_id' => 'id',
                                    'p_name' => 'name',
                                    'p_site_admin' => 'site_admin',
                                    'p_active' => 'active'
                                ))
                            ->where('obj.id=?', $this->target->id)
                            
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
                            
    
                        foreach (array_splice($temp_perm_ids, 0, 10) as $temp_id) {
                            $select1 = clone $select;
                            $select1->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $temp_id),
                                    array(
                                        'vc_status' => 'status',
                                        'vc_display' => 'display'
                                    )
                                );
                            
                            if ($tf = $this->db->fetchRow($select1)) {
                                $partial_array = array('profile' => $tf, 'new_message_counter' => $new_message_counter, 'tp' => $valid_profiles[$temp_id], 'data' => $data, 'internal' => $this->internal);                
                                $this->_helper->ViaMe->sendEmail(
                                    $valid_profiles[$temp_id]->b_email,
                                    $valid_profiles[$temp_id]->p_name,
                                    trim(strip_tags($this->view->ProfileDisplay(array('profile' => $tf, 'internal' => $this->internal)))) . ' Has Sent You A Message On ' . $this->community->display,
                                    $this->view->partial('write/emails/message.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display
                                );
                            }
                        }
      
                    } catch (Exception $e) { }
                }
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                
            } catch (Exception $e) {
                $this->db->rollBack();
                
                $this->view->formErrors = array('That message was not successfully sent.');
            }
        }
        elseif (!$this->getRequest()->isPost() && $this->_getParam('pid') && $this->_getParam('id')) {
            // If this is a reply, forward, draft, or template
            // Build the checks first into variables for easy insertion
            $to_me_check = $this->db->quoteInto('ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)', $this->target->id);
            $from_me_check = $this->db->quoteInto('profile_id=?', $this->target->id);
            $accepted_not_rejected_check = $this->db->quoteInto('ARRAY[?::bigint] <@ status_accepted OR status_rejected ISNULL OR NOT (ARRAY[?::bigint] <@ status_rejected)', $this->target->id);
            $not_self_destructed_check = 'self_destruct ISNULL OR self_destruct > now()';
            $not_deleted_check = $this->db->quoteInto('status_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_deleted)', $this->target->id);
            $not_perm_deleted_check = $this->db->quoteInto('status_perm_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_perm_deleted)', $this->target->id);
            $template_status_check['isnull'] = 'template_status ISNULL';
            $template_status_check['notnull'] = 'template_status NOTNULL';
            $template_status_check['false'] = "template_status='f'";
            $template_status_check['true'] = "template_status='t'";
            $active_status_check['isnull'] = 'active ISNULL';
            $active_status_check['notnull'] = 'active NOTNULL';
            $active_status_check['false'] = "active='f'";
            $active_status_check['true'] = "active='t'";
            
            if ($this->_getParam('pid') == $this->target->id) {
                // From Me
                $select = $this->db->select()
                    ->from('mail_mails', array('creation', 'profile_id', 'counter', 'to_profile_id', 'cc_profile_id', 'bcc_profile_id', 'priority', 'subject', 'content', 'template_status', 'i_status_read' => $this->db->quoteInto('(ARRAY[?::bigint] <@ status_read)', $this->target->id)))
                    ->where("(($from_me_check) AND ($active_status_check[notnull]) AND (($template_status_check[notnull]) OR ($not_self_destructed_check)))");
            } else {
                // To Me
                $select = $this->db->select()
                    ->from('mail_mails', array('creation', 'profile_id', 'counter', 'to_profile_id', 'cc_profile_id', 'priority', 'subject', 'content', 'template_status', 'i_status_read' => $this->db->quoteInto('(ARRAY[?::bigint] <@ status_read)', $this->target->id)))
                    ->where("(($to_me_check) AND ($accepted_not_rejected_check) AND ($not_self_destructed_check) AND ($not_perm_deleted_check) AND ($template_status_check[isnull]))");
            }
            
            $select
                ->where('profile_id=?', $this->_getParam('pid'))
                ->where('counter=?', $this->_getParam('id'))
                ->limit(1);

            $mail = $this->db->fetchRow($select);
            
            if ($mail) {
                if (isset($mail->template_status)) {
                    // Template or Draft
                    $form->populate((array) $mail);
                    foreach (array('to', 'cc', 'bcc') as $send_type) {
                        #$form->getElement($send_type)->setValue(preg_replace('/^\{(.*)\}$/', '$1', $mail->{"${send_type}_profile_id"}));
                        
                        $temparray = array();
                        $temparray = $this->VMAH->ePgArray($mail->{"${send_type}_profile_id"});
                        if (count($temparray)) {
                            $select = $this->VMAH->getVSelectFromList($temparray, false);
                            if ($contacts = $this->db->fetchAll($select, null, Zend_Db::FETCH_ASSOC)) {
                                $this->view->inlineScript()->appendScript(
                                    'var ' . $send_type . '_preload_data = ' . Zend_Json::encode($contacts) . ";\n"
                                );
                            }
                        }
                    }
                    
                    // If this is a draft, set so it can be deleted
                    if ($mail->template_status == false) {
                        $form->addElement('Hidden', 'isdraft', array('value' => $mail->counter));
                        $form->getDisplayGroup('hidden')->addElement($form->getElement('isdraft'));
                    }
                } else {
                    // Forward or Reply
                    if ($this->_getParam('reply')) {
                        #$form->getElement('to')->setValue($this->_getParam('tid'));
                        
                        $select = $this->VMAH->getVSelectFromList(array($mail->profile_id), false);
                        if ($contacts = $this->db->fetchAll($select, null, Zend_Db::FETCH_ASSOC)) {
                            $this->view->inlineScript()->appendScript(
                                'var to_preload_data = ' . Zend_Json::encode($contacts) . ";\n"
                            );
                        }
                        
                        if ($this->_getParam('replyall')) {
                            $temparray = array_diff(array_unique(array_merge($this->VMAH->ePgArray($mail->to_profile_id), $this->VMAH->ePgArray($mail->cc_profile_id))), array($this->target->id));
                            
                            if (count($temparray)) {
                                $select = $this->VMAH->getVSelectFromList($temparray, false);
                                if ($contacts = $this->db->fetchAll($select, null, Zend_Db::FETCH_ASSOC)) {
                                    $this->view->inlineScript()->appendScript(
                                        'var cc_preload_data = ' . Zend_Json::encode($contacts) . ";\n"
                                    );
                                }
                            }
                        }
                    
                        if (preg_match('/^Re:/', $mail->subject)) {
                            $form->getElement('subject')->setValue($mail->subject);
                        }
                        else {
                            $form->getElement('subject')->setValue('Re: ' . $mail->subject);
                        }
                    }
                    else {
                        $form->getElement('subject')->setValue('Fwd: ' . $mail->subject);
                    }
                    
                    $form->getElement('content')->setValue('<p><br /><br /></p><div style="border-left: 3px solid #00f; margin-top: 1em; padding-left: 10px;">' . $mail->content . '</div>');
                }
            }
        }
        elseif ($this->getRequest()->isPost()) {
            // Failed Post
            foreach (array('to', 'cc', 'bcc') as $send_type) {
                // Only send to valid users
                if ($this->_getParam($send_type . '_ids')) {
                    $select = $this->VMAH->getVSelectFromList($this->_getParam($send_type . '_ids'), true);
                    if ($profiles = $this->db->fetchAll($select)) {
                        $this->view->inlineScript()->appendScript(
                            'var ' . $send_type . '_preload_data = ' . Zend_Json::encode($profiles) . ";\n"
                        );
                    }
                }
            }
        }
        
        $this->view->form = $form;
    }
}