<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Widget_Widgets_FeedController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;

    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        if ($widget = $this->_getParam('widget')) {
            foreach (@explode($this->config->delimiter, $widget->parameter_values_delimited) as $param) {
                $tokens = preg_split('/:/', $param, 2);
                if ($tokens[0]) { $params[$tokens[0]] = (isset($tokens[1]) && $tokens[1] ? $tokens[1] : null); }
            }
            
            $table = 'widget_widgets_feed';
            
            $row = $feed = null;
            
            $row = $this->db->fetchRow("SELECT data, ((CEIL(EXTRACT(EPOCH FROM now())) > (wwf.modified + wwf.lifetime)) OR (CEIL(EXTRACT(EPOCH FROM wm.updated)) > wwf.modified)) AS expired FROM widget_widgets_feed wwf, widget_matrix wm, widget_widgets ww WHERE (wwf.com_id=wm.com_id AND wwf.net_id=wm.net_id AND wwf.via_id=wm.via_id AND wwf.widget_counter=wm.counter AND wm.widget_id=ww.id AND ww.id=?) AND wwf.com_id=? AND wwf.net_id=? AND wwf.via_id=? AND wwf.widget_counter=?", array($widget->widget_id, $widget->com_id, $widget->net_id, $widget->via_id, $widget->counter));
            
            if (isset($row) && isset($row->data) && $row->data) {
                $feed = Zend_Feed_Reader::importString($row->data);
            }
            
            if (!$feed || (isset($row) && (!$row->data || $row->expired))) {
                try {
                    // Change the Adapter to Curl First.  The Zend Socket implementation doesn't work that good.
                    //  Get invalid chunk encoding and parsing of the XML gets screwed up sometimes, too.
                    Zend_Feed::setHttpClient(new Zend_Http_Client(
                        null,
                        array(
                            'adapter'     => 'Zend_Http_Client_Adapter_Curl'
                        )
                    ));
                    
                    if ($feed_load = Zend_Feed::import($params['dp_feed_url'])) {
                        # HTML Purify the feed
                        $HTMLPurify = new ViaMe_Filter_HTMLPurify();
                        
                        if (isset($feed_load->title)) { $feed_load->title = $HTMLPurify->filter($feed_load->title); }
                        if (isset($feed_load->subtitle)) { $feed_load->subtitle = $HTMLPurify->filter($feed_load->subtitle); }
                        if (isset($feed_load->description)) { $feed_load->description = $HTMLPurify->filter($feed_load->description); }
                        
                        for ($i = 0; $i < $feed_load->count(); $i++) {
                            $entry = $feed_load->current();
                            
                            if (isset($entry->title)) { $entry->title = $HTMLPurify->filter($entry->title); }
                            if (isset($entry->summary)) { $entry->summary = $HTMLPurify->filter($entry->summary); }
                            if (isset($entry->description)) { $entry->description = $HTMLPurify->filter($entry->description); }
                            if (isset($entry->content)) { $entry->content = $HTMLPurify->filter($entry->content); }
                            
                            $feed_load->next();
                        }
                        
                        // Purge Old Widget Data - Garbage Collection
                        $this->db->query("DELETE FROM $table WHERE (CEIL(EXTRACT(EPOCH FROM now())) > (modified + lifetime)) AND com_id=? AND net_id=? AND via_id=?", array($widget->com_id, $widget->net_id, $widget->via_id));
                        if (isset($row)) {
                            $this->db->query("DELETE FROM $table WHERE com_id=? AND net_id=? AND via_id=? AND widget_counter=?", array($widget->com_id, $widget->net_id, $widget->via_id, $widget->counter));
                        }
                        
                        $this->db->query("INSERT INTO $table (com_id, net_id, via_id, widget_counter, lifetime, data) VALUES (?, ?, ?, ?, ?, ?)",
                            array(
                                $widget->com_id,
                                $widget->net_id,
                                $widget->via_id,
                                $widget->counter,
                                (isset($params['dp_ttl']) && $params['dp_ttl'] ? $params['dp_ttl'] * 3600 : 3600),
                                (mb_check_encoding($feed_load->saveXML(), 'UTF-8') ? $feed_load->saveXML() : mb_convert_encoding($feed_load->saveXML(), 'UTF-8'))
                            )
                        );
                        
                        $feed = Zend_Feed_Reader::importString($feed_load->saveXML());
                    }
                } catch (Exception $e) { }
            }
            
            if (isset($feed) && $feed) {
                $this->view->feed = $feed; // Render in view script
                $this->view->widget = $widget;
                $this->view->params = $params;
            }
            else {
                $this->_helper->viewRenderer->setNoRender(); // Return Nothing
                echo 'Feed Error';
            }
        }
    }
}