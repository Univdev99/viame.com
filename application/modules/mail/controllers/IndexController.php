<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Mail_IndexController extends ViaMe_Controller_Action
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
        #$this->view->headTitle('Folder Manager', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        
        // List Messages - Inbox, Drafts, Templates, Sent, Trash, or Folder
        /*
        Check                       Inbox       Drafts      Templates       Sent        Trash       Folder
        -----
        To Me                       X                                                   
                                                                                        VARY        VARY + JOIN
        From Me                                 X           X               X
        
        
        Already Accepted            X                       
            or Not Rejected
        
        Not Self Destructed         X                                       X           X           X
        
        Not Deleted                 X                                                   !
        
        Not Perm Deleted            X                                                   X if to me
        
        Template Status             ISNULL      ='f'        ='t'            ISNULL      ISNULL
        
        Active Status                           ='t'        ='t'            ='t'        NOT NULL
        */
        
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
        $display_in_trash_check = "display_in_trash='t'";
        $active_status_check['isnull'] = 'active ISNULL';
        $active_status_check['notnull'] = 'active NOTNULL';
        $active_status_check['false'] = "active='f'";
        $active_status_check['true'] = "active='t'";
        
        $select = $this->db->select()
            ->from(array('m' => 'mail_mails'),
                array('creation', 'profile_id', 'counter', 'priority', 'subject', 'i_status_read' => $this->db->quoteInto('(ARRAY[?::bigint] <@ status_read)', $this->target->id), 'i_status_to_me' => $to_me_check)
            );
        
        if (!$this->_getParam('fid')) {
            // To Me - Inbox Only
            $select
                ->where($to_me_check)
                ->where($accepted_not_rejected_check)
                ->where($not_self_destructed_check)
                ->where($not_deleted_check)
                ->where($not_perm_deleted_check)
                ->where($template_status_check['isnull'])
            ;
        }
        elseif ($this->_getParam('fid') == 'D') {
            // Drafts
            $select
                ->where($from_me_check)
                ->where($template_status_check['false'])
                ->where($active_status_check['true'])
            ;
        }
        elseif ($this->_getParam('fid') == 'T') {
            // Templates
            $select
                ->where($from_me_check)
                ->where($template_status_check['true'])
                ->where($active_status_check['true'])
            ;
        }
        elseif ($this->_getParam('fid') == 'S') {
            // Sent
            $select
                ->where($from_me_check)
                ->where($not_self_destructed_check)
                ->where($template_status_check['isnull'])
                ->where($active_status_check['true'])
            ;
        }
        elseif ($this->_getParam('fid') == 'R') {
            // Trash
            $select
                ->where("($to_me_check) AND ($accepted_not_rejected_check) AND ($not_self_destructed_check) AND
                    ($display_in_trash_check OR ((NOT ($not_deleted_check)) AND ($not_perm_deleted_check))) AND
                    ($template_status_check[isnull])")
                ->orWhere("($from_me_check) AND ($active_status_check[false]) AND (($template_status_check[notnull]) OR ($not_self_destructed_check))")
            ;
        }
        elseif ($this->_getParam('fid')) {
            // Folder
            $select
                ->where("($to_me_check) AND ($accepted_not_rejected_check) AND ($not_self_destructed_check) AND
                    ($template_status_check[isnull])")
                ->join(array('x' => 'mail_folder_matrix'), 'm.profile_id=x.from_profile_id AND m.counter=x.from_counter_id', array())
                ->join(array('f' => 'mail_folder_folders'), 'x.profile_id=f.profile_id AND x.folder_counter_id=f.counter', array())
                ->where('f.active=?', 't')
                ->where('f.profile_id=?', $this->target->id)
                ->where('f.counter=?', $this->_getParam('fid'))
            ;
        }
        
        if ($this->_getParam('fid')) {
            $this->view->folder = $this->_getParam('fid');
        }
        
        
        // Sort order
        $select->order(array('creation'));
        
        $this->view->mails = $this->db->fetchAll($select);
        
        
        // Need to load up the contacts
        if ($this->view->mails) {
            $c_ids = array();
            foreach ($this->view->mails as $temp) {
                if (isset($temp->profile_id) && $temp->profile_id) {
                    $c_ids[] = $temp->profile_id;
                }
            }
            if (count($c_ids)) {
                $this->view->profiles = array();
                foreach ($this->db->query($this->VMAH->getVSelectFromList($c_ids, false)) as $profile) {
                    $this->view->profiles[$profile->p_id] = $profile;
                }
            }
        }
            
        /*
        // Load up the existing folders
        $folders = array();
        foreach ($this->db->fetchAll("SELECT counter, name, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('mail_folder_folders', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM mail_folder_folders WHERE active='t' AND profile_id=? ORDER BY sort_order, name", array($this->target->id)) as $folder) {
            $temp_folders[$folder->sort_order] = $folder;
        }
        if (isset($temp_folders)) {
            $temp_sorted_keys = array_keys( $temp_folders );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $folders[$temp_folders[$key]->counter] = $temp_folders[$key];
            }
        }
        
        $this->view->folders = $folders;
        */
    }
}



/*
    ->where('
                (ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))
                AND
                (self_destruct ISNULL OR self_destruct > now())
                AND
                (
                    (ARRAY[?::bigint] <@ status_accepted) OR (status_rejected ISNULL OR NOT (ARRAY[?::bigint] <@ status_rejected))
                )
            ', $this->target->id);
        
        if (!$this->_getParam('fid')) {
            // To Me - Inbox Only
            $select
                ->where('ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id)', $this->target->id)
                ->where('ARRAY[?::bigint] <@ status_accepted OR (status_rejected ISNULL OR NOT (ARRAY[?::bigint] <@ status_rejected))', $this->target->id);
        }
        elseif ($this->_getParam('fid') == 'D' || $this->_getParam('fid') == 'T' || $this->_getParam('fid') == 'S') {
            // From Me - Drafts, Templates, Sent
            $select->where('profile_id=?', $this->target->id);
        }
        elseif ($this->_getParam('fid') == 'R') {
            // Trash - Either
            
        }
        elseif ($this->_getParam('fid')) {
            // Folders - No need to check the to or from as that is checked when adding to folder matrix
            // $select->join...
        }
        
        if ($this->_getParam('fid') != 'D' && $this->_getParam('fid') != 'T') {
            $select->where('self_destruct ISNULL OR self_destruct > now()');
        }
        
        if () {
            $select->where('status_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_deleted)', $this->target->id);
        }
        if () {
            $select->where('status_perm_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_perm_deleted)', $this->target->id);
        }
        if () {
           
*/