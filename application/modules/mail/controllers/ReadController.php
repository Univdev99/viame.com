<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Mail_ReadController extends ViaMe_Controller_Action
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
        $this->view->headTitle('Mail', 'PREPEND');
        $this->view->headTitle('Read Mail Message', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        
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
        
        $select = $this->db->select()
            ->from('mail_mails', array('creation', 'profile_id', 'counter', 'to_profile_id', 'cc_profile_id', 'priority', 'subject', 'content', 'template_status', 'i_status_read' => $this->db->quoteInto('(ARRAY[?::bigint] <@ status_read)', $this->target->id), 'i_status_to_me' => $to_me_check))
            ->where('profile_id=?', $this->_getParam('pid'))
            ->where('counter=?', $this->_getParam('id'))
            ->limit(1);
        if ($this->_getParam('pid') == $this->target->id) {
            // From Me
            $select->where("(($from_me_check) AND (($template_status_check[notnull]) OR ($not_self_destructed_check)))");
        } else {
            // To Me
            $select->where("(($to_me_check) AND ($accepted_not_rejected_check) AND ($not_self_destructed_check) AND ($template_status_check[isnull]))");
        }
        
        $mail = $this->db->fetchRow($select);
        
        if ($mail) {
            if (isset($mail) && $mail->i_status_to_me && !$this->_getParam('fid') && (!isset($mail->i_status_read) || !$mail->i_status_read)) {
                // Don't need permissions check as we already did that to retrieve, set, and view the mail
                $this->db->query("UPDATE mail_mails SET status_read=array_distinct(status_read || ? ::bigint) WHERE profile_id=? AND counter=?", array($this->target->id, $this->_getParam('pid'), $this->_getParam('id')));
                
                if ($this->member->profile->total_new_mail_count > 0) {
                    $this->db->query("UPDATE mail_stats SET total_new_mail_count=total_new_mail_count - 1 WHERE profile_id=?", $this->target->id);
                    
                    if ($this->member->profile->id == $this->target->id) {
                        $this->member->profile->total_new_mail_count = $this->member->profile->total_new_mail_count - 1;
                    }
                }
            }
            
            // Need to load up the contacts
            $c_ids = array();
            foreach (array('profile_id', 'to_profile_id', 'cc_profile_id', 'bcc_profile_id') as $temp) {
                if (isset($mail->{$temp}) && $mail->{$temp}) {
                    $c_ids = array_merge($c_ids, $this->VMAH->ePgArray($mail->{$temp}));
                }
            }
            if (count($c_ids)) {
                $this->view->profiles = array();
                foreach ($this->db->query($this->VMAH->getVSelectFromList($c_ids, false)) as $profile) {
                    $this->view->profiles[$profile->p_id] = $profile;
                }
            }
            
            $this->view->folder = $this->_getParam('fid');
            $this->view->mail = $mail;
        }
        else {                    
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Invalid Mail Message',
                'hd2' => 'Could not read that mail message',
                'bd' => '<p class="error">An error has occurred. The mail message specified could not be found</p><p>Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }

    }
}