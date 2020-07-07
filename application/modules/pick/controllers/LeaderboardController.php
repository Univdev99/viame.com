<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_LeaderboardController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'pick_picks';
    
    
    /* Don't Need Any PreDispatch Checks */
    public function preDispatch() { }
    
    
    public function indexAction($select = null)
    {
        
    }
    
    
    public function widgetAction($select = null)
    {
        $select = $this->db->select()
            ->from(array('obj' => $this->_tableName),
                array(
                    'module_id',
                    'com_id',
                    'net_id',
                    'via_id',
                    'matrix_counter',
                    
                    'COUNT(*) AS total_positions_count',
                    #'COUNT(NULLIF(position, 1)) AS total_positions_count_long',
                    #'COUNT(NULLIF(position, -1)) AS total_positions_count_short',
                    #'AVG(allocation) AS average_allocation',
                    #'MAX(allocation) AS max_allocation',
                    #'MIN(allocation) AS min_allocation',
                    #'AVG(risk) AS average_risk',
                    #'MAX(risk) AS max_risk',
                    #'MIN(risk) AS min_risk',
                    #'AVG(timeframe) AS average_timeframe',
                    #'MAX(timeframe) AS max_timeframe',
                    #'MIN(timeframe) AS min_timeframe',
                    #'AVG(NULLIF(COALESCE(open_price, open_temp_price), 0)) AS average_price',
                    #'MAX(NULLIF(COALESCE(open_price, open_temp_price), 0)) AS max_price',
                    #'MIN(NULLIF(COALESCE(open_price, open_temp_price), 0)) AS min_price',
                    
                    #'SUM(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * 100) AS total_gain_percentage',
                    #'AVG(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * 100) AS average_gain_percentage',
                    #'MAX(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * 100) AS max_gain_percentage',
                    #'MIN(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * 100) AS min_gain_percentage',
                    
                    'SUM(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * allocation) AS total_allocated_gain_percentage',
                    'AVG(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * allocation) AS average_allocated_gain_percentage',
                    'MAX(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * allocation) AS max_allocated_gain_percentage',
                    'MIN(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * allocation) AS min_allocated_gain_percentage',

                    #'COUNT(CASE WHEN ((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) > 0 THEN 1 ELSE NULL END) AS total_winners',
                    #'COUNT(CASE WHEN ((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) < 0 THEN 1 ELSE NULL END) AS total_losers',
                    #'AVG(suggested_stop_loss) AS average_ssl',
                    #'MIN(suggested_stop_loss) AS min_ssl',
                    #'MAX(suggested_stop_loss) AS max_ssl',
                    #'AVG((NULLIF(COALESCE(open_price, open_temp_price), 0) - suggested_stop_loss) / NULLIF(COALESCE(open_price, open_temp_price), 0) * position) AS average_ssl_percentage',
                    #'MIN((NULLIF(COALESCE(open_price, open_temp_price), 0) - suggested_stop_loss) / NULLIF(COALESCE(open_price, open_temp_price), 0) * position) AS min_ssl_percentage',
                    #'MAX((NULLIF(COALESCE(open_price, open_temp_price), 0) - suggested_stop_loss) / NULLIF(COALESCE(open_price, open_temp_price), 0) * position) AS max_ssl_percentage',
                    
                    #'AVG(target_price) AS average_target_price',
                    #'MAX(target_price) AS max_target_price',
                    #'MIN(target_price) AS min_target_price',
                    #'AVG((target_price - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) AS average_target_price_gain',
                    #'MAX((target_price - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) AS max_target_price_gain',
                    #'MIN((target_price - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) AS min_target_price_gain',
                    #'AVG(target_price - COALESCE(close_price, COALESCE(close_temp_price, qvd.last))) AS average_target_price_gain_to_actual',
                    #'MAX(target_price - COALESCE(close_price, COALESCE(close_temp_price, qvd.last))) AS max_target_price_gain_to_actual',
                    #'MIN(target_price - COALESCE(close_price, COALESCE(close_temp_price, qvd.last))) AS min_target_price_gain_to_actual',
                    
                    #'AVG(close_datestamp - open_datestamp) AS average_holding_period',
                    #'MAX(close_datestamp - open_datestamp) AS max_holding_period',
                    #'MIN(close_datestamp - open_datestamp) AS min_holding_period',
                    #'AVG(target_date - open_datestamp) AS average_target_holding_period',
                    #'MAX(target_date - open_datestamp) AS max_target_holding_period',
                    #'MIN(target_date - open_datestamp) AS min_target_holding_period',
                    #'AVG(target_date - close_datestamp) AS average_target_date_to_actual',
                    #'MAX(target_date - close_datestamp) AS max_target_date_to_actual',
                    #'MIN(target_date - close_datestamp) AS min_target_date_to_actual',
                )
            )
          
            ->join(array('qvd' => 'quote_view_data'), 'obj.symbol_id=qvd.id', array())
            
            ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter', array('x_display' => 'display'))
            ->where('x.active=?', 't')
            ->where('x.acl ISNULL or x.acl=?', 't')
            
            ->join(array('m' => 'module_modules'), 'x.module_id=m.id', array('m_display' => 'display', 'm_name' => 'name'))
            ->where('m.active=?', 't')
            
            ->join(array('p' => 'profile_profiles'), 'x.profile_id = p.id',
                array(
                    'p_id' => 'id',
                    'p_name' => 'name',
                    'p_site_admin' => 'site_admin',
                    'p_active' => 'active',
                    'p_picture_url' => 'picture_url',
                    'p_total_followers_count' => 'total_followers_count',
                    'p_total_following_count' => 'total_following_count'
                )
            )
            ->where('p.active=?', 't')
            
            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email'
                )
            )
            ->where('b.active=?', 't')
            
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                )
            )
            ->where('c.active=?', 't')
        ;
        
        $member_grouping = array();
        if (isset($this->member)) {
            $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                array(
                    'vc_status' => 'status',
                    'vc_display' => 'display'
                )
            );
            $select->joinLeft(array('pfm' => 'profile_follow_matrix'), $this->db->quoteInto("p.id = pfm.follow_profile_id AND pfm.profile_id=? AND pfm.status='t' AND pfm.active='t'", $this->member->profile->id),
                array(
                    'pfm_following' => 'active'
                )
            );
            
            $member_grouping = array('vc.status', 'vc.display', 'pfm.active');
        }
        
        $select
            ->group(array_merge(array('obj.module_id', 'obj.com_id', 'obj.net_id', 'obj.via_id', 'obj.matrix_counter', 'x.display', 'm.display', 'm.name', 'p.id', 'p.name', 'p.site_admin', 'p.active', 'p.picture_url', 'p.total_followers_count', 'p.total_following_count', 'b.id', 'b.site_admin', 'b.active', 'b.email', 'c.id', 'c.name', 'c.hostname'), $member_grouping))
            ->having('SUM(((COALESCE(close_price, COALESCE(close_temp_price, qvd.last)) - NULLIF(COALESCE(open_price, open_temp_price), 0)) * position) / NULLIF(COALESCE(open_price, open_temp_price), 0) * 100) NOTNULL')
        ;
        
        // General where insertion
        if ($this->_getParam('vmqa_where')) {
            $select->where($this->_getParam('vmqa_where'));
        }
        
        $select->order($this->_getParam('w_order', 'total_allocated_gain_percentage') . ' ' . $this->_getParam('w_order_direction', 'DESC'))
            ->limit($this->_getParam('w_limit', 5))
        ;
        
        $this->view->objects = $this->db->fetchAll($select);
        
        
        if ($this->_getParam('displayInCm')) {
            $this->render(null, 'tempInCm');
            $tempInCm = $this->getResponse()->getBody('tempInCm');
            $this->getResponse()->clearBody('tempInCm');
            
            $data = array(
                'id' => 'pick-leaderboard',
                'class' => 'cm decorated widget m_pick-leaderboard',
                'extra' => '',
                'bd' => $tempInCm
            );                
            
            /*
            $data['hd'] = '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/p/mid/' . $counter . '/">' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            if ($widget->secondary) {
                $data['hd2'] = ($widget->secondary_url ? '<a href="' . $this->view->escape($widget->secondary_url) . '">' : '') . $this->view->escape($widget->secondary) . ($widget->secondary_url ? '</a>' : '');
            }
            $data['ft2'] = '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/p/mid/' . $counter . '/">Get More: ' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            $data['extra'] .= '<a href="/' . $this->view->SEO_Urlify($widget->display ? $widget->display : $widget->m_display) . '/s' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/widget/p/mid/' . $counter . '/format/atom/" rel="nofollow" title="' . ($widget->display ? $widget->display : $widget->m_display) . '" class="feeder">' . ($widget->display ? $widget->display : $widget->m_display) . '</a>';
            
            if (
                (isset($this->target->owner) && $this->target->owner) ||
                (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) ||
                ($widget->allowed && $widget->privilege >= ViaMe_Controller_Action::ACL_WRITE && $widget->recursive) ||
                ($this->target->acl->allowed && $this->target->acl->privilege >= ViaMe_Controller_Action::ACL_WRITE && $this->target->acl->recursive)
            ) {
                $data['ft'] = '<a href="' . $this->view->internal->target->pre . '/' . $this->view->internal->module_modules[$spec]->name . '/create/p/mid/' . $counter . '/" rel="nofollow" class="create">Create ' . $this->view->internal->module_modules[$spec]->display . '</a>';
                $widget->display_cm = true;
            } 
            
            if (!$data['bd']) {
                // No content and no write auth, return nothing.
                $this->getResponse()->setHttpResponseCode(503)->sendHeaders();
                exit;
            }
            */
            
            if ($data['bd']) {
                echo $this->view->CM($data);
            }
            else {
                $this->getResponse()->setHttpResponseCode(503)->sendHeaders();
                exit;
            }
        }
    }
}