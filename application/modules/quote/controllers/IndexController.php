<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Quote_IndexController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    
    public function preDispatch() { }
    
    
    public function indexAction()
    {
        $quote_functions = array(
            'default' => array(
            
            ),
            'quote' => array(
            
            ),
            'rating' => array(
            
            ),
            'content' => array(
            
            ),
            'news' => array(
            
            ),
            'links' => array(
            
            )
        );
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        $form = new Zend_Form(array(
            'attribs' => array(
                'class' => 'quote_form',
                'method' => 'get',
                'action' => $this->internal->target->pre . '/quote/'
            ),
            'elements' => array(
                's' => array('Text', array(
                'label' => 'Stock Symbol',
                'id' => 'quote_symbol',
                'description' => '<a href="' . $this->internal->target->pre . '/quote/lookup/">Symbol Lookup</a>',
                'class' => 'vmfh_acss autogo',
                'order' => 1
            )),
            )
        ));
        $form->getElement('s')->getDecorator('description')->setEscape(false);
        $this->view->form = $form;
        
        $this->view->headTitle('Quote', 'PREPEND');
        
        // Should adjust to get company information from database and quote data from feed
        
        if($this->_getParam('s') && $form->isValid($this->_getAllParams())) {
            $quotes = new ViaMe_Vm_Quotes();
            if ($results = $quotes->fetch($this->_getParam('s'), 'snxl1ght1jkc1p2vpa2oj1bb6raa5et8dy')) {
                $this->view->results = $results[0];
            }
            else {
            	if(strtoupper($this->_getParam('s')) == "MGXR.CN")
  				{
					$new_symbole = "MGXRCN.V";
				}
				else if(strtoupper($this->_getParam('s')) == "NLR.CN")
  				{
					$new_symbole = "NLRCN.V";
				}
				else
				{
					$new_symbole = strtoupper($this->_getParam('s'));
				}  
				//$new_symbole = strtoupper($this->_getParam('s'));
				       	            	
                if ($all = $this->db->fetchAll("SELECT * FROM quote_view_data WHERE internal_symbol=?", $new_symbole)) {
                	$oldData = $all[0];
                    $this->view->results = [
                        $oldData->internal_symbol, // Symbol - 0
                        $oldData->name, // Company Name - 1
                        $oldData->exchdisp, // Stock Exchange - 2
                        $oldData->last, // Last - 3
                        $oldData->day_low, // Day Low - 4
                        $oldData->day_high, // Day High - 5
                        'N/A', // Last Trade Time - 6
                        $oldData->year_low, // 52Week Low - 7
                        $oldData->year_high, // 52Week High - 8
                        sprintf("%.2f", $oldData->last - $oldData->previous_close), // Change - 9
                        sprintf("%.2f%%", ($oldData->last - $oldData->previous_close) / $oldData->previous_close * 100), // Change In Percent - 10
                        $oldData->volume, // Volume - 11
                        $oldData->previous_close, // Previous Close - 12
                        $oldData->average_daily_volume, // Average Daily Volume - 13
                        $oldData->open, // Open - 14
                        $oldData->market_cap, // Market Cap - 15
                        $oldData->bid, // Bid - 16
                        $oldData->bid_size, // Bid Size - 17
                        $oldData->pe_ratio, // PE Ratio - 18
                        $oldData->ask, // Ask - 19
                        $oldData->ask_size, // Ask Size - 20
                        $oldData->earnings_share, // Earnings Per Share - 21
                        'N/A', // 1 Year Target Price - 22
                        $oldData->dividend_share, // Dividend Per Share - 23
                        $oldData->dividend_yield, // Dividend Yield - 24
                    ];
                    
                    
                    
                    // Every 6 Hours, we will try to get a little fresher data and save it if we get any
                    function readYahoo($symbol, $tsStart, $tsEnd) {
                       if($symbol == 'NLRCN.V') $symbol = 'NLR.CN';
						if($symbol == 'GYNAF.V') $symbol = 'GYNAF';
						if($symbol == 'MGXRCN.V') $symbol = 'MGXR.CN';
			 preg_match('"CrumbStore\":{\"crumb\":\"(?<crumb>.+?)\"}"',
                          file_get_contents('https://uk.finance.yahoo.com/quote/' . $symbol),
                          $crumb);  // can contain \uXXXX chars
                        if (!isset($crumb['crumb'])) return false;
                        $crumb = json_decode('"' . $crumb['crumb'] . '"');  // \uXXXX to UTF-8
                        foreach ($http_response_header as $header) {
                          if (0 !== stripos($header, 'Set-Cookie: ')) continue;
                          $cookie = substr($header, 14, strpos($header, ';') - 14);  // after 'B='
                        }  // cookie looks like "fkjfom9cj65jo&b=3&s=sg"
                        if (!isset($cookie)) return false;
                        $fp = fopen('https://query1.finance.yahoo.com/v7/finance/download/' . $symbol
                          . '?period1=' . $tsStart . '&period2=' . $tsEnd . '&interval=1d'
                          . '&events=history&crumb=' . $crumb, 'rb', FALSE,
                        stream_context_create(array('http' => array('method' => 'GET',
                          'header' => 'Cookie: B=' . $cookie))));
                        if (FALSE === $fp) return false;
                        $buffer = '';
                        while (!feof($fp)) $buffer .= implode(',', fgetcsv($fp, 5000)) . PHP_EOL;
                        fclose($fp);
                        return $buffer;
                    }
                    
                    if ($oldData->seconds_since_data_updated > 7200) {
                        $toUpdate = array();
                        
                        // Try IEX first, then try yahoo
                        $iex = json_decode(file_get_contents('https://api.iextrading.com/1.0/stock/market/batch?types=quote&symbols=' . $oldData->internal_symbol));
                        $iexArray = (array) $iex;
                        if ((strpos($oldData->internal_symbol, '.') === false) && (count($iexArray))) {
                            $theData = $iex->{$oldData->internal_symbol}->quote;
                            
                            $this->view->results[14] = sprintf("%.2f", $theData->open);
                              $toUpdate['open'] = $this->view->results[14];
                            $this->view->results[12] = sprintf("%.2f", $theData->previousClose);
                              $toUpdate['previous_close'] = $this->view->results[12];
                            $this->view->results[3] = sprintf("%.2f", $theData->latestPrice);
                              $toUpdate['last'] = $this->view->results[3];
                            $this->view->results[11] = $theData->latestVolume;
                              $toUpdate['volume'] = $this->view->results[11];
                            $this->view->results[7] = sprintf("%.2f", $theData->week52Low);
                              $toUpdate['year_low'] = $this->view->results[7];
                            $this->view->results[8] = sprintf("%.2f", $theData->week52High);
                              $toUpdate['year_high'] = $this->view->results[8];
                              
                            $this->view->results[9] = $theData->change;
                            $this->view->results[10] = sprintf("+%.2f%%", $theData->changePercent * 100);
                            
                        }
                        else {
                            $start = mktime(0, 0, 0, date('n'), date('j'), date('Y'));
                            $result = readYahoo($oldData->internal_symbol, $start - 86400, $start);  
                            $lines = split(PHP_EOL, trim($result));
                            array_shift($lines);
                            $lines = array_reverse($lines);
                            $day1 = array_shift($lines);
                            $day2 = array_shift($lines);
                            
                            if ($day1) {
                                # Date, Open, High, Low, Close, Adj Close, Volume
                                $parts = split(',', $day1);
                                $todayData = ($parts[0] == date('Y-m-d')) ? true : false;

                                $this->view->results[14] = sprintf("%.2f", $parts[1]);
                                  $toUpdate['open'] = $this->view->results[14];
                                $this->view->results[5] = sprintf("%.2f", $parts[2]);
                                  $toUpdate['day_high'] = $this->view->results[5];
                                $this->view->results[4] = sprintf("%.2f", $parts[3]);
                                  $toUpdate['day_low'] = $this->view->results[4];
                                $this->view->results[3] = sprintf("%.2f", $parts[4]);
                                  $toUpdate['last'] = $this->view->results[3];
                                $this->view->results[11] = $parts[6];
                                  $toUpdate['volume'] = $this->view->results[11];
                                  
                                if ($day2) {
                                    $parts2 = split(',', $day2);
                                    if ($todayData && $parts2[0] != date('Y-m-d')) {
                                        // We have yesterdays data too
                                        $this->view->results[12] = sprintf("%.2f", $parts2[4]);
                                          $toUpdate['previous_close'] = $this->view->results[12];
                                        $this->view->results[9] = sprintf("%.2f", $toUpdate['last'] - $toUpdate['previous_close']);
                                        $this->view->results[10] = sprintf("%.2f%%", ($toUpdate['last'] - $toUpdate['previous_close']) / $toUpdate['previous_close'] * 100);
                                    }
                                }
                                
                            }
                        }
                        
                        // Update the database
                        if (count($toUpdate)) {
                            $this->db->update('quote_data', $toUpdate, $this->db->quoteInto('symbol_id=?', $oldData->id));
                        }
                    }
                    // Every 6 Hours, we will try to get a little fresher data and save it if we get any
                    
                    
                    
                } 
                else {
                    $this->view->results = null;
                }
            }
            
            if ((count($this->view->results) < 2) || ($this->view->results[2] == 'N/A' && $this->view->results[3] == '0.00')) {
            #if (count($this->view->results) < 2) {
                $this->view->headTitle($this->_getParam('s') . ' Data Not Found', 'PREPEND');
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => $this->_getParam('s') . 'Is Not a Valid Symbol',
                    'hd2' => 'No data found for that symbol',
                    'bd' => '<p class="error">' . $this->_getParam('s') . ' is not a valid symbol.</p><p>No data was found for that symbol.  Please check the symbol and try your request again.</p>'
                ));
                $this->getResponse()->setHttpResponseCode(404);
                return $this->_helper->viewRenderer->setNoRender();
            }
            else {
                // Load up internal VM id for symbol
                
                $this->view->verification = $quotes->verify($this->view->results[0]);
                $this->view->symbol_id = $this->view->verification[strtoupper($this->view->results[0])]->id;
                
                // Load the data we have in the database
                if ($this->view->symbol_id) {
                    $select = $this->db->select()
                        ->from(array('obj' => 'quote_symbols'))
                        ->where('obj.id = ?', $this->view->symbol_id)
                        ->where('obj.active = ?', 't')
                        ->order('obj.orderby DESC')->limit(1);
                    if (isset($this->member->profile) && $this->member->profile->id) {
                        $select->joinLeft(array('qfm' => 'quote_follow_matrix'), $this->db->quoteInto("obj.id = qfm.symbol_id AND qfm.profile_id=?", $this->member->profile->id), array('qfm_following' => 'qfm.active'));
                    }
                    
                    $this->view->symbol_data = $this->db->fetchRow($select);
                    
                    reset($this->view->verification);
                    $temp_current = current($this->view->verification);
                    $temp_tick = $temp_current->internal_symbol;
                    reset($this->view->verification);
                }
                
                // Description
                if (is_null($this->view->symbol_data->description)) {
                    // Attempt to fetch and update
                        $client = new Zend_Http_Client("http://www.google.com/finance", array('adapter' => 'Zend_Http_Client_Adapter_Curl'));
                        $client->setParameterGet('q', $temp_tick);
                        try {
                            $response = $client->request();
                        } catch (Exception $e) { }
                        if ($response->getStatus() == 200) {
                            $desc = $response->getBody();
                            if (strpos($desc, 'companySummary')) {
                                $desc = trim(preg_replace('/^.*<div class=companySummary>(.*?)<.*/s', '$1', $desc));
                            }
                            else { $desc = ''; }
                                
                            $this->view->symbol_data->description = iconv('Windows-1252', 'UTF-8//IGNORE', iconv('ISO-8859-1', 'Windows-1252//IGNORE', $desc));

                            try {
                                $this->db->update('quote_symbols', array('description' => $this->view->symbol_data->description), $this->db->quoteInto('id=?', $this->view->symbol_id));
                            } catch (Exception $e) { $this->view->symbol_data->description = null; }
                        }
                }
                
                // Meta
                $PTitle =  ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' - ' . ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]);
                // Canonical Link to SCN
                if ($this->community->name == 'smallcapnetwork') {
                    $this->view->headLink(array(
                        'rel' => 'canonical',
                        'href' => '/' . $this->view->SEO_Urlify(((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . '-' . ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : ($this->view->results[1] ? $this->view->results[1] : 'Quote'))) . '/s/quote/p/s/' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . '/' . (($this->_getParam('f') && isset($quote_functions[$this->_getParam('f')])) ? 'f/' . $this->_getParam('f') . '/' : ''),
                        'title' =>  $PTitle
                    ));
                    $this->view->headMeta()->setName('robots', 'all');
                }
                else {
                    $this->view->headLink(array(
                        'rel' => 'canonical',
                        'href' => 'https://www.smallcapnetwork.com/' . $this->view->SEO_Urlify(((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . '-' . ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : ($this->view->results[1] ? $this->view->results[1] : 'Quote'))) . '/s/quote/p/s/' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . '/' . (($this->_getParam('f') && isset($quote_functions[$this->_getParam('f')])) ? 'f/' . $this->_getParam('f') . '/' : ''),
                        'title' =>  $PTitle
                    ));
                    $this->view->headMeta()->setName('robots', 'noindex, noarchive, nofollow');
                }
                $this->view->headTitle(
                    $PTitle . ' - Quotes, News, Research & Opinions'
                    #($this->community->display ? $this->community->display : $this->community->name) .
                , 'PREPEND');
                
                $this->view->headMeta()->setName('description',
                    ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' Stock HQ. ' .
                    'In-depth research & opinions on ' . 
                    ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]) .
                    ' (' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ') from expert traders and investors who follow ' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' closely.'
                );
                
                $this->view->headMeta()->setName('keywords',
                ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ', ' . 
                ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]) . ', ' .
                ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' stock, ' . 
                $this->view->results[2] . ':' .  ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ', ' . 
                'buy ' .  ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ', ' . 
                'sell ' .  ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ', ' . 
                
                    $this->view->SEO_Keywords(
                        #((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) 
                        ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]) .
                        ((isset($this->view->symbol_data->description) && $this->view->symbol_data->description) ? ' ' . $this->view->symbol_data->description : '')
                    )
                );
                
                // Open Graph
                $rating = $this->db->fetchRow("SELECT COUNT(*) AS total, AVG(obj.position) AS rating FROM pick_picks AS obj INNER JOIN module_matrix AS x ON obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter INNER JOIN module_modules AS m ON x.module_id=m.id INNER JOIN profile_profiles AS p ON obj.profile_id = p.id INNER JOIN member_members AS b ON p.member_id = b.id INNER JOIN system_communities AS c ON p.community_id = c.id INNER JOIN quote_view_symbol_matrix AS qx ON obj.symbol_id=qx.id WHERE (x.active='t') AND (m.active='t') AND (p.active='t') AND (b.active='t') AND (c.active='t') AND ((obj.com_id='2') OR (m.allow_flow='t' AND x.allow_out_flow AND (obj.net_id = 0 OR (ARRAY[obj.via_id, x.counter] :: text ~ E'()' AND x.allow_community_outflow && ARRAY[2] :: bigint[])))) AND (obj.active='t') AND (obj.activation ISNULL OR obj.activation <= now()) AND (obj.expiration ISNULL OR obj.expiration >= now()) AND (obj.partial_close_parent_id ISNULL) AND (obj.symbol_id=?)", $this->view->symbol_data->id);
                
                if ($rating && isset($rating->total) && $rating->total) {
                    try {
                        $the_rating_value = sprintf("%.1f", (intval(($rating->rating + 1) / 2 * 9) + 1) / 2);
                        $the_rating_value = str_replace('.', '-', $the_rating_value);
                        $this->view->headMeta()->setProperty('og:image', $this->vars->static_host . "/css/theme/default/assets/stockhq/large_stars/$the_rating_value.png");
                    } catch (Exception $e) { }
                }
                #elseif (isset($this->view->symbol_data->logo_url) && $this->view->symbol_data->logo_url) {
                else {
                    try {
                        #$this->view->headMeta()->setProperty('og:image', $this->view->symbol_data->logo_url);
                        $this->view->headMeta()->setProperty('og:image', $this->vars->static_host . "/css/theme/default/assets/stockhq/large_stars/0-0.png");
                    } catch (Exception $e) { }
                }
                
                // RSS
                foreach (array('atom' => 'application/atom+xml', 'rss' => 'application/rss+xml') as $key => $val) {
                    $this->view->headLink()->appendAlternate(
                        '/' . $this->view->SEO_Urlify(((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : ($this->view->results[1] ? $this->view->results[1] : 'Quote'))) . '/s/system/widget/symbol/p/symbol/' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . "/format/$key/",
                        $val,
                        $PTitle . " - " . strtoupper($key) . ' Feed'
                    );
                }
                
                $this->_addBreadCrumb(array(
                    'title' => ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' - ' .
                        ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]),
                    'simple' => ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . ' - ' .
                        ((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : $this->view->results[1]),
                    'url' => '/' . $this->view->SEO_Urlify(((isset($this->view->symbol_data->name) && $this->view->symbol_data->name) ? $this->view->symbol_data->name : ($this->view->results[1] ? $this->view->results[1] : 'Quote'))) . '/s/quote/p/s/' . ((isset($temp_tick) && $temp_tick) ? $temp_tick : $this->view->results[0]) . '/'
                ));
                
                $dummy_object = new StdClass;
                $dummy_object->community_mask = null;
                $dummy_object->network_mask = null;
                $dummy_object->profile_mask = null;
                $dummy_object->mask_counter = null;
                $dummy_object->counter = '*';
                $dummy_object->allow_in_flow = 't';
                $dummy_object->allow_community_inflow = null;
                $dummy_object->allow_network_inflow = null;
                $dummy_object->allow_profile_inflow = '{}';
                
                if (!(isset($this->target->type) || $this->target->type)) { $this->target->type = 'COM'; }
                
                // If posted comment, add it
                if (isset($this->member->profile->id) && $this->getRequest()->isPost() && $this->_getParam('comment')) {
                    $HPF = new ViaMe_Filter_HTMLPurify();
                    
                    $stmt = $this->db->query('INSERT INTO quote_comments(symbol_id, profile_id, ip_address, content) VALUES (?, ?, ?, ?)', array($this->view->symbol_id, $this->member->profile->id, $_SERVER['REMOTE_ADDR'], strip_tags($HPF->filter($this->_getParam('comment')))));
                }
                
                
                // Articles
                $select = $this->_buildComplexQuery('article_articles', $dummy_object);
                #$select->where('obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote($this->view->results[1]) . ') OR (obj.meta_keywords ILIKE ' . "'%' || " . $this->db->quote($this->view->results[0]) . " || '%')");
                $select->where(($this->view->symbol_id ? $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $this->view->symbol_id) : '') . 'obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $this->view->results[1]))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($this->view->results[0]) . " || E'((,|\\\\s)+|$)'))");
                $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                $this->view->articles = $this->db->fetchAll($select);
                
                // Picks - Closed Picks
                $select = $this->_buildComplexQuery('pick_picks', $dummy_object);
                $select->join(array('qx' => 'quote_view_symbol_matrix'), 'obj.symbol_id=qx.id',
                    array(
                        'qx_symbol' => 'symbol',
                        'qx_name' => 'name',
                        'qx_type' => 'type',
                        'qx_exch' => 'exch',
                        'qx_internal_symbol' => 'internal_symbol',
                        'qx_delayed_symbol' => 'delayed_symbol',
                        'qx_realtime_symbol' => 'realtime_symbol',
                        'qx_seconds_since_data_updated' => 'seconds_since_data_updated'
                    )
                );
                $this->view->ratings_select_query_base = clone $select;
                $select->where('obj.symbol_id=?', $this->view->symbol_id);
                $select->where('obj.partial_close_parent_id ISNULL');
                
                //$select->where('obj.close_datestamp ISNULL');
                $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                
                $this->view->quote_data = array();
                $this->view->picks = $this->db->fetchAll($select);
                /* MANUALLY CREATE WITH EXISTING DATA
                if ($this->view->picks = $this->db->fetchAll($select)) {
                    $temp = $quotes->fetch($this->view->picks[0]->qx_delayed_symbol, 'sl1ba');
                    $this->view->quote_data[$this->view->picks[0]->qx_delayed_symbol] = $temp[0];
                }
                */
                    
                // Only get content for subpages that display it
                //  * SET TO TRUE
                if ($this->_getParam('f') || true) {
                    // Blogs
                    $select = $this->_buildComplexQuery('blog_blogs', $dummy_object);
                    #$select->where('obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote($this->view->results[1]) . ') OR (obj.meta_keywords ILIKE ' . "'%' || " . $this->db->quote($this->view->results[0]) . " || '%')");
                    $select->where(($this->view->symbol_id ? $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $this->view->symbol_id) : '') . 'obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $this->view->results[1]))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($this->view->results[0]) . " || E'((,|\\\\s)+|$)'))");
                    $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                    $this->view->blogs = $this->db->fetchAll($select);
                    
                    // Message Boards
                    $select = $this->_buildComplexQuery('message_messages', $dummy_object);
                    #$select->where('obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote($this->view->results[1]) . ') OR (obj.meta_keywords ILIKE ' . "'%' || " . $this->db->quote($this->view->results[0]) . " || '%')");
                    $select->where(($this->view->symbol_id ? $this->db->quoteInto('obj.symbols && ARRAY[?::bigint] OR ', $this->view->symbol_id) : '') . 'obj.search @@ plainto_tsquery(' . $this->db->quote($this->view->results[0]) . " || ' ' || " . $this->db->quote(trim(preg_replace(array('/[^\p{L}\p{N}]/u', '/(inc|corp).*/i'), array(' ', ' '), $this->view->results[1]))) . ") OR (obj.meta_keywords ~* (E'(^|(,|\\\\s)+)' || " . $this->db->quote($this->view->results[0]) . " || E'((,|\\\\s)+|$)'))");
                    $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                    
                    $this->view->messages = $this->db->fetchAll($select);
                    
                    if ($this->view->symbol_id) {
                        // Comments
                        $select = $this->db->select()
                            ->from(array('obj' => 'quote_comments'))
                            ->where('obj.symbol_id = ?', $this->view->symbol_id)
                            ->where('obj.active = ?', 't')
    
                    
                            ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                                array(
                                    'name' => 'name',
                                    'p_id' => 'id',
                                    'p_name' => 'name',
                                    'p_site_admin' => 'site_admin',
                                    'p_active' => 'active'
                                )
                            )
                            #->where('p.active NOTNULL')
                    
                            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                                array(
                                'b_id' => 'id',
                                'b_site_admin' => 'site_admin',
                                'b_active' => 'active',
                                'b_email' => 'email'
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
                            ->where('c.active=?', 't')
    
                            ->order('obj.creation DESC')->limit(10);
                        if (isset($this->member)) {
                            $select->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("p.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->member->profile->id),
                                array(
                                    'vc_status' => 'status',
                                    'vc_display' => 'display'
                                )
                            );
                        }
                        $this->view->comments = $this->db->fetchAll($select);
                        
                        // Analysis
                        $select = $this->_buildComplexQuery('analysis_analysiss', $dummy_object);
                        $select->where('obj.symbols && ARRAY[?::bigint]', $this->view->symbol_id);
                        $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                        $this->view->analysis = $this->db->fetchAll($select);
                        
                        // Files
                        $select = $this->_buildComplexQuery('file_files', $dummy_object);
                        $select->where("obj.file_type NOT ILIKE 'image%'");
                        $select->where('obj.symbols && ARRAY[?::bigint]', $this->view->symbol_id);
                        $select->order('COALESCE(obj.published, obj.creation) DESC')->limit(10);
                        $this->view->files = $this->db->fetchAll($select);
                    }
                }
            }
            
            if ($this->_getParam('f') && isset($quote_functions[$this->_getParam('f')])) {
                $this->render($this->_getParam('f'));
            }
            else {
                $this->render('display');
            }
        }
    }
}
