<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Controller_Default_Widget extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    #protected $_moduleInMatrix = true; // Not for widgets
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_READ;
    protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
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
        if (!$contextSwitch->hasContext('sitemap')) {
            $contextSwitch->addContext('sitemap', 
                array(
                    #'suffix'    => 'rss',
                    #'headers'   => array('Content-Type' => 'application/xml')
                    'headers'   => array('Content-Type' => 'text/plain')
                )
            );
        }
        $contextSwitch
            ->addActionContext('index', array('html', 'atom', 'rss', 'sitemap'))
            ->addActionContext('profile', array('html', 'atom', 'rss', 'sitemap'))
            ->addActionContext('symbol', array('html', 'atom', 'rss', 'sitemap'))
            ->setAutoJsonSerialization(false)
            ->initContext();
            
        #$this->_helper->layout->disableLayout();
    }
    
    
    #public function preDispatch() { } // Still need ACL for widgets?
    public function preDispatch()
    {
        if (!($this->getRequest()->getModuleName() == 'system' && $this->getRequest()->getControllerName() == 'widget')) {
            if ($this->target->type == 'COM' && !$this->_getParam('mid') && !$this->_getParam('profile') && ($this->_getParam('symbol') || $this->_getParam('symbol_id')) && isset($this->module_modules[$this->getRequest()->getModuleName()])) {
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
        }
        
        parent::preDispatch();
    }
    
    public function indexAction($select = null)
    {
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        $widget = $this->_getParam('widget');
        
        if (isset($this->_allModuleSelect) && $this->_allModuleSelect) {
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $this->_allModuleSelect->__toString() . ')')), array('*'))
            ;

            $this->_setParam('mid', 0);
            /*
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
            */
            $widget = new StdClass();
              $widget->creation = $this->target->space->creation;
              $widget->updated = $this->target->space->updated;
              $widget->display = ($this->target->space->name ? $this->target->space->name : $this->target->space->name);
              $widget->interactive = true;
        }
        
        if (!$widget && $this->_getParam('mid', 0)) {
            foreach ($this->target->modules as $module) {
                if ($module->module_id == $this->module_modules[$this->getRequest()->getModuleName()]->id && $module->counter == $this->_getParam('mid', 0)) {
                    $widget = $module;
                    break;
                }
            }
        }
        
        if ($widget) {
            if (!$select) {
                $select = $this->_buildComplexQuery($this->_tableName, $widget);
                
                /* Start Custom By Profile or by Symbols */
                $select = $this->addProfileSymbolClauses($select);
                /* End Custom By Profile or by Symbols */
                
                // Wrap the select - intersect with quote_view_data and easier params
                $select = $this->db->select()
                  ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
                ;
            }


            /* Begin Finishing Touches On Wrapped Select */
                #select title,symbols from article_articles where via_id=2 and matrix_counter=1 and symbols && (select array_accum(id) from quote_view_data where id=62230);
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
                
                // Need to leave the coalesce as the default sort order because in system widget, when joining with comments, comments do NOT have the published_display_date
                $select->order(array(
                        ($this->_getParam('w_order') ? $this->_getParam('w_order', 'COALESCE(published_display_date, modified, creation)') : 'COALESCE(published_display_date, modified, creation)') . ' ' . $this->_getParam('w_order_direction', 'DESC'),
                        'COALESCE(published, creation) DESC'
                    ))
                    ->limit($this->_getParam('w_limit', ((isset($widget->widget_max) && $widget->widget_max > 0) ? $widget->widget_max : 5)))
                ;
            /* End Finishing Touches On Wrapped Select */
            
            
            $this->view->widget = $widget;
            
            $this->view->objects = $this->db->fetchAll($select);
            
            if (
              (
                (isset($widget->community_mask) && $widget->community_mask) ||
                (isset($widget->network_mask) && $widget->network_mask) ||
                (isset($widget->profile_mask) && $widget->profile_mask)
              ) &&
              ($widget->mask_counter)
            ) {
                $this->view->masked = $widget->counter;
                $this->masked = $widget->counter;
            }
            
            if ($contextSwitch->getCurrentContext() && $contextSwitch->getCurrentContext() != 'html') {
                $published = new Zend_Date();
                $published->set($widget->creation, Zend_Date::ISO_8601);
                if (isset($this->internal->member)) {
                    $published->setTimezone($this->internal->member->timezone);
                }
                else {
                    $published->setTimezone($this->internal->config->timezone);
                }
                
                $lastUpdate = new Zend_Date();
                $lastUpdate->set($widget->updated, Zend_Date::ISO_8601);
                if (isset($this->internal->member)) {
                    $lastUpdate->setTimezone($this->internal->member->timezone);
                }
                else {
                    $lastUpdate->setTimezone($this->internal->config->timezone);
                }
                
                $metas = array('author', 'copyright');
                foreach ($this->view->headMeta()->getContainer()->getArrayCopy() as $key => $val) {
                    if (in_array($val->name, $metas)) {
                        $metas[$val->name] = $val->content;
                    }
                    
                }
                
                // Create the link
                $link = 'http://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s';
                if (isset($this->masked) && $this->masked) {
                    $link .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->masked . '/format/' . $contextSwitch->getCurrentContext() . '/';
                }
                else {
                    if (isset($widget->com_id) && $widget->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $widget->com_id != $this->internal->community->id)) { $link .= '/com/' . $widget->com_id; }
                    elseif (isset($widget->net_id) && $widget->net_id) { $link .= '/net/' . $widget->net_id; }
                    elseif (isset($widget->via_id) && $widget->via_id) { $link .= '/via/' . $widget->via_id; }
                    
                    $link .= '/' . $this->getRequest()->getModuleName() . '/widget/p' . (isset($widget->counter) && $widget->counter ? '/mid/' . $widget->counter : '') . '/format/' . $contextSwitch->getCurrentContext() . '/';
                    
                    if ($_SERVER['SCRIPT_URI'] != $link && strcasecmp($_SERVER['SCRIPT_URI'], $link) ===0) {
                        $link = $_SERVER['SCRIPT_URI'];
                    }
                }
                
                // Build the Feed Array
                $array = array(
                    //required
                    'title' => ($widget->display ? $widget->display : $widget->m_display),
                    'link'  => $link,
                    'charset' => 'UTF-8',
                    'language' => 'en-US',
                    
                    // optional
                    // Apple News doesn't like published here?
                    //'published'  => $published->getTimestamp(),
                    'lastUpdate' => $lastUpdate->getTimestamp(),
                    
                    'description' => (isset($widget->meta_description) && $widget->meta_description ? $widget->meta_description : (isset($widget->m_description) && $widget->m_description ? $widget->m_description : '')),
                    'author'      => $metas['author'],
                    'email'       => (isset($this->community->email) && $this->community->email ? $this->community->email : $this->config->admin->email),
                    'copyright' => $metas['copyright'],
                    
                    'webmaster' => (isset($this->community->email) && $this->community->email ? $this->community->email : $this->config->admin->email),
                    
                    #'image'     => 'url to image',
                    #'generator' => 'generator'
                );
                
                foreach ($this->view->objects as $object) {
                    $published->set(($object->published_display_date ? $object->published_display_date : ($object->published ? $object->published : $object->creation)), Zend_Date::ISO_8601);
                    $lastUpdate->set(($object->published_display_date ? $object->published_display_date : ($object->published ? $object->published : $object->creation)), Zend_Date::ISO_8601);
                    #$lastUpdate->set(($object->modified ? $object->modified : ($object->published ? $object->published : $object->creation)), Zend_Date::ISO_8601);
                    
                    // Create the link
                    /*
                    $link = 'http://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($object->title) . '/s';
                    if ($this->masked) {
                        #$link .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->masked . '/id/' . $object->counter . '/';
                        $link .= $this->internal->target->pre . '/' . $this->internal->module_modules[$object->module_id]->name . '/view/p/mid/' . $this->masked . '/id/' . $object->counter . '/';
                    }
                    else {
                        if ($object->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $object->com_id != $this->internal->community->id)) { $link .= '/com/' . $object->com_id; }
                        elseif ($object->net_id) { $link .= '/net/' . $object->net_id; }
                        elseif ($object->via_id) { $link .= '/via/' . $object->via_id; }
                        
                        #$link .= '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $object->matrix_counter . '/id/' . $object->counter . '/';
                        $link .= '/' . $this->internal->module_modules[$object->module_id]->name . '/view/p/mid/' . $object->matrix_counter . '/id/' . $object->counter . '/';
                    }
                    */
                    #$this->view->masked = $this->masked;
                    $this->view->module = $this->internal->module_modules[$object->module_id]->name;
                    $link = $this->view->ContentLink(array('object' => $object, 'view' => $this->view));
                    
                    $oarray = array(
                        //required
                        'title' => iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', ($object->title ? $object->title : 'No Title') . ($object->active == false ? ' (Draft Status)' : '')),
                        'link'  => $link,
                        
                        'keywords' => trim(isset($object->meta_keywords) && $object->meta_keywords ? $object->meta_keywords : ''),
                        'symbols' => trim(isset($object->symbols) && $object->symbols ? $object->symbols : ''),
                        
                        // required, only text, no html
                        //  Took out the object description as a fallback to sync with the html widget
                        #'description' => (isset($object->summary) && $object->summary ? $object->summary : (isset($object->description) && $object->description ? $object->description : (isset($object->content) && $object->content ? $this->view->SEO_Quip($object->content, 256) : ''))),
                        
                        # Trying to get Apple NewsPublisher to work
                        #'description' => (isset($object->summary) && $object->summary ? html_entity_decode($object->summary) : (isset($object->content) && $object->content ? html_entity_decode($this->view->SEO_Quip($object->content, 256), ENT_QUOTES, 'UTF-8') : '')) . "<p><a href=\"$link\">" . $this->internal->community->display . ": $object->title</a></p>",
                        #'description' => (isset($object->summary) && $object->summary ? html_entity_decode($object->summary) : (isset($object->content) && $object->content ? html_entity_decode($this->view->SEO_Quip($object->content, 256), ENT_QUOTES, 'UTF-8') : '')) . "<p><a href=\"$link\">" . $this->internal->community->display . ": $object->title</a></p>",
                        
                        
                        'description' => iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', 
                            isset($object->summary) && $object->summary ?
                                $object->summary
                                :
                                (
                                    isset($object->content) && $object->content ?
                                    $this->view->SEO_Quip($object->content, 256)
                                    :
                                    $object->title
                                )
                        ),
                        
                        
                        
                        #html_entity_decode(mb_convert_encoding(stripslashes($name), "HTML-ENTITIES", 'UTF-8'))
                        
                        
                        /* May need to use this someday...
                            $text = iconv("UTF-8","UTF-8//IGNORE",$text);  */
                        
                        
                        // optional
                        'guid' => $link,

                        #'content' => $object->content,
                        
                        'published' => $published->getTimestamp(),
                        'lastUpdate' => $lastUpdate->getTimestamp(),
                        
                        #'category' => array(array('term' => 'whatisthisanyways', 'scheme' => 'schemerschemer', 'attributes' => array('a'=>'b', 'c'=>'d', 'e'=>'f'))),
                        
                        #'comments'   => 'comments page of the feed entry',
                        #'commentRss' => 'the feed url of the associated comments',
                        
                        #'author' => array(
                        #    // required
                        #    'name' => $object->p_name,
                        #    'email' => 'test@test.com',
                        #    'url'   => 'http://www.yahoo.com'
                        #),
                        
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
                
                // Load up stock quotes
                if (($this->_getParam('gnews') || $this->_getParam('bzn') || $this->_getParam('twitter')) &&
                  $this->internal->community->meta_stocks && !$this->_getParam('commentsonly')) {
                    $symbol_ids = $stock_quotes = array();
                    foreach ($array['entries'] as $item) {
                        $arr_res = $this->VMAH->ePgArray($item['symbols']);
                        if ($arr_res && count($arr_res)) {
                            foreach ($arr_res as $temp_id) { $symbol_ids[] = $temp_id; }
                        }
                    }
                    $symbol_ids = array_unique($symbol_ids);
                    
                    // Construct the query
                    if ($symbol_ids) {
                        $query = "SELECT qs.id AS id, qe.google_exchange_pre || (CASE (qe.google_exchange_pre NOTNULL AND qe.google_exchange_pre != '') WHEN 't' THEN ':' ELSE '' END) AS google_exchange, qs.symbol AS symbol FROM quote_symbols qs, quote_exchanges qe WHERE qs.exchange_id=qe.id AND qs.id IN (" . (implode(',', $symbol_ids)) . ")";
                        
                        foreach ($this->db->fetchAll($query) as $temp) {
                            $stock_quotes[(string) $temp->id] = $temp;
                        }
                    }
                }
                
                if ($contextSwitch->getCurrentContext() == 'sitemap') {
                    // Sitemap Format
                    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
                    echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
                    if ($this->_getParam('gnews')) {
                        echo ' xmlns:news="http://www.google.com/schemas/sitemap-news/0.9"';
                    }
                    echo '>' . "\n";
                    
                    foreach ($array['entries'] as $item) {
                        // Fix-up
                        $arr_res = $this->VMAH->ePgArray($item['symbols']);
                        if ($arr_res && count($arr_res)) {
                            $holder = array();
                            foreach ($arr_res as $temp_id) {
                                if (isset($stock_quotes[(string) $temp_id]) && $stock_quotes[(string) $temp_id]) {
                                    $holder[] = $stock_quotes[(string) $temp_id]->google_exchange . $stock_quotes[(string) $temp_id]->symbol;
                                    if (count($holder) >=5) { break; }
                                }
                            }
                            if (count($holder)) {
                                $item['symbols'] = implode(', ', $holder);
                            }
                            else {
                                $item['symbols'] = '';
                            }
                        }
                        else {
                            $item['symbols'] = '';
                        }
?>
    <url>
        <loc><?= $item['link'] ?></loc>
        <lastmod><?= date('c', $item['lastUpdate']) ?></lastmod>
<?php if ($this->_getParam('gnews')) { ?>
        <news:news>
            <news:publication>
                <news:name><?= htmlspecialchars($this->internal->community->display) ?></news:name>
                <news:language>en</news:language>
            </news:publication>
            <news:publication_date><?= date('c', $item['published']) ?></news:publication_date>
            <news:title><?= htmlspecialchars($item['title']) ?></news:title>
<?php if ($item['keywords']) { ?>
            <news:keywords><?= htmlspecialchars($item['keywords']) ?></news:keywords>
<?php } ?>
<?php if ($this->internal->community->meta_stocks && $item['symbols']) { ?>
            <news:stock_tickers><?= $item['symbols'] ?></news:stock_tickers>
<?php } ?>
        </news:news>
<?php } ?>
    </url>
<?php
                    }
                    
                    echo '</urlset> ' . "\n";

                }
                else {
                    // RSS and ATOM Format
                    if ($this->_getParam('bzn') && $this->internal->community->meta_stocks && !$this->_getParam('commentsonly')) {
                        // Add the ticker categories for Benzinga
                        foreach ($array['entries'] as &$item) {
                            $arr_res = $this->VMAH->ePgArray($item['symbols']);
                            if ($arr_res && count($arr_res)) {
                                foreach ($arr_res as $temp_id) {
                                    if (!isset($item['category']) || !is_array($item['category'])) {
                                        $item['category'] = array();
                                    }
                                    $item['category'][] = array('term' => $stock_quotes[(string) $temp_id]->symbol, 'scheme' => 'tickers');
                                    
                                }
                            }
                        }
                    }
                    elseif ($this->_getParam('twitter')) {
                        // Add the ticker and hashtags to Twitter Titles
                        if ($this->internal->community->meta_stocks && !$this->_getParam('commentsonly')) {
                            foreach ($array['entries'] as &$item) {
                                $arr_res = $this->VMAH->ePgArray($item['symbols']);
                                if ($arr_res && count($arr_res)) {
                                    foreach ($arr_res as $temp_id) {
                                        if (!isset($item['category']) || !is_array($item['category'])) {
                                            $item['category'] = array();
                                        }
                                        $item['title'] .= ' $' . $stock_quotes[(string) $temp_id]->symbol;
                                    }
                                }
                            }
                        }
                        
                        if (isset($item['keywords']) && $item['keywords']) {
                            foreach ($array['entries'] as &$item) {
                                $keywords =  preg_replace('/[^a-zA-Z0-9]+/', '', explode(',', $item['keywords']));
                                for ($i = 0; $i < count($keywords) && $i < 3; $i++) {
                                    $item['title'] .= ' #' . htmlspecialchars($keywords[$i]);
                                }
                            }
                        }
                    }
                    
                    $feed = Zend_Feed::importBuilder(new Zend_Feed_Builder($array), $contextSwitch->getCurrentContext());
                    #$feed = Zend_Feed::importArray($array, $contextSwitch->getCurrentContext());
                    $feed->send();
                }
                
                $this->_helper->viewRenderer->setNoRender();
            }
            else {
                // html feed; output in view script; add headlinks
                // Create the link
                
                /*  IS THIS NECESSARY ???  THIS WOULD BE FOR BUILDING A SYSTEM/WIDGET HTML PAGE - CURRENTLY THERE IS NONE.
                
                $link = 'http://' . $this->internal->vars->host . '/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s';
                if (isset($widget->interactive, $widget->masked, $widget->x_interactive) && $widget->interactive && $widget->masked ? $widget->x_interactive : true) {
                    $clink = $link;
                }
                if ($this->masked) {
                    #$link .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/widget/p/mid/' . $this->masked . '/format';
                    #if (isset($clink)) { $clink .= $this->internal->target->pre . '/' . $this->getRequest()->getModuleName() . '/comments/widget/p/mid/' . $this->masked . '/format'; }
                    $link .= $this->internal->target->pre . '/' . $this->internal->module_modules[$object->module_id]->name . '/widget/p/mid/' . $this->masked . '/format';
                    if (isset($clink)) { $clink .= $this->internal->target->pre . '/' . $this->internal->module_modules[$object->module_id]->name . '/comments/widget/p/mid/' . $this->masked . '/format'; }
                }
                else {
                    if ($widget->com_id && ((isset($this->internal->params->com_id) && $this->internal->params->com_id) || $widget->com_id != $this->internal->community->id)) {
                        $link .= '/com/' . $widget->com_id;
                        if (isset($clink)) { $clink .= '/com/' . $widget->com_id; }
                    }
                    elseif ($widget->net_id) {
                        $link .= '/net/' . $widget->net_id;
                        if (isset($clink)) { $clink .= '/net/' . $widget->net_id; }
                    }
                    elseif ($widget->via_id) {
                        $link .= '/via/' . $widget->via_id;
                        if (isset($clink)) { $clink .= '/via/' . $widget->via_id; }
                    }
                    
                    #$link .= '/' . $this->getRequest()->getModuleName() . '/widget/p' . ($widget->counter ? '/mid/' . $widget->counter : '') . '/format';
                    #if (isset($clink)) { $clink .= '/' . $this->getRequest()->getModuleName() . '/comments/widget/p' . ($widget->counter ? '/mid/' . $widget->counter : '') . '/format'; }
                    
                    $link .= '/' . $this->internal->module_modules[$widget->module_id]->name . '/widget/p' . ($widget->counter ? '/mid/' . $widget->counter : '') . '/format';
                    if (isset($clink)) { $clink .= '/' . $this->internal->module_modules[$widget->module_id]->name . '/comments/widget/p' . ($widget->counter ? '/mid/' . $widget->counter : '') . '/format'; }
                }
                */
                
                /*
                foreach (array_intersect_key($contextSwitch->getContexts(), array_flip($contextSwitch->getActionContexts($this->getRequest()->getActionName()))) as $key => $val) {
                    if ($key == 'html') { continue; }
                    // Skip adding for current modules - those will be added in the controller; usually index and view
                    if (isset($this->target->currentModule) && $this->target->currentModule->module_id==$widget->module_id && $this->target->currentModule->counter==$widget->counter) { continue; }
                    $this->view->headLink()->appendAlternate(
                        "$link/$key/",
                        ($val['headers']['Content-Type'] ? $val['headers']['Content-Type'] : 'text/html'),
                        ($widget->display ? $widget->display : $widget->m_display) . ' - ' . strtoupper($key) . ' Feed'
                    );
                    if (isset($clink)) {
                        $this->view->headLink()->appendAlternate(
                            "$clink/$key/",
                            ($val['headers']['Content-Type'] ? $val['headers']['Content-Type'] : 'text/html'),
                            ($widget->display ? $widget->display : $widget->m_display) . ' (Comments) - ' . strtoupper($key) . ' Feed'
                        );
                    }
                }
                */
            }
            
            /*
            if ($contextSwitch->getCurrentContext()) {
                #$this->_helper->viewRenderer->setNoRender();
        
                switch ($contextSwitch->getCurrentContext()) {
                    case 'atom':
                        break;
                    #case 'json':
                    #    echo 'json';
                    #    break;
                    #case 'xml':
                    #    echo 'xml';
                    #    break;
                    default:
                        break;
                }
            }
            */
            
            if ($this->_getParam('displayInCm') && $widget && !$this->getParam('delayDisplayInCmCheck')) { return $this->displayInCm($widget); }
        }
        else {
            $this->_helper->viewRenderer->setNoRender();
        }
    }
    
    
    public function displayInCm($widget)
    {
        $this->render(null, 'tempInCm');
        $tempInCm = $this->getResponse()->getBody('tempInCm');
        $this->getResponse()->clearBody('tempInCm');
        
        //Zend_Debug::Dump($widget);
        
        // This is similar to the DisplayWidget view helper.  Keep synced.
        $spec = $widget->module_id;
        $counter = $widget->counter;
        $id = "m-$spec-$counter";
        
        $data = array(
            'id' => $id,
            'class' => 'cm decorated widget',
            'extra' => '',
            'bd' => $tempInCm
        );                

        /* ACL Check */
        if (
            (isset($this->target->owner) && $this->target->owner) ||
            (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) ||
            ($widget->acl === null || $widget->acl === true || $widget->show_on_fail === true) ||
            ($widget->allowed && $widget->privilege > 0 && $widget->recursive) ||
            ($this->target->acl->allowed && $this->target->acl->recursive)
        ) {
            $data['hd'] = '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/p/mid/' . $counter . '/">' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            if ($widget->secondary) {
                $data['hd2'] = ($widget->secondary_url ? '<a href="' . $this->view->escape($widget->secondary_url) . '">' : '') . $this->view->escape($widget->secondary) . ($widget->secondary_url ? '</a>' : '');
            }
            $data['ft2'] = '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/p/mid/' . $counter . '/">Get More: ' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            $data['extra'] .= '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/widget/p/mid/' . $counter . '/format/atom/" rel="nofollow" title="' . ($widget->display ? $widget->display : $widget->m_display) . '" class="feeder">' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            
            /* ACL Check */
            if (
                (isset($this->target->owner) && $this->target->owner) ||
                (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) ||
                ($widget->allowed && $widget->privilege >= ViaMe_Controller_Action::ACL_WRITE && $widget->recursive) ||
                ($this->target->acl->allowed && $this->target->acl->privilege >= ViaMe_Controller_Action::ACL_WRITE && $this->target->acl->recursive)
            ) {
                $data['ft'] = '<a href="' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/create/p/mid/' . $counter . '/" rel="nofollow" class="create">Create ' . $this->view->internal->module_modules[$spec]->display . '</a>';
                $widget->display_cm = true;
            } 
            #elseif (!$data['bd'] && isset($widget->total_item_count) && !$widget->total_item_count) {
            if (!$data['bd']) {
                // No content and no write auth, return nothing.
                $this->getResponse()->setHttpResponseCode(503)->sendHeaders();
                exit;
            }
            
            $data['class'] .= ' m_' . $this->view->internal->module_modules[$spec]->name;
        }
        else {
            $this->getResponse()->setHttpResponseCode(503)->sendHeaders();
            exit;
        }
        
        if ((isset($widget->display_cm) && $widget->display_cm) || $data['bd']) {
            echo $this->view->CM($data);
        }
        else {
            $this->getResponse()->setHttpResponseCode(503)->sendHeaders();
            exit;
        }
    }
    
    
    public function addProfileSymbolClauses($select)
    {
        // Some of the same code in the default index and system/widget controller
        
        // Profile ID clause
        if ($this->_getParam('w_profile_id')) {
            $wheres = array();
            foreach (preg_split('/[,\s]+/', $this->_getParam('w_profile_id')) as $profile_id) {
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
        if ($this->_getParam('w_symbol') || $this->_getParam('w_symbol_id')) {
            $select_symbols = $this->db->select()
                ->from(array('obj' => 'quote_view_symbol_matrix'))
                ->where('obj.active = ?', 't');
                
            $wheres = array();
            if ($this->_getParam('w_symbol')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('w_symbol')) as $symbol) {
                    $symbol = trim($symbol);
                    if ($symbol) {
                        $wheres[] = $this->db->quoteInto('obj.internal_symbol=?', $symbol);
                    }
                }
            }
            if ($this->_getParam('w_symbol_id')) {
                foreach (preg_split('/[,\s]+/', $this->_getParam('w_symbol_id')) as $symbol_id) {
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