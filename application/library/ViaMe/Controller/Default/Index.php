<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Index extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_READ;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function preDispatch()
    {
        if ($this->target->type == 'COM'  && !$this->_getParam('mid') && !$this->_getParam('profile') && ($this->_getParam('symbol') || $this->_getParam('symbol_id')) && isset($this->module_modules[$this->getRequest()->getModuleName()])) {
            // Symbol - Retrieve all of type for symbol - fetch the select query from system widget
            require_once $this->vars->APP_PATH . "/modules/system/controllers/WidgetController.php";
            $this->_setParam('nomid', 1);
            $this->_setParam('mid', $this->module_modules[$this->getRequest()->getModuleName()]->id);
            $SWC = new System_WidgetController($this->getRequest(), $this->getResponse());
            #$SWC->init();
            #$SWC->preDispatch();
            #list($this->_allModuleSelect, $this->_symbolData) = $SWC->symbolAction();
            list($this->_allModuleSelect, $this->_symbolData) = $SWC->symbolAction();
            $this->_moduleInMatrix = false;
        }
        elseif ($this->target->type == 'COM'  && $this->_getParam('profile') && $this->_getParam('profile_id') && isset($this->module_modules[$this->getRequest()->getModuleName()])) {
                // Profile - Retrieve all of type for user - fetch the select query from system widget
                require_once $this->vars->APP_PATH . "/modules/system/controllers/WidgetController.php";
                $this->_setParam('nomid', 1);
                $this->_setParam('mid', $this->module_modules[$this->getRequest()->getModuleName()]->id);
                $SWC = new System_WidgetController($this->getRequest(), $this->getResponse());
                #$SWC->init();
                #$SWC->preDispatch();
                $this->_allModuleSelect = $SWC->profileAction();
                $this->_moduleInMatrix = false;
            }
        elseif ($this->target->type == 'VIA'  && $this->target->space->id && $this->_getParam('profile') && isset($this->module_modules[$this->getRequest()->getModuleName()])) {
            // Profile - Retrieve all of type for user - fetch the select query from system widget
            require_once $this->vars->APP_PATH . "/modules/system/controllers/WidgetController.php";
            $this->_setParam('nomid', 1);
            $this->_setParam('profile_id', $this->target->id);
            $this->_setParam('mid', $this->module_modules[$this->getRequest()->getModuleName()]->id);
            $SWC = new System_WidgetController($this->getRequest(), $this->getResponse());
            #$SWC->init();
            #$SWC->preDispatch();
            $this->_allModuleSelect = $SWC->profileAction();
            $this->_moduleInMatrix = false;
        }
        elseif ($this->_getParam('nomid') && isset($this->module_modules[$this->getRequest()->getModuleName()])) {
            // NoMid - Retrieve all of type from space - fetch the select query from system widget
            require_once $this->vars->APP_PATH . "/modules/system/controllers/WidgetController.php";
            $this->_setParam('mid', $this->module_modules[$this->getRequest()->getModuleName()]->id);
            $SWC = new System_WidgetController($this->getRequest(), $this->getResponse());
            #$SWC->init();
            #$SWC->preDispatch();
            $this->_allModuleSelect = $SWC->indexAction();
            $this->_moduleInMatrix = false;
        }
        
        parent::preDispatch();
    }
    
    
    public function indexAction($select = null)
    {
        if (!$select) {
            if (isset($this->_allModuleSelect) && $this->_allModuleSelect) {
                // Wrap the select
                $select = $this->db->select()
                  ->from(array('tt' => new Zend_Db_Expr('(' . $this->_allModuleSelect->__toString() . ')')), array('*'))
                ;
                
                $this->resetMID();
            }
            else {
                $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
                
                $select = $this->addProfileSymbolClauses($select);
                
                // Wrap the select - intersect with quote_view_data and easier params
                $select = $this->db->select()
                  ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
                ;
            }
        }
        
        
        /* Begin Finishing Touches On Wrapped Select */
            #select title,symbols from article_articles where via_id=2 and matrix_counter=1 and symbols && (select array_accum(id) from quote_view_data where id=62230);
            #left join quote_view_data qvd on tt.symbol_id=qvd.symbol_id;
            // Intersect against a quote_view_data select
            if ($this->_getParam('qvd_where') || $this->_getParam('qvd_join_where')) {
                /*
                    NOTE: With the new way of left joining the tables, the where clause can just be a where clause.  Can be integrated into vmqa_where and
                      doesn't necessarily have to be in qvd_where.  But, this is a special case where the table HAS TO HAVE symbol_id to join on.  NOT symbols array[bigint]
                */
                if ($this->_getParam('qvd_join_where')) {
                    $select->joinLeft(array('qvd' => 'quote_view_data'), 'tt.symbol_id=qvd.symbol_id', array())->where($this->_getParam('qvd_join_where'));
                }
                else {
                    $qvd_select = $this->db->select()->from('quote_view_data', array(new Zend_Db_Expr('array_accum(symbol_id)')))->where($this->_getParam('qvd_where'));
                    $select->where(new Zend_Db_Expr( '(symbols && (' . $qvd_select->__toString() . '))' ));
                }
            }
            
            // General where insertion
            if ($this->_getParam('vmqa_where')) {
                $select->where($this->_getParam('vmqa_where'));
            }
            
            if (!$this->_getParam('select_order_set')) {
                $select->order(array(
                    ($this->_getParam('order') ? $this->_getParam('order', 'published_display_date') : 'published_display_date') . ' ' . $this->_getParam('order_direction', 'DESC'),
                    'COALESCE(published, creation) DESC'
                ));
            }
        /* End Finishing Touches On Wrapped Select */
        
        
        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $paginator->setItemCountPerPage($this->_getParam('limit', 10));
        $paginator->setPageRange(11);
        
        $this->view->paginator = $paginator;
        if (($this->target->currentModule->community_mask || $this->target->currentModule->network_mask || $this->target->currentModule->profile_mask) && ($this->target->currentModule->mask_counter)) {
            $this->view->masked = $this->target->currentModule->counter;
        }
        
        self::setMeta();
    }
    
    public function setMeta() {
        // Robots
        if (!(isset($this->view->masked) || $this->view->masked) && isset($this->view->paginator) && $this->view->paginator->count()) {
            # ART - 8/19/2015 - Removed robots all for index pages
            #$this->view->headMeta()->setName('robots', 'all');
        }
        
        // Navigation Links
        $the_title = ($this->target->currentModule->display ? $this->target->currentModule->display : $this->module_modules[$this->target->currentModule->module_id]->display);
        $nav_title = $this->view->escape($the_title);
        
        $nav_seo = $this->view->SEO_Urlify($the_title);
        $nav_pre = $this->target->pre;
        $nav_module = $this->getRequest()->getModuleName();
        $nav_counter = $this->target->currentModule->counter;
        
        if ($this->target->type == 'COM'  && !$this->_getParam('mid') && !$this->_getParam('profile') && ($this->_getParam('symbol') || $this->_getParam('symbol_id')) && $this->_getParam('nomid')) {
            $the_title = $this->_symbolData->name . ' - ' . $the_title;
            // HeadLinks
            $this->view->canonical_link = '';
            if ($this->view->SEO_Urlify($this->_symbolData->name)) { $this->view->canonical_link .= '/' . $this->view->SEO_Urlify($this->_symbolData->name) . '/s'; }
            $this->view->canonical_link .= "/$nav_module/p/symbol/" . $this->_symbolData->internal_symbol . '/';
            $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $the_title));
            
            $this->view->headTitle($the_title, 'PREPEND');
            // Via Breadcrumb
            //$this->_addBreadCrumb(array(
            //    'title' => $the_title,
            //    'simple' => $the_title,
            //    'url' => $this->vars->request_uri
            //));
            
            if ($this->view->paginator) {
                $this->view->headLink(array('rel' => 'contents', 'href' => '?', 'title' => $nav_title));
                $this->view->headLink(array('rel' => 'start', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->first, 'title' => $nav_title));
                if (isset($this->view->paginator->getPages()->previous)) {
                    $this->view->headLink(array('rel' => 'previous', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->previous, 'title' => $nav_title));
                }
                if (isset($this->view->paginator->getPages()->next)) {
                    $this->view->headLink(array('rel' => 'next', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->next, 'title' => $nav_title));
                }
            }
            #http://dev.viame.com/system/widget/symbol/p/symbol/YHOO/mid/15/format/atom/
            
            // HeadLinks
            $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
            
            $link .= '/system/widget/symbol/p/symbol/' . $this->_symbolData->internal_symbol . '/mid/' . $this->target->currentModule->module_id . '/format';
            
            foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                $this->view->headLink()->appendAlternate(
                    "$link/$key/",
                    $val,
                    $the_title . ' - ' . strtoupper($key) . ' Feed'
                );
            }
        }
        elseif ($this->target->type == 'COM'  && $this->_getParam('profile') && $this->_getParam('profile_id') && $this->_getParam('nomid')) {
            $the_title = 'Multiple Profiles - ' . $the_title;
            // HeadLinks
            $this->view->canonical_link = '';
            if ($nav_seo) { $this->view->canonical_link .= "/$nav_seo/s"; }
            $this->view->canonical_link .= "$nav_pre/$nav_module/p/profile/1/profile_id/" . $this->_getParam('profile_id') . '/';
            $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $the_title));
            
            $this->view->headTitle($the_title, 'PREPEND');
            // Via Breadcrumb
            //$this->_addBreadCrumb(array(
            //    'title' => $the_title,
            //    'simple' => $the_title,
            //    'url' => $this->vars->request_uri
            //));
            
            if ($this->view->paginator) {
                $this->view->headLink(array('rel' => 'contents', 'href' => '?', 'title' => $nav_title));
                $this->view->headLink(array('rel' => 'start', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->first, 'title' => $nav_title));
                if (isset($this->view->paginator->getPages()->previous)) {
                    $this->view->headLink(array('rel' => 'previous', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->previous, 'title' => $nav_title));
                }
                if (isset($this->view->paginator->getPages()->next)) {
                    $this->view->headLink(array('rel' => 'next', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->next, 'title' => $nav_title));
                }
            }
            
            // HeadLinks
            $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
            $link .= '/system/widget/profile/p/profile_id/' . $this->_getParam('profile_id') . '/format';
            $clink = $link;
            
            foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                $this->view->headLink()->appendAlternate(
                    "$link/$key/",
                    $val,
                    $the_title . ' - ' . strtoupper($key) . ' Feed'
                );
                if (isset($clink)) {
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/comments/1/",
                        $val,
                        $the_title . ' (With Comments) - ' . strtoupper($key) . ' Feed'
                    );
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/commentsonly/1/",
                        $val,
                        $the_title . ' (Comments Only) - ' . strtoupper($key) . ' Feed'
                    );
                }
            }
        }
        elseif ($this->target->type == 'VIA'  && $this->target->space->id && $this->_getParam('profile') && $this->_getParam('nomid')) {
            $the_title = $this->target->space->name . ' - ' . $the_title;
            // HeadLinks
            $this->view->canonical_link = '';
            if ($nav_seo) { $this->view->canonical_link .= "/$nav_seo/s"; }
            $this->view->canonical_link .= "$nav_pre/$nav_module/p/profile/1/";
            $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $the_title));
            
            $this->view->headTitle($the_title, 'PREPEND');
            // Via Breadcrumb
            //$this->_addBreadCrumb(array(
            //    'title' => $the_title,
            //    'simple' => $the_title,
            //    'url' => $this->vars->request_uri
            //));
            
            if ($this->view->paginator) {
                $this->view->headLink(array('rel' => 'contents', 'href' => '?', 'title' => $nav_title));
                $this->view->headLink(array('rel' => 'start', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->first, 'title' => $nav_title));
                if (isset($this->view->paginator->getPages()->previous)) {
                    $this->view->headLink(array('rel' => 'previous', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->previous, 'title' => $nav_title));
                }
                if (isset($this->view->paginator->getPages()->next)) {
                    $this->view->headLink(array('rel' => 'next', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->next, 'title' => $nav_title));
                }
            }
            
            // HeadLinks
            $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
            $clink = $link;
            
            #if ($this->target->type == 'COM' && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->target->id != $this->internal->community->id)) {
            #    $link .= '/com/' . $this->target->id;
            #    if (isset($clink)) { $clink .= '/com/' . $this->target->id; }
            #}
            #elseif ($this->target->type == 'NET') {
            #    $link .= '/net/' . $this->target->id;
            #    if (isset($clink)) { $clink .= '/net/' . $this->target->id; }
            #}
            #elseif ($this->target->type == 'VIA') {
                $link .= '/via/' . $this->target->id;
                if (isset($clink)) { $clink .= '/via/' . $this->target->id; }
            #}
            
            #                  /system/widget/p/mid/15/xid/1/format/atom/comments/1/
            
            $link .= '/system/widget/profile/p/mid/' . $this->target->currentModule->module_id . '/format';
            if (isset($clink)) { $clink .= '/system/widget/profile/p/mid/' . $this->target->currentModule->module_id . '/format'; }
            
            foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                $this->view->headLink()->appendAlternate(
                    "$link/$key/",
                    $val,
                    $the_title . ' - ' . strtoupper($key) . ' Feed'
                );
                if (isset($clink)) {
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/comments/1/",
                        $val,
                        $the_title . ' (With Comments) - ' . strtoupper($key) . ' Feed'
                    );
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/commentsonly/1/",
                        $val,
                        $the_title . ' (Comments Only) - ' . strtoupper($key) . ' Feed'
                    );
                }
            }
        }
        elseif ($this->_getParam('nomid')) {
            // HeadLinks
            $this->view->canonical_link = '';
            if ($nav_seo) { $this->view->canonical_link .= "/$nav_seo/s"; }
            $this->view->canonical_link .= "$nav_pre/$nav_module/p/nomid/1/";
            $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $the_title));
            
            $this->view->headTitle($the_title, 'PREPEND');
            // Via Breadcrumb
            //$this->_addBreadCrumb(array(
            //    'title' => $the_title,
            //    'simple' => $the_title,
            //    'url' => $this->vars->request_uri
            //));
            
            if ($this->view->paginator) {
                $this->view->headLink(array('rel' => 'contents', 'href' => '?', 'title' => $nav_title));
                $this->view->headLink(array('rel' => 'start', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->first, 'title' => $nav_title));
                if (isset($this->view->paginator->getPages()->previous)) {
                    $this->view->headLink(array('rel' => 'previous', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->previous, 'title' => $nav_title));
                }
                if (isset($this->view->paginator->getPages()->next)) {
                    $this->view->headLink(array('rel' => 'next', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->next, 'title' => $nav_title));
                }
            }
            
            // HeadLinks
            $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
            $clink = $link;
            
            if ($this->target->type == 'COM' && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->target->id != $this->internal->community->id)) {
                $link .= '/com/' . $this->target->id;
                if (isset($clink)) { $clink .= '/com/' . $this->target->id; }
            }
            elseif ($this->target->type == 'NET') {
                $link .= '/net/' . $this->target->id;
                if (isset($clink)) { $clink .= '/net/' . $this->target->id; }
            }
            elseif ($this->target->type == 'VIA') {
                $link .= '/via/' . $this->target->id;
                if (isset($clink)) { $clink .= '/via/' . $this->target->id; }
            }
            
            #                  /system/widget/p/mid/15/xid/1/format/atom/comments/1/
            
            $link .= '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/format';
            if (isset($clink)) { $clink .= '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/format'; }
            
            foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                $this->view->headLink()->appendAlternate(
                    "$link/$key/",
                    $val,
                    ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' - ' . strtoupper($key) . ' Feed'
                );
                if (isset($clink)) {
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
                }
            }
        }
        else {
            // HeadLinks
            $this->view->canonical_link = '';
            if ($nav_seo) { $this->view->canonical_link .= "/$nav_seo/s"; }
            $this->view->canonical_link .= "$nav_pre/$nav_module/p/mid/$nav_counter/";
            $this->view->headLink(array('rel' => 'canonical', 'href' => $this->view->canonical_link, 'title' => $the_title));
            
            if ($this->view->paginator) {
                $this->view->headLink(array('rel' => 'contents', 'href' => '?', 'title' => $nav_title));
                $this->view->headLink(array('rel' => 'start', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->first, 'title' => $nav_title));
                if (isset($this->view->paginator->getPages()->previous)) {
                    $this->view->headLink(array('rel' => 'previous', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->previous, 'title' => $nav_title));
                }
                if (isset($this->view->paginator->getPages()->next)) {
                    $this->view->headLink(array('rel' => 'next', 'href' => '?limit=' . $this->view->paginator->getPages()->itemCountPerPage . '&page=' . $this->view->paginator->getPages()->next, 'title' => $nav_title));
                }
            }
            
            // HeadLinks
            $link = 'https://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . '/s';
            if (isset($this->target->currentModule->interactive, $this->target->currentModule->masked, $this->target->currentModule->x_interactive) && $this->target->currentModule->interactive && $this->target->currentModule->masked ? $this->target->currentModule->x_interactive : true) {
                $clink = $link;
            }
            
            if ($this->view->masked) {
                $link .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->view->masked . '/format';
                #if (isset($clink)) { $clink .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->view->masked . '/format'; }
                if (isset($clink)) { $clink .= $this->internal->target->pre . '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/xid/' . $this->view->masked . '/format'; }
            }
            else {
                if ($this->target->currentModule->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $this->target->currentModule->com_id != $this->internal->community->id)) {
                    $link .= '/com/' . $this->target->currentModule->com_id;
                    if (isset($clink)) { $clink .= '/com/' . $this->target->currentModule->com_id; }
                }
                elseif ($this->target->currentModule->net_id) {
                    $link .= '/net/' . $this->target->currentModule->net_id;
                    if (isset($clink)) { $clink .= '/net/' . $this->target->currentModule->net_id; }
                }
                elseif ($this->target->currentModule->via_id) {
                    $link .= '/via/' . $this->target->currentModule->via_id;
                    if (isset($clink)) { $clink .= '/via/' . $this->target->currentModule->via_id; }
                }
                
                $link .= '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->target->currentModule->counter . '/format';
                #if (isset($clink)) { $clink .= '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->target->currentModule->counter . '/format'; }
                if (isset($clink)) { $clink .= '/system/widget/p/mid/' . $this->target->currentModule->module_id . '/xid/' . $this->target->currentModule->counter . '/format'; }
            }
            
            foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                $this->view->headLink()->appendAlternate(
                    "$link/$key/",
                    $val,
                    ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' - ' . strtoupper($key) . ' Feed'
                );
                if (isset($clink) && $this->target->currentModule->interactive) {
                    /*
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/",
                        $val,
                        ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' (With Comments) - ' . strtoupper($key) . ' Feed'
                    );
                    $this->view->headLink()->appendAlternate(
                        "$clink/$key/",
                        $val,
                        ($this->target->currentModule->display ? $this->target->currentModule->display : $this->target->currentModule->m_display) . ' (Comments Only) - ' . strtoupper($key) . ' Feed'
                    );
                    */
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
                }
            }
        }
    }
    
    
    public function resetMID() {
        $this->_setParam('mid', 0);
        $this->internal->params->mid = 0;
        $this->target->currentModule = new StdClass;
        $this->target->currentModule->module_id = $this->module_modules[$this->getRequest()->getModuleName()]->id;
        $this->target->currentModule->counter = 0;
        $this->target->currentModule->display = null;
        $this->target->currentModule->m_name = $this->module_modules[$this->getRequest()->getModuleName()]->name;
        $this->target->currentModule->m_display = $this->module_modules[$this->getRequest()->getModuleName()]->display;
        
        $this->target->currentModule->content = null;
        $this->target->currentModule->interactive = null;
        $this->target->currentModule->community_mask = null;
        $this->target->currentModule->network_mask = null;
        $this->target->currentModule->profile_mask = null;
        $this->target->currentModule->mask_counter = null;
    }
    
    
    public function addProfileSymbolClauses($select)
    {
        // Some of the same code in the default widget and system/widget controller
        
        // Profile ID clause
        if ($this->_getParam('profile_id')) {
            $wheres = array();
            foreach (preg_split('/[,\s]+/', $this->_getParam('profile_id')) as $profile_id) {
                $profile_id = trim($profile_id);
                if (is_numeric($profile_id)) {
                    $wheres[] = $this->db->quoteInto('obj.profile_id=?', $profile_id);
                }
            }
            if (count($wheres)) {
                $select->where(join(' OR ', $wheres));
            }
        }
        
        // Look up by symbol or symbol id
        if ($this->_getParam('symbol') || $this->_getParam('symbol_id')) {
            $select_symbols = $this->db->select()
                ->from(array('obj' => 'quote_view_symbol_matrix'))
                ->where('obj.active = ?', 't');
                
            $wheres = array();
            if ($this->_getParam('symbol')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('symbol')) as $symbol) {
                    $symbol = trim($symbol);
                    if ($symbol) {
                        $wheres[] = $this->db->quoteInto('obj.internal_symbol=?', $symbol);
                    }
                }
            }
            if ($this->_getParam('symbol_id')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('symbol_id')) as $symbol_id) {
                    $symbol_id = trim($symbol_id);
                    if (is_numeric($symbol_id)) {
                        $wheres[] = $this->db->quoteInto('obj.id=?', $symbol_id);
                    }
                }
            }
            if (count($wheres)) {
                $select_symbols->where(implode(' OR ', $wheres))->limit(100);
            }
            else {
                $select_symbols->limit(1);
            }
            
            $symbols = $this->db->fetchAll($select_symbols);
            if (count($symbols)) {
                $wheres = array();
                foreach ($symbols as $symbol) {
                    $wheres[] = '(' . $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $symbol->id) . 'obj.search @@ plainto_tsquery(' . $this->db->quote($symbol->internal_symbol) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $symbol->name))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($symbol->internal_symbol) . " || E'((,|\\\\s)+|$)'))" . ')';
                }
                if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }    
            }
        }
        
        // Look up by follows - Profiles or symbols
        if ($this->_getParam('following')) {
            $wheres = array();
            
            if ($this->_getParam('following') == 'profiles' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.profile_id IN (SELECT follow_profile_id FROM profile_follow_matrix WHERE status='t' AND active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            if ($this->_getParam('following') == 'symbols' || $this->_getParam('following') == 'profilesandsymbols') {
                $wheres[] = $this->db->quoteInto("obj.symbols && (SELECT array_accum(symbol_id) FROM quote_follow_matrix WHERE active='t' AND profile_id=?)", (isset($this->member->profile->id) ? $this->member->profile->id : 0));
            }
            
            if (count($wheres)) { $select->where(implode(' OR ', $wheres)); }   
        }
        
        return $select;
    }
}
