<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class System_CommentsController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_subModule = null;
    protected $_masked = null;
    protected $_masked_type = null;
    protected $_masked_which = null;
    protected $_masked_counter = null;
    
    
    public function init()
    {
        parent::init();
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $contextSwitch->removeContext('json');
        $contextSwitch->removeContext('xml');
        
        if (!$contextSwitch->hasContext('html')) {
            $contextSwitch->addContext('html', array());
        }
        if (!$contextSwitch->hasContext('atom')) {
            $contextSwitch->addContext('atom', 
                array(
                    #'suffix'    => 'atom',
                    'headers'   => array('Content-Type' => 'application/atom+xml')
                    #'headers'   => array('Content-Type' => 'text/plain')
                )
            );
        }
        if (!$contextSwitch->hasContext('rss')) {
            $contextSwitch->addContext('rss', 
                array(
                    #'suffix'    => 'rss',
                    'headers'   => array('Content-Type' => 'application/rss+xml')
                    #'headers'   => array('Content-Type' => 'text/plain')
                )
            );
        }
        $contextSwitch
            ->addActionContext('widget', array('html', 'atom', 'rss'))
            ->setAutoJsonSerialization(false)
            ->initContext();
    }
    
    
    // Already Checked Prior to Getting Called
    public function preDispatch()
    {
        if (!isset($this->internal->subModule)) {
            return $this->_denied();
        }
        else {
            $this->_subModule = $this->internal->subModule;
        }
        
        if ($this->_subModule) {
            if (!$this->_subModule->interactive) {
                return $this->_denied();
            }
            
            if (($this->_subModule->community_mask || $this->_subModule->network_mask || $this->_subModule->profile_mask) && ($this->_subModule->mask_counter)) {
                $this->_masked = true;
                
                if ($this->_subModule->community_mask) {
                    $this->_masked_type = 'COM';
                    $this->_masked_which = $this->_subModule->community_mask;
                }
                elseif ($this->_subModule->network_mask) {
                    $this->_masked_type = 'NET';
                    $this->_masked_which = $this->_subModule->network_mask;
                }
                elseif ($this->_subModule->profile_mask) {
                    $this->_masked_type = 'VIA';
                    $this->_masked_which = $this->_subModule->profile_mask;
                }
                
                $this->_masked_counter = $this->_subModule->mask_counter;
            }
        }
    }
    
    
    public function listAction()
    {
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/comments_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);
        $form->setAction($this->target->pre . '/' . $this->_subModule->m_name . '/comments/post/mid/' . $this->internal->params->mid . '/id/' . (isset($this->internal->params->id) ? $this->internal->params->id . '/' : ''));
        
        $form->addDisplayGroup(array('title', 'content'), 'main', array('legend' => 'Add Your Comment'));
        
        $form->addElement('Submit', 'submit', array('label' => 'Post Comment', 'ignore' => true, 'order' => 999));
        #$form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit'), 'buttons');
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        #$form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar'), 'hidden');
        
        $this->view->form = $form;
        
        // Has to match view controller - Default to 1 if no id
        if (!$this->_getParam('id')) { $this->_setParam('id', 1); }
        
        if (($this->_getParam('mid') || ($this->_masked_counter)) && $this->_getParam('id')) {
            $select = $this->_buildComplexCommentsQuery($this->_subModule);
            
            // Sort Order
            $select->order('obj.' . $this->_getParam('c_order', 'creation') . ' ' . $this->_getParam('c_order_direction', 'DESC'));
            
            #$this->view->comments = $this->db->fetchAll($select);
            
            $paginator = Zend_Paginator::factory($select);
            $paginator->setCurrentPageNumber($this->_getParam('cpage', 1));
            $paginator->setItemCountPerPage($this->_getParam('climit', 10));
            #$paginator->setPageRange(11);
            #$paginator->setView();
            
            $this->view->paginator = $paginator;
        }
        else {
            return $this->_helper->viewRenderer->setNoRender();
        }
    }
    
    
    public function widgetAction()
    {
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        if ($this->_getParam('mid') || $this->_masked_counter) {
            $select = $this->_buildComplexCommentsQuery($this->_subModule);
                
            $select->limit($this->_getParam('c_limit', 5));
            $select->order('obj.' . $this->_getParam('c_order', 'creation') . ' ' . $this->_getParam('c_order_direction', 'DESC'));
            
            $this->view->objects = $this->db->fetchAll($select);
            
            if ($this->_masked) {
                $this->view->masked = $this->_mask_counter;
            }

            if ($contextSwitch->getCurrentContext() && $contextSwitch->getCurrentContext() != 'html') {
                $published = new Zend_Date();
                if ($this->_subModule) { $published->set($this->_subModule->creation, Zend_Date::ISO_8601); }
                if (isset($this->internal->member)) {
                    $published->setTimezone($this->internal->member->timezone);
                }
                
                $lastUpdate = new Zend_Date();
                if ($this->_subModule) { $lastUpdate->set($this->_subModule->updated, Zend_Date::ISO_8601); }
                if (isset($this->internal->member)) {
                    $lastUpdate->setTimezone($this->internal->member->timezone);
                }
                
                $metas = array('author', 'copyright');
                foreach ($this->view->headMeta()->getContainer()->getArrayCopy() as $key => $val) {
                    if (in_array($val->name, $metas)) {
                        $metas[$val->name] = $val->content;
                    }
                    
                }
                
                // Create the link
                $link = 'http://' . $this->internal->vars->host;
                if ($this->_subModule) {
                    $link .= '/' . $this->view->SEO_Urlify($this->_subModule->display ? $this->_subModule->display : $this->_subModule->m_display) . '/s';
                }
                if ($this->_masked) {
                    $link .= $this->internal->target->pre . '/' . $this->_subModule->m_name . '/comments/widget/mid/' . $this->_masked_counter;
                }
                else {
                    if ($this->_subModule->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->_subModule->com_id != $this->internal->community->id)) { $link .= '/com/' . $this->_subModule->com_id; }
                    elseif ($this->_subModule->net_id) { $link .= '/net/' . $this->_subModule->net_id; }
                    elseif ($this->_subModule->via_id) { $link .= '/via/' . $this->_subModule->via_id; }
                    
                    $link .= '/' . $this->_subModule->m_name . '/comments/widget/mid/' . $this->_subModule->counter;
                }
                if ($this->_getParam('id')) {
                    $link .= '/id/' . $this->_getParam('id');
                }
                $link .= '/format/' . $contextSwitch->getCurrentContext() . '/';
                
                // Build the Feed Array
                $array = array(
                    //required
                    'title' => ($this->_subModule->display ? $this->_subModule->display : $this->_subModule->m_display) . ' - Comments',
                    'link'  => $link,
                    'charset' => 'UTF-8',
                    
                    // optional
                    'published'  => $published->getTimestamp(),
                    'lastUpdate' => $lastUpdate->getTimestamp(),
                    
                    'description' => ($this->_subModule->meta_description ? $this->_subModule->meta_description : $this->_subModule->m_description),
                    'author'      => $metas['author'],
                    #'email'       => 'email of the author',
                    'copyright' => $metas['copyright'],
                    
                    'webmaster' => ($this->community->email ? $this->community->email : $this->config->admin->email),
                    
                    #'image'     => 'url to image',
                    #'generator' => 'generator',
                    #'language'  => 'language the feed is written in'
                );
                
                foreach ($this->view->objects as $object) {
                    $published->set($object->creation, Zend_Date::ISO_8601);
                    
                    // Create the link
                    $link = 'http://' . $this->internal->vars->host;
                    #$link .= '/' . $this->view->SEO_Urlify($object->title) . '/s';
                    if ($this->_masked) {
                        $link .= $this->internal->target->pre . '/' . $this->_subModule->m_name . '/view/p/mid/' . $this->_masked_counter . '/id/' . $object->item_counter . '/';
                    }
                    else {
                        if ($object->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $object->com_id != $this->internal->community->id)) { $link .= '/com/' . $object->com_id; }
                        elseif ($object->net_id) { $link .= '/net/' . $object->net_id; }
                        elseif ($object->via_id) { $link .= '/via/' . $object->via_id; }
                        
                        $link .= '/' . $this->_subModule->m_name . '/view/p/mid/' . $object->matrix_counter . '/id/' . $object->item_counter . '/';
                    }
                    
                    $oarray = array(
                        //required
                        'title' => $object->title,
                        'link'  => $link,
                        
                        // required, only text, no html
                        'description' => (isset($object->summary) && $object->summary ? $object->summary : (isset($object->meta_description) && $object->meta_description ? $object->meta_description : (isset($object->content) && $object->content ? $this->view->SEO_Quip($object->content, 256) : ''))),

                        // optional
                        #'guid' => $link,

                        #'content' => $object->content,
                        
                        'lastUpdate' => $published->getTimestamp(),
                        
                        #'comments'   => 'comments page of the feed entry',
                        #'commentRss' => 'the feed url of the associated comments',
                        
                        /*
                        // optional, original source of the feed entry
                        'source' => array(
                            // required
                            'title' => 'title of the original source',
                            'url'   => 'url of the original source'
                        ),
                        
                        // optional, list of the attached categories
                        'category' => array(
                            array(
                                // required
                                'term' => 'first category label',
            
                                // optional
                                'scheme' => 'url that identifies a categorization scheme'
                            ),
            
                            array(
                                // data for the second category and so on
                            )
                        ),
            
                        // optional, list of the enclosures of the feed entry
                        'enclosure'    => array(
                            array(
                                // required
                                'url' => 'url of the linked enclosure',
            
                                // optional
                                'type' => 'mime type of the enclosure',
                                'length' => 'length of the linked content in octets'
                            ),
            
                            array(
                                //data for the second enclosure and so on
                            )
                        )
                        */
                    );
                    
                    $array['entries'][] = $oarray;
                }
                
                $feed = Zend_Feed::importBuilder(new Zend_Feed_Builder($array), $contextSwitch->getCurrentContext());
                $feed->send();
                
                $this->_helper->viewRenderer->setNoRender();
            }
            else {
                // html feed; output in view script; add headlinks
                // Create the link
                $link = 'http://' . $this->internal->vars->host;
                if ($this->_masked) {
                    $link .= $this->internal->target->pre . '/' . $this->_subModule->m_name . '/comments/widget/mid/' . $this->_masked_counter . '/format';
                }
                else {
                    if ($this->_subModule->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->_subModule->com_id != $this->internal->community->id)) { $link .= '/com/' . $this->_subModule->com_id; }
                    elseif ($this->_subModule->net_id) { $link .= '/net/' . $this->_subModule->net_id; }
                    elseif ($this->_subModule->via_id) { $link .= '/via/' . $this->_subModule->via_id; }
                    
                    $link .= '/' . $this->_subModule->m_name . '/comments/widget/mid/' . $this->_subModule->counter . '/format';
                }
                
                foreach (array_intersect_key($contextSwitch->getContexts(), array_flip($contextSwitch->getActionContexts($this->getRequest()->getActionName()))) as $key => $val) {
                    if ($key == 'html') { continue; }
                    // Skip adding for current modules - those will be added in the controller; usually index and view
                    if (isset($this->target->currentModule) && $this->target->currentModule->module_id==$this->_subModule->module_id && $this->target->currentModule->counter==$this->_subModule->counter) { continue; }
                    $this->view->headLink()->appendAlternate(
                        "$link/$key/",
                        ($val['headers']['Content-Type'] ? $val['headers']['Content-Type'] : 'text/html'),
                        ($this->_subModule->display ? $this->_subModule->display : $this->_subModule->m_display) . ' - ' . strtoupper($key) . ' Feed'
                    );
                }
            }
        }
        else {
            $this->_helper->viewRenderer->setNoRender();
        }
    }
    
    
    public function postAction()
    {
        if (!$this->internal->subModule->_modObject->allow_comments) {
            return $this->_denied();
        }
        
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/comments_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('title', 'content'), 'main', array('legend' => 'Add Your Comment'));
        
        $form->addElement('Submit', 'submit', array('label' => 'Post Comment', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('comment_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $data['module_id'] = $this->_subModule->module_id;
            $data['matrix_counter'] = ($this->_masked ? $this->_masked_counter : $this->_getParam('mid'));
            $data['item_counter'] = $this->_getParam('id');
            $data['profile_id'] = (isset($this->member) ? $this->member->profile->id : 0);
            
            if ($this->_getParam('conc')) {
                $data['parent_id'] = $this->_getParam('conc');
            }
            
            switch(($this->_masked ? $this->_masked_type : $this->target->type)) {
                case 'VIA':
                    $data['via_id'] = ($this->_masked ? $this->_masked_which : $this->target->id);
                    $search_space = 'via_id';
                    $search_id = $data['via_id'];
                    break;
                case 'NET':
                    $data['net_id'] = ($this->_masked ? $this->_masked_which : $this->target->id);
                    $search_space = 'net_id';
                    $search_id = $data['net_id'];
                    break;
                default:
                    $data['com_id'] = ($this->_masked ? $this->_masked_which : $this->target->id);
                    $search_space = 'com_id';
                    $search_id = $data['com_id'];
                    break;
            }
            
            $data['title'] = $this->_getParam('title');
            $data['content'] = $this->_getParam('content');
            
            if ($this->_subModule->moderated && !($this->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE)) {
                $data['active'] = 'f';
            }
            
            if (isset($_SERVER['REMOTE_ADDR'])) { $data['ip_address'] = $_SERVER['REMOTE_ADDR']; }
            
            try {
                $system_comments = new system_models_comments();
                $system_comments->insert($data);
                
                $this->log->WARN('New comment created.');
                
                // Send Notification Email
                if ($this->_subModule->_modObject->b_id <> $this->member->id && $this->_subModule->_modObject->p_active && $this->_subModule->_modObject->b_active) {
                    if ($this->_masked) {
                        $this->view->masked = $this->_mask_counter;
                    }
                    
                    if ($this->_subModule->moderated && !($this->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE)) {
                        // Moderated Comments - Need to Fetch the ID of just posted Comment
                        $comment_counter = $this->db->fetchOne('SELECT MAX(counter) FROM system_comments WHERE ' . $search_space . '=? AND module_id=? AND matrix_counter=? AND item_counter=? AND profile_id=?', array($search_id, $data['module_id'], $data['matrix_counter'], $data['item_counter'], $data['profile_id']));
                    }
                    
                    try {
                        // Have to backwards selected the comment poster as viewed by the author
                        $select = $this->db->select()
                            ->from(array('obj' => 'profile_profiles'), array(
                                    'p_id' => 'id',
                                    'p_name' => 'name',
                                    'p_site_admin' => 'site_admin',
                                    'p_active' => 'active'
                                ))
                            ->where('obj.id=?', $this->member->profile->id)
                            
                            ->join(array('b' => 'member_members'), 'obj.member_id = b.id',
                                array(
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
                            )
                            
                            ->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->_subModule->_modObject->profile_id),
                                array(
                                    'vc_status' => 'status',
                                    'vc_display' => 'display'
                                )
                            );
                        
                        if ($tp = $this->db->fetchRow($select)) {
                            $partial_array = array('profile' => $tp, 'comment_counter' => (isset($comment_counter) ? $comment_counter : null), 'subModule' => $this->_subModule, 'data' => $data, 'internal' => $this->internal);                
                            $this->_helper->ViaMe->sendEmail(
                                $this->_subModule->_modObject->b_email,
                                $this->_subModule->_modObject->p_name,
                                ((isset($tp->vc_status) && isset($tp->vc_display) && $tp->vc_status && $tp->vc_display) ? $this->view->escape($tp->vc_display) : $this->view->escape($this->member->profile->name)) . ' Has Commented On Your ' . $this->_subModule->m_display,
                                $this->view->partial('comments/emails/commented.phtml', $partial_array), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display
                            );
                        }
                    } catch (Exception $e) { }
                }
                
                $this->_autoredirect($this->target->pre . '/');
            } catch (Exception $e) {
                $this->view->formErrors = array('That comment was not successfully created.');
            }
        }
        
        $this->view->form = $form;
    }
    
    
    public function editAction()
    {
        if (!$this->internal->subModule->_modObject->allow_comments) {
            return $this->_denied();
        }
        if (!(isset($this->internal->member) && ($this->internal->member->site_admin || $this->internal->member->profile->site_admin))) {
              return $this->_unauthorized('Access Error', 'You do not have sufficient privileges to access this area.');
        }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/comments_form.php';
        $form = new Zend_Form();
        #$form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        #$form->setMethod('post');
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('title', 'content'), 'main', array('legend' => 'Edit Comment'));
        
        $form->addElement('Submit', 'submit', array('label' => 'Edit Comment', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {   
            $system_comments = new system_models_comments();
                     
            $data['title'] = $this->_getParam('title');
            $data['content'] = $this->_getParam('content');
            
            $where = array();
            $where[] = $system_comments->getAdapter()->quoteInto(strtolower($this->internal->target->type) . '_id=?', $this->internal->target->id);
            $where[] = $system_comments->getAdapter()->quoteInto('module_id=?', $this->_subModule->module_id);
            $where[] = $system_comments->getAdapter()->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $system_comments->getAdapter()->quoteInto('item_counter=?', $this->_getParam('id'));
            $where[] = $system_comments->getAdapter()->quoteInto('counter=?', $this->_getParam('cid'));
            
            try {
                $system_comments->update(
                    $data,
                    $where
                );
                
                $this->log->WARN('Comment modified.');
                
                $this->_autoredirect($this->target->pre . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That comment was not successfully created.');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->db->fetchrow('SELECT title, content FROM system_comments WHERE ' . strtolower($this->internal->target->type) . "_id=? AND module_id=? AND matrix_counter=? AND item_counter=? AND counter=? AND active='t'", array($this->internal->target->id, $this->_subModule->module_id, $this->_getParam('mid'), $this->_getParam('id'), $this->_getParam('cid'))));
            #$form->getElement('status')->setValue($this->_modObject->active);
        }
        
        $this->view->form = $form;
        
        $this->renderScript('comments/post.phtml');
    }
    
    
    public function moderateAction()
    {
        $system_comments = new system_models_comments();
            
        $row = $system_comments->find(
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'COM' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'NET' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'VIA' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            $this->_subModule->module_id,
            ($this->_masked ? $this->_masked_counter : $this->_getParam('mid')),
            $this->_getParam('id'),
            $this->_getParam('cid')
        )->current();
        
        if (isset($row)) {
            switch ($this->_getParam('status')) {
                case 'approve' :
                    $row->active = 't';
                    break;
                case 'reject' :
                    $row->active = null;
                    break;
            }
            
            $row->save();
        }
        
        $this->_autoredirect($this->target->pre . '/');
    }
    
    public function deleteAction()
    {
        $system_comments = new system_models_comments();
            
        $row = $system_comments->find(
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'COM' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'NET' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            (($this->_masked ? $this->_masked_type : $this->target->type) == 'VIA' ? ($this->_masked ? $this->_masked_which : $this->target->id) : 0),
            $this->_subModule->module_id,
            ($this->_masked ? $this->_masked_counter : $this->_getParam('mid')),
            $this->_getParam('id'),
            $this->_getParam('cid')
        )->current();
        
        if (isset($row)) {
            $row->active = null;            
            $row->save();
        }
        
        $this->_autoredirect($this->target->pre . '/');
    }
    
    
    protected function _buildComplexCommentsQuery($object = null)
    {
        $select = null;
        if ($object) {
            // No ACL on Comments
            #$acl_clause = $this->db->quoteInto('(check_acl(obj.acl, ?, obj.com_id, obj.net_id, obj.via_id, obj.module_id, obj.matrix_counter, obj.counter, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            
            $select = $this->db->select()
                ->from(array('obj' => 'system_comments'), array('*'))
                ->where('obj.' . strtolower($this->_masked ? $this->_masked_type : $this->target->type) . '_id=?', ($this->_masked ? $this->_masked_which : $this->target->id))
                ->where('obj.module_id=?', $object->module_id)
                ->where('obj.matrix_counter=?', ($this->_masked ? $this->_masked_counter : $this->_getParam('mid')))
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'p_name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_picture_url' => 'picture_url',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active'
                    )
                )
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active'
                    )
                )
                ->where('b.active NOTNULL')
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                ->where('c.active=?', 't');
            
            if ($this->_getParam('id')) {
                // ACL already done in calling module
                $select->where('obj.item_counter=?', $this->_getParam('id'));
            }
            elseif (!($this->target->acl->privilege >= self::ACL_READ && $this->target->acl->recursive)) {
                // Need to do ACL check on items
                $acl_clause = $this->db->quoteInto('(check_acl(t.acl, ?, t.com_id, t.net_id, t.via_id, t.module_id, t.matrix_counter, t.counter, 0))', (isset($this->member->profile->id) ? $this->member->profile->id : 0));
                
                $select->join(array('t' => $object->m_name . '_' . $object->m_name . 's'), 'obj.com_id = t.com_id AND obj.net_id = t.net_id AND obj.via_id = t.via_id AND obj.module_id = t.module_id AND obj.matrix_counter = t.matrix_counter AND obj.item_counter = t.counter', array())
                ->where("t.acl ISNULL OR $acl_clause.allowed ISNULL OR ($acl_clause.allowed AND $acl_clause.privilege > 0)");
                if ($this->target->acl->privilege >= self::ACL_MODERATE) {
                    $select->where('t.active NOTNULL');
                }
                else {
                    $select->where('t.active=?', 't');
                }
            }
            
            if (isset($this->member)) {
                $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                    array(
                        'vc_status' => 'status',
                        'vc_display' => 'display'
                    )
                );
            }
            
            if ($this->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE) {
                $select->where('obj.active NOTNULL');
            }
            else {
                $select->where('obj.active=?', 't');
            }
        }
        
        return $select;
    }
}
