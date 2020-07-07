<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_CronController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function preDispatch()
    {
    	ignore_user_abort(true);
        set_time_limit(600);
            
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) { return $this->_denied(); }
        else {
            $this->view->headTitle('Admin', 'PREPEND');
            $this->view->headTitle('Cron', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    public function newauthorsAction()
    {
        $this->view->headTitle('New Authors', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        $default_begindate = date('m/d/Y H:i:s', time() - 86400);
        $default_sendemail = true;
        $default_email = 'akang@levelogic.com, jmangubat@levelogic.com';
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'new_authors_form',
                'method' => 'post',
                'id' => 'new_authors_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.new_authors_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
            	'sendemail' => array('Checkbox', array(
                    'label' => 'Email Report',
                    'description' => 'Send an email of the report',
                    'value' => $default_sendemail,
                    'order' => 5
                )),
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Email address(es) where the report should be sent (comma delimited)',
                    'value' => $default_email,
                    'order' => 10
                )),
                'begin_datetime' => array('Text', array(
		            'label' => 'Starting Date/Time',
		            'description' => 'Start searching from date/time (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
		            'class' => 'vmfh_date vmfh_datetime',
		            'autocomplete' => 'off',
		            'order' => 15,
		            'value' => $default_begindate,
		            'validators' => array(
		                array('Regex', false, array(
		                    'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' \d+:\d+:\d+$/',
		                    'messages' => array(
		                        Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
		                    )
		                )),
		                array('Date', false, array(
		                    'format' => preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' HH:mm:ss',
		                    'messages' => array(
		                        Zend_Validate_Date::INVALID_DATE => "'%value%' does not appear to be a valid date/time"
		                    )
		                ))
		            )
		        ))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Report', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('new_authors_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $select = $this->db->select()
	            ->from(array('obj' => 'article_articles'))
	            ->where('obj.active=?', 't')
	            ->where('obj.creation >= ?', $this->_getParam('begin_datetime', $default_begindate))
	            
	            ->join(array('x' => 'module_matrix'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.counter', array('x_interactive' => 'interactive'))
                ->where('x.active=?', 't')
                
                ->join(array('m' => 'module_modules'), 'x.module_id=m.id', array())
                ->where('m.active=?', 't')
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active'
                    )
                )
                ->where('p.active=?', 't')
                #->where('p.community_id=?', 2) # See Below with the newly added left joins
                ->where('p.id <> ?', 18950) // eTeligis
                ->where('p.id <> ?', 1005)  // Larry Isen
                ->where('p.id <> ?', 9724)  // BioMedReports
                ->where('p.id <> ?', 10011) // David Ibrahim
                ->where('p.id <> ?', 16027) // Ray Dirks Research
                ->where('p.id <> ?', 21121) // E. Michael Greenberg
                ->where('p.id <> ?', 16571) // Bio-Wire
                ->where('p.id <> ?', 8996)  // OnTheMarket
                ->where('p.id <> ?', 1867)  // Chris Vermeulen
                ->where('p.id <> ?', 15181) // InvestorStockAlerts
                ->where('p.id <> ?', 21786) // ProfitConfidential
                ->where('p.id <> ?', 11043) // Takeover Analyst
                ->where('p.id <> ?', 22389) // IdaH (Jonathan)
                ->where('p.id <> ?', 20786) // toppennystocks
                ->where('p.id <> ?', 20704) // Penny Pick Alerts
                ->where('p.id <> ?', 22723) // John Suh
                ->where('p.id <> ?', 9839)  // Stock House Group
                ->where('p.id <> ?', 23108) // investmentcontrarians
                ->where('p.id <> ?', 23129) // pennymagic
                ->where('p.id <> ?', 18293) // Tedra DeSue
                ->where('p.id <> ?', 23543) // RedChip
                ->where('p.id <> ?', 22570) // rjamestaylor
                ->where('p.id <> ?', 24173) // pineanalytics
                ->where('p.id <> ?', 24047) // Jade Morgan
                ->where('p.id <> ?', 24995) // EquityOptionsGuru
                ->where('p.id <> ?', 25203) // Alexander Daley
                ->where('p.id <> ?', 25773) // SmallCapGuru
                ->where('p.id <> ?', 23425) // The Individual Investor
                ->where('p.id <> ?', 22795) // Stocker
                ->where('p.id <> ?', 25650) // Tim Lambert
                ->where('p.id <> ?', 24671) // IdaHansen
                ->where('p.id <> ?', 24406) // Glenwoods
                ->where('p.id <> ?', 25686) // AlphaVN Securities
                ->where('p.id <> ?', 24790) // Samuel J Rae
                ->where('p.id <> ?', 26106) // riddock57
                ->where('p.id <> ?', 26919) // stockinvestor
                ->where('p.id <> ?', 26859) // James Morgan
                ->where('p.id <> ?', 28002) // itradebiostocks
                ->where('p.id <> ?', 28228) // Johan Albrecht
                ->where('p.id <> ?', 28266) // itradethebios
                ->where('p.id <> ?', 28311) // Stock Whisper
                ->where('p.id <> ?', 28440) // Richard Hudson
                ->where('p.id <> ?', 26624) // Amy Baldwin
                ->where('p.id <> ?', 28014) // Elias Hertz
                ->where('p.id <> ?', 30798) // Adamlee
                ->where('p.id <> ?', 32790) // Kimberly Hernandez
                ->where('p.id <> ?', 15410) // SmallCapVoice
                ->where('p.id <> ?', 28141) // SmallCapPower.com
                ->where('p.id <> ?', 26149) // Dutch Trader
                ->where('p.id <> ?', 34383) // Adrian D. Levi - Shas
                ->where('p.id <> ?', 34672) // stocktalk
                ->where('p.id <> ?', 27124) // Richard Cox
                ->where('p.id <> ?', 38134) // Thomas Keane
                ->where('p.id <> ?', 24698) // Ciaran Thornton
                ->where('p.id <> ?', 42659) // Sachi Mohanty
                ->where('p.id <> ?', 49100) // lilyportugueze
                ->where('p.id <> ?', 52444) // Todd Peavy
                ->where('p.id <> ?', 55305) // Michael McCarthy
                ->where('p.id <> ?', 55359) // Darren Williams
                ->where('p.id <> ?', 55383) // Sara Cornell
                ->where('p.id <> ?', 55390) // Phil Saunders
                ->where('p.id <> ?', 55398) // Robert Smith
                ->where('p.id <> ?', 55421) // Jared Kimball
                ->where('p.id <> ?', 55460) // Azam Zariff
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email'
                    )
                )
                ->where('b.active=?', 't')
                ->where('b.id <> ?', 2) // Arthur
                ->where('b.id <> ?', 777) // SCN
                ->where('b.id <> ?', 49068) // Ninja Plays
                ->where('b.id <> ?', 7) // James Brumley
                ->where('b.id <> ?', 1943) // Bryan Murphy
                ->where('b.id <> ?', 3762) // John Udovich
                ->where('b.id <> ?', 3776) // Jonathan Yates
                ->where('b.id <> ?', 18728) // Jonathan Yates
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                ->where('c.active=?', 't')
                
                // Only for this community
                ->joinLeft(array('xns' => 'network_networks'), 'obj.net_id = xns.id', array())
                ->joinLeft(array('xps' => 'profile_profiles'), 'obj.via_id = xps.id', array())
                ->where('obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)', $this->community->id)
	        ;
            
            $output = '';
            
	        if ($objects = $this->db->fetchAll($select)) {
	            $this->view->module = 'article';
	            ob_start();
				echo '<h1>New Articles</h1><ul>';
				foreach ($objects as $object) {
					$link = $this->view->ContentLink(array('object' => $object, 'view' => $this->view));
					echo '<li><a href="'.$link.'">'.$object->title.'</a>';
					echo '<br />By ' . $this->view->ProfileDisplay(array('profile' => $object, 'internal' => $this->internal));
					echo "<br /><br /></li>\n";
				}
				echo '</ul>';
				$output = ob_get_contents();
				ob_end_clean();
				$this->view->module = 'admin';
			}
			
			if ($output) {
				echo $output;
				echo '<p><a href="/admin/">Continue...</a></p>';
				
				if ($this->_getParam('sendemail', $default_sendemail)) {
		            $this->_helper->ViaMe->sendEmail(preg_split("/[\s,]+/", $this->_getParam('email', $default_email)), null, 'New Articles By Unapproved Authors Report', $output, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
		        }
    		}
    		else {
    			echo '<p>Nothing to report... <a href="/admin/">Continue...</a></p>';
    		}        
        }
        else {
            $form->populate($this->_getAllParams());
            echo $form;
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function expiredmembersAction()
    {
        $this->view->headTitle('Expired Members', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        $default_process = true;
        $default_sendemail = true;
        $default_email = 'akang@levelogic.com';
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'expired_members_form',
                'method' => 'post',
                'id' => 'expired_members_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.expired_members_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
            	'process' => array('Checkbox', array(
                    'label' => 'Process Expired Members',
                    'description' => 'Process expired members removing them from lists and deleting access',
                    'value' => $default_process,
                    'order' => 1
                )),
            	'sendemail' => array('Checkbox', array(
                    'label' => 'Email Report',
                    'description' => 'Send an email of the report',
                    'value' => $default_sendemail,
                    'order' => 5
                )),
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Email address(es) where the report should be sent (comma delimited)',
                    'value' => $default_email,
                    'order' => 10
                ))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Process Expired Members', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('expired_members_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $select = $this->db->select()
	            ->from(array('obj' => 'acl_members'))
	            ->where('obj.active ISNULL')
	            ->where("DATE(obj.expiration) < (DATE('now') - '1 Week'::Interval)")
	            
	            ->join(array('x' => 'acl_acls'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.matrix_counter AND obj.item_counter=x.item_counter AND obj.acl_counter=x.counter', array('x_title' => 'title', 'x_greenarrow_list_id' => 'greenarrow_list_id'))
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active'
                    )
                )
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email'
                    )
                )
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                
                
                // Only for this community
                ->joinLeft(array('xns' => 'network_networks'), 'obj.net_id = xns.id', array())
                ->joinLeft(array('xps' => 'profile_profiles'), 'obj.via_id = xps.id', array())
                ->where('obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)', $this->community->id)
                
                
                ->order(array('x.title ASC', 'obj.expiration DESC', 'obj.profile_id ASC'))
	        ;
            #echo $select;
            
            $output = $item = '';
            
            ob_start();
	        if ($objects = $this->db->fetchAll($select)) {
	            #$this->view->module = 'article';
	            echo '<h1>Canceled Members</h1><ul>';
	            
	            if ($this->_getParam('process', $default_process)) {
                    $manage = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST'), $this->community->name); // LIVE Is Production Environment Only
                }
                
				foreach ($objects as $object) {
					if ($item != $object->x_title) {
		            	$item = $object->x_title;
		            	echo "</ul><h2>$item</h2><ul>";
		            }
		            
					echo '<li>';
					echo $this->view->ProfileDisplay(array('profile' => $object, 'internal' => $this->internal)) . ' - (' . $object->p_id . ' - <a href="mailto:'.$object->b_email.'">' . $object->b_email . '</a>) : ';
					$dt = new DateTime($object->expiration);
					echo $dt->format('m/d/Y');
					
					if ($this->_getParam('process', $default_process)) {
					    $trxn_data = array(
                            'subscriptionId' => $object->identifier
                        );
                        #Zend_Debug::Dump($trxn_data);
                        $trxn_result = $manage->transact('ARBCancelSubscriptionRequest', $trxn_data);
                        #Zend_Debug::Dump($trxn_result);
                        if ($trxn_result['status']) {
                            echo ' - Removed From ARB';
                        }
                        echo ' - Processed';
					}
					
					echo "</li>\n";
				}
				echo '</ul>';
				
				if ($this->_getParam('process', $default_process)) {
				    // Remove all active nulls and expired more than a week ago
    				#$this->db->getConnection()->exec("DELETE FROM acl_members WHERE active ISNULL AND (DATE(expiration) < (DATE('now') - '1 Week'::Interval))");
    				
    				# New Statement - Try to Isolate This Community
					$this->db->getConnection()->exec($this->db->quoteInto("DELETE FROM acl_members WHERE active ISNULL AND (DATE(expiration) < (DATE('now') - '1 Week'::Interval)) AND (com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter, profile_id) IN (SELECT \"obj\".com_id, \"obj\".net_id, \"obj\".via_id, \"obj\".module_id, \"obj\".matrix_counter, \"obj\".item_counter, \"obj\".acl_counter, \"obj\".profile_id FROM \"acl_members\" AS \"obj\" INNER JOIN \"acl_acls\" AS \"x\" ON obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.matrix_counter AND obj.item_counter=x.item_counter AND obj.acl_counter=x.counter INNER JOIN \"profile_profiles\" AS \"p\" ON obj.profile_id = p.id INNER JOIN \"member_members\" AS \"b\" ON p.member_id = b.id INNER JOIN \"system_communities\" AS \"c\" ON p.community_id = c.id LEFT JOIN \"network_networks\" AS \"xns\" ON obj.net_id = xns.id LEFT JOIN \"profile_profiles\" AS \"xps\" ON obj.via_id = xps.id WHERE (obj.active ISNULL) AND (DATE(obj.expiration) < (DATE('now') - '1 Week'::Interval)) AND (obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)))", $this->community->id));
					
				    echo '<p>Deleted members with null active and expired more than a week ago.</p>';
				}
			}
			
			
            $select = $this->db->select()
	            ->from(array('obj' => 'acl_members'))
	            ->where('obj.active NOTNULL')
	            ->where("DATE(obj.expiration) < DATE('now')")
	            
	            ->join(array('x' => 'acl_acls'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.matrix_counter AND obj.item_counter=x.item_counter AND obj.acl_counter=x.counter', array('x_title' => 'title', 'x_greenarrow_list_id' => 'greenarrow_list_id'))
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active'
                    )
                )
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email'
                    )
                )
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                
                
                // Only for this community
                ->joinLeft(array('xns' => 'network_networks'), 'obj.net_id = xns.id', array())
                ->joinLeft(array('xps' => 'profile_profiles'), 'obj.via_id = xps.id', array())
                ->where('obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)', $this->community->id)
                
                
                ->order(array('x.title ASC', 'obj.expiration DESC', 'obj.profile_id ASC'))
	        ;
            #echo $select;
            
            $item = '';
            
	        if ($objects = $this->db->fetchAll($select)) {
	            #$this->view->module = 'article';
	            echo '<h1>Expired Members</h1><ul>';
	            
	            if ($this->_getParam('process', $default_process)) {
                    require_once $this->vars->APP_PATH . "/library/Other/GreenArrowStudioAPI.php";
                    $GA_API = new GreenArrowStudioAPI();
                }
                
				foreach ($objects as $object) {
					if ($item != $object->x_title) {
		            	$item = $object->x_title;
		            	echo "</ul><h2>$item</h2><ul>";
		            }
		            
					echo '<li>';
					echo $this->view->ProfileDisplay(array('profile' => $object, 'internal' => $this->internal)) . ' - (' . $object->p_id . ' - <a href="mailto:'.$object->b_email.'">' . $object->b_email . '</a>) : ';
					$dt = new DateTime($object->expiration);
					echo $dt->format('m/d/Y');
					
					if ($this->_getParam('process', $default_process)) {
						if ($object->x_greenarrow_list_id) {
							// Remove them from list
							try {
                                $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                            		'email'         => $object->b_email,
                            		'listid'        => $object->x_greenarrow_list_id,
                            		'requestip'     => $_SERVER['REMOTE_ADDR']
                            	));
                            	echo ' - Unsubscribed From List';
                            } catch (Exception $e) { echo ' - NOT Unsubscribed From List'; }
                                
                            // Add SCNEO Unsubscribed Members Back into SCN Regular (5)
                            // Do it for all subscribers - Sync with AccessController
                            if (true || $object->x_greenarrow_list_id == 28) {
    							try {
                                	$result_add = $GA_API->call_method('subscriberAdd', Array(
                                		'email'         => $object->b_email,
                                		'listid'        => 5,
                                		'reactivate'    => 1,
                                		'requestip'     => $_SERVER['REMOTE_ADDR']
                                	));
                                	echo ' - Resubscribed To SCN Regular';
                                } catch (Exception $e) { echo ' - NOT Resubscribed To SCN Regular'; }
                            }
						}
						echo ' - Processed';
					}
					
					echo "</li>\n";
				}
				echo '</ul>';
				
				if ($this->_getParam('process', $default_process)) {
					// Update all expired to active null
					#$this->db->getConnection()->exec("UPDATE acl_members SET active=null WHERE active NOTNULL AND (DATE(expiration) < DATE('now'))");
					
					# New Statement - Try to Isolate This Community
					$this->db->getConnection()->exec($this->db->quoteInto("UPDATE acl_members SET active=null WHERE active NOTNULL AND (DATE(expiration) < DATE('now')) AND (com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter, profile_id) IN (SELECT \"obj\".com_id, \"obj\".net_id, \"obj\".via_id, \"obj\".module_id, \"obj\".matrix_counter, \"obj\".item_counter, \"obj\".acl_counter, \"obj\".profile_id FROM \"acl_members\" AS \"obj\" INNER JOIN \"acl_acls\" AS \"x\" ON obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.matrix_counter AND obj.item_counter=x.item_counter AND obj.acl_counter=x.counter INNER JOIN \"profile_profiles\" AS \"p\" ON obj.profile_id = p.id INNER JOIN \"member_members\" AS \"b\" ON p.member_id = b.id INNER JOIN \"system_communities\" AS \"c\" ON p.community_id = c.id LEFT JOIN \"network_networks\" AS \"xns\" ON obj.net_id = xns.id LEFT JOIN \"profile_profiles\" AS \"xps\" ON obj.via_id = xps.id WHERE (obj.active NOTNULL) AND (DATE(obj.expiration) < DATE('now')) AND (obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)))", $this->community->id));
					
					echo '<p>Set expired members to null active.</p>';
		        }
				
				#$this->view->module = 'admin';
			}
			
			
			$output = ob_get_contents();
			ob_end_clean();
			
			if ($output) {
				echo $output;
				echo '<p><a href="/admin/">Continue...</a></p>';
				
				if ($this->_getParam('sendemail', $default_sendemail)) {
		            $this->_helper->ViaMe->sendEmail(preg_split("/[\s,]+/", $this->_getParam('email', $default_email)), null, 'Processing Expired Members', $output, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
		        }
    		}
    		else {
    			echo '<p>Nothing to report... <a href="/admin/">Continue...</a></p>';
    		}        
        }
        else {
            $form->populate($this->_getAllParams());
            echo $form;
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function ccexpireAction()
    {
        $this->view->headTitle('Expiring Credit Cards', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        $default_process = true;
        $default_sendemail = true;
        $default_email = 'akang@levelogic.com';
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'cc_expire_form',
                'method' => 'post',
                'id' => 'cc_expire_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.cc_expire_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
            	'process' => array('Checkbox', array(
                    'label' => 'Process Members',
                    'description' => 'Send an email to members with renewals coming up and credit cards expiring',
                    'value' => $default_process,
                    'order' => 1
                )),
            	'sendemail' => array('Checkbox', array(
                    'label' => 'Email Report',
                    'description' => 'Send an email of the report',
                    'value' => $default_sendemail,
                    'order' => 5
                )),
                'email' => array('Text', array(
                    'label' => 'Email Address',
                    'description' => 'Email address(es) where the report should be sent (comma delimited)',
                    'value' => $default_email,
                    'order' => 10
                ))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Process', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('cc_expire_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $select = $this->db->select()
	            ->from(array('obj' => 'acl_members'))
	            ->where('obj.active=?', 't')
	            ->where('obj.cc_exp_month NOTNULL')
                ->where('obj.cc_exp_year NOTNULL')
                ->where("obj.expiration < (date_trunc('month', now()) + '2 months'::interval)")
                ->where("date(obj.cc_exp_year || '-' || obj.cc_exp_month || '-01') < (date_trunc('month', now()) + '2 months'::interval)")
	            #->where('obj.profile_id=?', 2)
	            
	            ->join(array('x' => 'acl_acls'), 'obj.com_id=x.com_id AND obj.net_id=x.net_id AND obj.via_id=x.via_id AND obj.module_id=x.module_id AND obj.matrix_counter=x.matrix_counter AND obj.item_counter=x.item_counter AND obj.acl_counter=x.counter', array('x_title' => 'title', 'x_greenarrow_list_id' => 'greenarrow_list_id'))
                
                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                    array(
                        'name' => 'name',
                        'p_id' => 'id',
                        'p_name' => 'name',
                        'p_site_admin' => 'site_admin',
                        'p_active' => 'active'
                    )
                )
                
                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                    array(
                    'b_id' => 'id',
                    'b_site_admin' => 'site_admin',
                    'b_active' => 'active',
                    'b_email' => 'email',
                    'b_first_name' => 'first_name',
                    'b_middle_name' => 'middle_name',
                    'b_last_name' => 'last_name'
                    )
                )
                
                ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                
                
                // Only for this community
                ->joinLeft(array('xns' => 'network_networks'), 'obj.net_id = xns.id', array())
                ->joinLeft(array('xps' => 'profile_profiles'), 'obj.via_id = xps.id', array())
                ->where('obj.com_id=? OR (obj.net_id > 0 AND xns.community_id=?) OR (obj.via_id > 0 AND xps.community_id=?)', $this->community->id)
                
                
                ->order(array('x.title ASC', 'obj.expiration DESC', 'obj.profile_id ASC'))
	        ;
            #echo $select;
            
            $output = $item = '';
            
            ob_start();
	        if ($objects = $this->db->fetchAll($select)) {
	            echo '<h1>Expiring Credit Cards</h1><ul>';
                
                foreach ($objects as $object) {
					if ($item != $object->x_title) {
		            	$item = $object->x_title;
		            	echo "</ul><h2>$item</h2><ul>";
		            }
		            
					echo '<li>';
					echo $this->view->ProfileDisplay(array('profile' => $object, 'internal' => $this->internal)) . ' - (' . $object->p_id . ' - <a href="mailto:'.$object->b_email.'">' . $object->b_email . '</a>) : ';
					$dt = new DateTime($object->expiration);
					echo $dt->format('m/d/Y');
					echo ' : ' . $object->cc_exp_month . '-' . $object->cc_exp_year;
					
					if ($this->_getParam('process', $default_process)) {
					    $to_name = $object->p_name;
                        if ($object->b_first_name) {
                            $to_name = $object->b_first_name;
                            if ($object->b_middle_name) { $to_name .= ' ' . $object->b_middle_name; }
                            if ($object->b_last_name) { $to_name .= ' ' . $object->b_last_name; }
                        }
                        
                        $temp = $this->view->partial('cron/email/expired_cc.phtml', array('object' => $object, 'internal' => $this->internal));
                        
					    // Send an email
					    $this->_helper->ViaMe->sendEmail($object->b_email, $to_name, $object->x_title . ' - Credit Card Expiring Soon', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
					    
					    echo ' - Emailed Reminder to Update Credit Card';
                        echo ' - Processed';
					}
					
					echo "</li>\n";
				}
				echo '</ul>';
			}
			
			
			$output = ob_get_contents();
			ob_end_clean();
			
			if ($output) {
				echo $output;
				echo '<p><a href="/admin/">Continue...</a></p>';
				
				if ($this->_getParam('sendemail', $default_sendemail)) {
		            $this->_helper->ViaMe->sendEmail(preg_split("/[\s,]+/", $this->_getParam('email', $default_email)), null, 'Expiring Credit Cards', $output, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
		        }
    		}
    		else {
    			echo '<p>Nothing to report... <a href="/admin/">Continue...</a></p>';
    		}        
        }
        else {
            $form->populate($this->_getAllParams());
            echo $form;
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}