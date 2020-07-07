<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Mail_MultiController extends ViaMe_Controller_Action
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
        
        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function indexAction() {
        if ($this->_getParam('function') && method_exists(get_class(), (strtolower($this->_getParam('function')) . 'Action'))) {
            $func = strtolower($this->_getParam('function')) . 'Action';
            $this->$func();
        }
    }
    
    
    public function postDispatch()
    {
        if ($this->_getParam('nar')) {
            return $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        else {
            return $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
    }
    
    
    public function copyAction()
    {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $mids = $this->_getParam('mid');
        if ($mids && !is_array($mids)) {
            $mids = array($mids);
        }
        
        $which = array();
        
        if (count($mids)) {
            foreach ($mids as $dm) {
                if ($dm) {
                    list($pid, $cid) = explode('-', $dm);
                    if ($pid && $cid) {
                        $which[] = $this->db->quoteInto('(profile_id=?',  $pid) . $this->db->quoteInto(' AND counter=?)', $cid);
                    }
                }
            }
        }
        
        
        if ($this->_getParam('fid') && (count($which) || $this->_getParam('v'))) {
            // To Me
            $query = 'INSERT INTO mail_folder_matrix (profile_id, folder_counter_id, from_profile_id, from_counter_id) SELECT ';
            $query .= $this->db->quoteInto('?, ', $this->target->id);
            $query .= $this->db->quoteInto('?, profile_id, counter FROM mail_mails WHERE ', $this->_getParam('fid'));
            
            if (count($which)) {
                $query .= '(' . implode(' OR ', $which) . ')' . ' AND ';
            }
                
            $query .= $this->db->quoteInto('(ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))', $this->target->id);
            $query .= ' AND (self_destruct ISNULL OR self_destruct > now())';
            $query .= $this->db->quoteInto('AND (status_deleted ISNULL OR NOT ARRAY[?::bigint] <@ status_deleted)', $this->target->id);
            $query .= $this->db->quoteInto(' AND (status_perm_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_perm_deleted))', $this->target->id);
            
            // Avoid Duplicates to avoid failure
            $query .= $this->db->quoteInto(' AND counter NOT IN (SELECT from_counter_id FROM mail_folder_matrix WHERE profile_id=?', $this->target->id);
            $query .= $this->db->quoteInto(' AND folder_counter_id=?)', $this->_getParam('fid'));
            
            try {
                $this->db->query($query);
            } catch (Exception $e) { }
        }
    }
    
    
    public function moveAction()
    {
        if ($this->_getParam('fid')) {
            $this->copyAction();
            $this->_setParam('fid', null);
            $this->deleteAction();
        }
    }
    
    
    public function deleteAction($perm = false)
    {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $mids = $this->_getParam('mid');
        if ($mids && !is_array($mids)) {
            $mids = array($mids);
        }
        
        $which = array();
        
        if (count($mids)) {
            foreach ($mids as $dm) {
                if ($dm) {
                    list($pid, $cid) = explode('-', $dm);
                    if ($pid && $cid) {
                        $which[] = $this->db->quoteInto('(profile_id=?',  $pid) . $this->db->quoteInto(' AND counter=?)', $cid);
                    }
                }
            }
        }
        
        if ($this->_getParam('fid')) { $fid = $this->_getParam('fid'); }
        elseif ($this->_getParam('fid2')) { $fid = $this->_getParam('fid2'); }
        
        if (count($which) || $this->_getParam('v')) {
            if (!$fid) {
                // To Me - Inbox Only
                $query = $this->db->quoteInto('UPDATE mail_mails SET status_deleted=array_distinct(status_deleted || ?::bigint)', $this->target->id);
                if ($perm) {
                    $query .= $this->db->quoteInto(', status_perm_deleted=array_distinct(status_perm_deleted || ?::bigint)', $this->target->id);
                }
                $query .= ' WHERE ';
                if (count($which)) {
                    $query .= '(' . implode(' OR ', $which) . ')' . ' AND ';
                }
                $query .= $this->db->quoteInto('(ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))', $this->target->id);

                $this->db->query($query);
                
                $this->db->query('SELECT resync_mail_stats(?)', $this->target->id);
            }
            elseif ($fid == 'D' || $fid == 'T' || $fid == 'S') {
                // Drafts, Templates, or Sent
                $query = 'UPDATE mail_mails SET ';
                if ($perm) {
                    $query .= 'active=NULL';
                }
                else {
                    $query .= "active='f'";
                }
                $query .= $this->db->quoteInto(" WHERE profile_id=? AND active='t' AND ", $this->target->id);
                
                switch ($fid) {
                    case 'D':
                        $query .= "template_status='f'";
                        break;
                    case 'T':
                        $query .= "template_status='t'";
                        break;
                    case 'S':
                        $query .= "template_status ISNULL";
                        break;
                }
                
                if (count($which)) {
                    $query .= ' AND (' . implode(' OR ', $which) . ')';
                }
                
                $this->db->query($query);
            }
            elseif ($fid == 'R') {
                // Trash
                
                // Drafts, Templates, or Sent
                $query .= $this->db->quoteInto("UPDATE mail_mails SET active=NULL WHERE profile_id=? AND active='f'", $this->target->id);
                if (count($which)) {
                    $query .= ' AND (' . implode(' OR ', $which) . ')';
                }
                
                $this->db->query($query);
                
                // Inbox
                $query = $this->db->quoteInto('UPDATE mail_mails SET status_perm_deleted=array_distinct(status_perm_deleted || ?::bigint) WHERE ', $this->target->id);
                if (count($which)) {
                    $query .= '(' . implode(' OR ', $which) . ')' . ' AND ';
                }
                $query .= $this->db->quoteInto('(ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))', $this->target->id);
                $this->db->query($query);
            }
            elseif ($fid) {
                // Folder
                $delete_where = array();
                
                $delete_where[] = $this->db->quoteInto('profile_id=?', $this->target->id);
                $delete_where[] = $this->db->quoteInto('folder_counter_id=?', $fid);
                
                if (count($which)) {
                    $which = array();
                    foreach ($mids as $dm) {
                        if ($dm) {
                            list($pid, $cid) = explode('-', $dm);
                            if ($pid && $cid) {
                                $which[] = $this->db->quoteInto('(from_profile_id=?',  $pid) . $this->db->quoteInto(' AND from_counter_id=?)', $cid);
                            }
                        }
                    }
                    
                    $delete_where[] = '(' . implode(' OR ', $which) . ')';
                }
                
                $this->db->delete('mail_folder_matrix', $delete_where);
                
                // Restore the flags to show in trash if needed
                if (!$perm) {
                    
                }
    
            }
        }
    }
    
    
    public function undeleteAction()
    {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $mids = $this->_getParam('mid');
        if ($mids && !is_array($mids)) {
            $mids = array($mids);
        }
        
        $which = array();
        
        if (count($mids)) {
            foreach ($mids as $dm) {
                if ($dm) {
                    list($pid, $cid) = explode('-', $dm);
                    if ($pid && $cid) {
                        $which[] = $this->db->quoteInto('(profile_id=?',  $pid) . $this->db->quoteInto(' AND counter=?)', $cid);
                    }
                }
            }
        }
        
        if (count($which) || $this->_getParam('v')) {
            // To Me
            $query = $this->db->quoteInto('UPDATE mail_mails SET status_deleted=array_distinct(array_except(status_deleted, ARRAY[?::bigint])) WHERE ', $this->target->id);
            if (count($which)) {
                $query .= '(' . implode(' OR ', $which) . ')' . ' AND ';
            }
            $query .= $this->db->quoteInto('(ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))', $this->target->id);
            
            $query .= ' AND (self_destruct ISNULL OR self_destruct > now())';
            $query .= $this->db->quoteInto(' AND (status_perm_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_perm_deleted))', $this->target->id);
            $query .= $this->db->quoteInto('AND (ARRAY[?::bigint] <@ status_deleted)', $this->target->id);
            
            $this->db->query($query);
            
            $this->db->query('SELECT resync_mail_stats(?)', $this->target->id);
                
            
            // From Me
            $query = $this->db->quoteInto("UPDATE mail_mails SET active='t' WHERE profile_id=? AND active='f'", $this->target->id);
            if (count($which)) {
                $query .= ' AND (' . implode(' OR ', $which) . ')';
            }
            
            $query .= ' AND (template_status NOTNULL OR (self_destruct ISNULL OR self_destruct > now()))';
            
            $this->db->query($query);
        }
    }
    
    
    public function readAction($unread = false)
    {
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        $mids = $this->_getParam('mid');
        if ($mids && !is_array($mids)) {
            $mids = array($mids);
        }
        
        $which = array();
        
        if (count($mids)) {
            foreach ($mids as $dm) {
                if ($dm) {
                    list($pid, $cid) = explode('-', $dm);
                    if ($pid && $cid) {
                        $which[] = $this->db->quoteInto('(profile_id=?',  $pid) . $this->db->quoteInto(' AND counter=?)', $cid);
                    }
                }
            }
        }
        
        if (count($which) || $this->_getParam('v')) {
            // To Me
            $query = 'UPDATE mail_mails SET status_read=';
            
            if ($unread) {
                $query .= $this->db->quoteInto('array_distinct(array_except(status_read, ARRAY[?::bigint]))', $this->target->id);
            }
            else {
                $query .= $this->db->quoteInto('array_distinct(status_read || ? ::bigint)', $this->target->id);
            }
            
            $query .= ' WHERE ';
            
            if (count($which)) {
                $query .= '(' . implode(' OR ', $which) . ')' . ' AND ';
            }
            $query .= $this->db->quoteInto('(ARRAY[?::bigint] <@ (to_profile_id || cc_profile_id || bcc_profile_id))', $this->target->id);
            
            $query .= ' AND (self_destruct ISNULL OR self_destruct > now())';
            $query .= $this->db->quoteInto('AND (status_deleted ISNULL OR NOT ARRAY[?::bigint] <@ status_deleted)', $this->target->id);
            $query .= $this->db->quoteInto(' AND (status_perm_deleted ISNULL OR NOT (ARRAY[?::bigint] <@ status_perm_deleted))', $this->target->id);
            
            
            $this->db->query($query);
            
            $this->db->query('SELECT resync_mail_stats(?)', $this->target->id);
        }
    }
    
    
    public function unreadAction()
    {
        $this->readAction(true);
    }
}