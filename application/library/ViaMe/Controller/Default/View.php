<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_View extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    protected $_moduleInMatrix = true;
    protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_READ;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function init($select = null)
    {
        parent::init();
        
        $backup_select = null;
        
        if (isset($this->target->type) && isset($this->target->currentModule)) {
            if (!$select) {
                $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
                
                # If this module is NOT masked, set the target space
                if (($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter)) {
                    $select->where('obj.' . strtolower($this->target->type) . '_id <> ?', $this->target->id);
                }
                else {
                    $select->where('obj.' . strtolower($this->target->type) . '_id = ?', $this->target->id);
                }
            }
            
            // Modify the selector to include google news approved to eliminate an extra query
            //  - Doesn't work...No setPart method available
            #$select_parts = $select->getPart('columns');
            #$select_parts[] = array('p', 'profile_google_news_approved', 'p_profile_google_news_approved');
            #$select->reset('columns');
            #$select->columns($select_parts);
            #Zend_Debug::Dump($select);
            
            $select->limit(1);
            
            $backup_select = clone $select;
            
            if ($this->_getParam('id') == 'last') {
                // id = last - last (most recent) one
                $select->order('published_display_date DESC');
                $this->internal->canonical_redirect_code = 303;
            }
            elseif ($this->_getParam('id', 0)) {
                $select->where('obj.counter = ?', $this->_getParam('id'));
            }
            elseif ($this->_getParam('title', 0)) {
                $select->where('obj.title = ?', $this->_getParam('title'));
            }
            else {
                // No id param - will just assume first one
                $select->order('published_display_date ASC');
            }
            
            try {
                $this->_modObject = $this->db->fetchRow($select);
                
                // Can Edit Objects Created By You Anywhere
                if (isset($this->_modObject->profile_id) && isset($this->member->profile->id) && $this->_modObject->profile_id == $this->member->profile->id) {
                    $this->_modObject->allowed = true;
                    $this->_modObject->privilege = self::ACL_OWNER;
                }
            } catch (Exception $e) { }
                
            if (!isset($this->_modObject) || !$this->_modObject) { unset($this->_modObject); }

            if (isset($this->_modObject)) {
                $this->view->object = $this->_modObject;
                
                if (!$this->_getParam('id', 0)) {
                    $this->_setParam('id', $this->_modObject->counter);
                    $this->internal->params->id = $this->_modObject->counter;
                }
                
                if (($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter)) {
                    $this->view->masked = $this->target->currentModule->counter;
                    $this->masked = $this->target->currentModule->counter;
                }
                
                // Next object
                #if ($backup_select) { $select = clone $backup_select; }
                #else { $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule); }
                $select = clone $backup_select;
                $select->where('obj.creation > ?', $this->_modObject->creation)
                    ->order('published_display_date ASC')
                    ->limit(1);
                try {
                    $this->view->next_object = $this->db->fetchRow($select);
                } catch (Exception $e) { }
                if (!isset($this->view->next_object) || !$this->view->next_object) { unset($this->view->next_object); }
                
                // Prev object
                #if ($backup_select) { $select = clone $backup_select; }
                #else { $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule); }
                $select = clone $backup_select;
                $select->where('obj.creation < ?', $this->_modObject->creation)
                    ->order('published_display_date DESC')
                    ->limit(1);
                try {
                    $this->view->prev_object = $this->db->fetchRow($select);
                } catch (Exception $e) { }
                if (!isset($this->view->prev_object) || !$this->view->prev_object) { unset($this->view->prev_object); }
            }
        }
    }
    
    
    public function indexAction()
    {
        // META
        $this->view->headMeta()->setName('author', $this->view->object->p_name);
        $this->view->headMeta()->setName('copyright', 'Copyright by ' . $this->view->object->p_name);
        $this->view->headTitle(preg_replace("/(\n|\r)+/", ' ', ($this->view->object->meta_title ? $this->view->object->meta_title : $this->view->object->title)), 'PREPEND');
        
        if (isset($this->view->object->meta_description) && $this->view->object->meta_description) { $this->view->headMeta()->setName('description', preg_replace("/(\n|\r)+/", ' ', $this->view->object->meta_description)); }
        elseif (isset($this->view->object->summary) && $this->view->object->summary) { $this->view->headMeta()->setName('description', preg_replace("/(\n|\r)+/", ' ', $this->view->object->summary)); }
        elseif (isset($this->view->object->content) && $this->view->object->content) {
            $temp = $this->view->SEO_Quip($this->view->object->content);
            if ($temp) {
                $this->view->headMeta()->setName('description', preg_replace("/(\n|\r)+/", ' ', $temp));
            }
        }
        if (isset($this->view->object->meta_keywords) && $this->view->object->meta_keywords) { $this->view->headMeta()->setName('keywords', preg_replace("/(\n|\r)+/", ' ', $this->view->object->meta_keywords)); }
        elseif (isset($this->view->object->content) && $this->view->object->content) {
            $temp = $this->view->SEO_Keywords($this->view->object->content);
            if ($temp) {
                $this->view->headMeta()->setName('keywords', preg_replace("/(\n|\r)+/", ' ', $temp));
            }
        }
        // Open Graph
        if (isset($this->view->object->widget_image_url) && $this->view->object->widget_image_url) {
            try {
                $this->view->headMeta()->setProperty('og:image', $this->view->object->widget_image_url);
            } catch (Exception $e) { }
        }
        elseif (preg_match('/\<img/i', $this->view->object->content)) {
            try {
                $this->view->headMeta()->setProperty('og:image', preg_replace('/^.*?\<img.*?src=[\'\"]([^\'\"]+?)[\'\"].*$/si', '$1', $this->view->object->content));
            } catch (Exception $e) { }
        }
        
        
        // Google News
        if (
            $this->target->space->google_news_approved ||
            $this->target->currentModule->google_news_approved ||
            $this->_modObject->google_news_approved ||
            ($this->db->fetchOne('SELECT profile_google_news_approved FROM profile_profiles WHERE id = ?', $this->_modObject->profile_id))
            
        ) {
            // Reversed this all - default set in in metainfo plugin
            #$this->view->headMeta()->setName('Googlebot-News', 'noindex, nofollow');
            foreach ($this->view->headMeta()->getContainer() as $index => $item) {
                if (strtolower($item->type) == 'name' && strtolower($item->name) == 'googlebot-news') {
                    $this->view->headMeta()->offsetUnset($index);
                    if (!$this->masked) {
                        $this->view->headMeta()->setName('robots', 'all');
                    }
                    break;
                }
            }
        }
        // All Robots
        #if (!$this->masked) {
        #    $this->view->headMeta()->setName('robots', 'all');
        #}
        
        // Navigation Links
        $the_title = ($this->target->currentModule->display ? $this->target->currentModule->display : $this->module_modules[$this->target->currentModule->module_id]->display);
        $nav_title = $this->view->escape($the_title);
        $nav_seo = $this->view->SEO_Urlify($the_title);
        $nav_pre = $this->target->pre;
        $nav_module = $this->getRequest()->getModuleName();
        $nav_counter = $this->target->currentModule->counter;
        
        // Headlinks
        $nav_seo_pre = '';
        if ($this->view->SEO_Urlify($this->view->object->title ? $this->view->object->title : $the_title)) { $nav_seo_pre = '/' . $this->view->SEO_Urlify($this->view->object->title ? $this->view->object->title : $the_title) . '/s'; }
        
        $this->view->canonical_link = "$nav_seo_pre$nav_pre/$nav_module/view/p/mid/$nav_counter/id/" . $this->view->object->counter . '/';
        $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $this->view->object->title));
        $this->view->headLink(array('rel' => 'contents', 'href' => "$nav_seo_pre$nav_pre/$nav_module/p/mid/$nav_counter/", 'title' => $nav_title));
        $this->view->headLink(array('rel' => 'start', 'href' => "$nav_seo_pre$nav_pre/$nav_module/view/p/mid/$nav_counter/", 'title' => $nav_title));
        if (isset($this->view->prev_object)) {
            $this->view->headLink(array('rel' => 'previous', 'href' => '/' . $this->view->SEO_Urlify($this->view->prev_object->title) . "/s$nav_pre/$nav_module/view/p/mid/$nav_counter/id/" . $this->view->prev_object->counter . '/', 'title' => $this->view->prev_object->title));
        }
        if (isset($this->view->next_object)) {
            $this->view->headLink(array('rel' => 'next', 'href' => '/' . $this->view->SEO_Urlify($this->view->next_object->title) . "/s$nav_pre/$nav_module/view/p/mid/$nav_counter/id/" . $this->view->next_object->counter . '/', 'title' => $this->view->next_object->title));
        }
        
        // HeadLinks
        $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
        if (isset($this->target->currentModule->interactive, $this->target->currentModule->masked, $this->target->currentModule->x_interactive) && $this->target->currentModule->interactive && $this->target->currentModule->masked ? $this->target->currentModule->x_interactive : true) {
            $clink = $link;
            $clinkone = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->view->object->title ? $this->view->object->title : ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display)) . '/s';
        }

        if ($this->masked) {
            $link .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->masked . '/format';
            if (isset($clink)) {
                #$clink .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->masked . '/format';
                $clink .= $this->internal->target->pre . '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/xid/' . $this->masked . '/format';
                $clinkone .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->masked . '/id/' . $this->view->object->counter . '/format';
            }
        }
        else {
            if ($this->target->currentModule->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->target->currentModule->com_id != $this->internal->community->id)) {
                $link .= '/com/' . $this->target->currentModule->com_id;
                if (isset($clink)) {
                    $clink .= '/com/' . $this->target->currentModule->com_id;
                    $clinkone .= '/com/' . $this->target->currentModule->com_id;
                }
            }
            elseif ($this->target->currentModule->net_id) {
                $link .= '/net/' . $this->target->currentModule->net_id;
                if (isset($clink)) {
                    $clink .= '/net/' . $this->target->currentModule->net_id;
                    $clinkone .= '/net/' . $this->target->currentModule->net_id;
                }
            }
            elseif ($this->target->currentModule->via_id) {
                $link .= '/via/' . $this->target->currentModule->via_id;
                if (isset($clink)) {
                    $clink .= '/via/' . $this->target->currentModule->via_id;
                    $clinkone .= '/via/' . $this->target->currentModule->via_id;
                }
            }
            
            $link .= '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->target->currentModule->counter . '/format';
            if (isset($clink)) {
                $clink .= '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/xid/' . $this->target->currentModule->counter . '/format';
                $clinkone .= '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->target->currentModule->counter . '/id/' . $this->view->object->counter . '/format';
            }
            
        }
        
        foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
            $this->view->headLink()->appendAlternate(
                "$link/$key/",
                $val,
                ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' - ' . strtoupper($key) . ' Feed'
            );
            if (isset($clink) && $this->target->currentModule->interactive) {
                $this->view->headLink()->appendAlternate(
                    "$clink/$key/comments/1/",
                    $val,
                    ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' (With Comments) - ' . strtoupper($key) . ' Feed'
                );
                $this->view->headLink()->appendAlternate(
                    "$clink/$key/commentsonly/1/",
                    $val,
                    ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' (Comments Only) - ' . strtoupper($key) . ' Feed'
                );
                $this->view->headLink()->appendAlternate(
                    "$clinkone/$key/",
                    $val,
                    ($this->view->object->title) . ' (#' . $this->view->object->counter . ' Comments) - ' . strtoupper($key) . ' Feed'
                );
            }
        }
        
        // For comments system
        $this->internal->subModule = $this->target->currentModule;
        if (isset($this->_modObject)) { $this->internal->subModule->_modObject = $this->_modObject; }
        $this->internal->subModule->name = $this->module->name;
    }
    
    public function postDispatch()
    {
        // Use currentModule for two params to account for masked modules.
        #echo '<img src="http://' . $this->vars->host . '/zfbp/viewtracker/p/no_cache/1/t/' . strtolower($this->target->type) . '/i/' . $this->target->id . '/m/' . $this->target->currentModule->module_id . '/x/' . $this->target->currentModule->counter . '/c/' . $this->_modObject->counter . '/" alt="View Counter" height="0" width="0" border="0" style="height: 0; width: 0; border: none; display: none;" />';
        
        echo '<img src="http://' . $this->vars->host . '/zfbp/viewtracker/p/t/' . strtolower($this->target->type) . '/i/' . $this->target->id . '/m/' . $this->target->currentModule->module_id . '/x/' . $this->target->currentModule->counter . '/c/' . $this->_modObject->counter . '/" alt="View Counter" height="0" width="0" border="0" style="height: 0; width: 0; border: none; display: none;" />';
    }
}