<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Contact_WidgetController extends ViaMe_Controller_Action
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
        
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        #$contextSwitch->removeContext('json');
        $contextSwitch->removeContext('xml');
        
        /*
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
        */
        
        $contextSwitch
            ->addActionContext('index', array('json'))
            ->setAutoJsonSerialization(false)
            ->initContext();
            
        #$this->_helper->layout->disableLayout();
    }
    
    
    #public function preDispatch() { }
    
    
    public function indexAction($select = null)
    {
        /*
            5 Modes
            
            1 - Default : All Profiles (Default)
            2 - All Profiles and Personal Groups (AddGroups) : /add/groups/
            3 - Contacts Only (ContactsOnly) : /contacts/only/
            4 - Contacts and Groups (ContactsOnly AddGroups) : /contacts/only/
            5 - Groups Only (NoProfiles AddGroups) : /no/profiles/add/groups/
            
            NoContacts : /no/contacts/ - No contacts in profiles
        */
        
        $data = array();
        $selects = array();
        
        $contextSwitch = $this->_helper->getHelper('contextSwitch');
        
        if ($this->_getParam('query')) {
            
            // Sync This Select With the Create Lookup
            $select = $this->VMAH->getVSelectFromList(null, false);
            
            /*
            $select = $this->db->select()
                ->from(array('obj' => 'profile_profiles'), array('p_id' => 'id', 'p_name' => 'name', 'type' => new Zend_Db_Expr("'V'")))
                ->where('obj.active = ?',  't')
                
                ->join(array('b' => 'member_members'), 'obj.member_id = b.id', array())
                ->where('b.active = ?', 't')
                
                ->join(array('c' => 'system_communities'), 'obj.community_id = c.id',
                    array(
                    'c_id' => 'id',
                    'c_name' => 'name',
                    'c_hostname' => 'hostname'
                    )
                )
                ->where('c.active=?', 't')
                
                ->joinLeft(array('vc' => 'contact_contacts'), $this->db->quoteInto("obj.id = vc.contact_profile_id AND vc.profile_id=? AND vc.status='t' AND vc.active='t'", $this->target->id),
                    array(
                        'vc_creation' => 'creation',
                        'vc_display' => 'display',
                        'vc_description' => 'description',
                        'vc_message' => 'message',
                        'vc_auto_reciprocate' => 'auto_reciprocate',
                        'vc_status' => 'status',
                        'vc_active' => 'active'
                    )
                );
            */
            
            // Contacts Only
            if ($this->_getParam('contacts') == 'only') {
                $select->where('vc.active=?', 't');
            } elseif ($this->_getParam('no') == 'contacts') {
                $select->where('vc.status ISNULL')->where('vc.active ISNULL');
            }
            
            $select1 = clone $select;
              $select1->where('obj.name ~* ? OR vc.display ~* ?', '^' . $this->_getParam('query'));
                #->joinLeft(array('emtpy' => new Zend_Db_Expr('(SELECT 1 AS zzuzzorder)')), new Zend_Db_Expr('true'));
            $select2 = clone $select;
              $select2->where('obj.name ~* ? OR vc.display ~* ?', $this->_getParam('query'));
                #->joinLeft(array('empty' => new Zend_Db_Expr('(SELECT 2 AS zzuzzorder)')), new Zend_Db_Expr('true'));
            
            if (!($this->_getParam('no') == 'profiles')) {
                $selects[] = $select1;
                $selects[] = $select2;
            }
            
            if ($this->_getParam('add') == 'groups') {
                /*
                    Group Selects - Will Use the vc spots for group information
                    
                    p_id				    counter
                    p_name				    name
                    type				    "G"
                    c_id
                    c_name                  parent_id
                    c_hostname              total_member_count
                    vc_creation			    creation
                    vc_display			    name
                    vc_description			description
                    vc_message			    
                    vc_auto_reciprocate		
                    vc_status
                    vc_active			    active
                */
                $nullc = new Zend_Db_Expr('null');
                $gselect = $this->db->select()
                    ->from(array('obj' => 'contact_group_groups'), array(
                        'p_id' => 'counter',
                        'p_name' => 'name',
                        'p_site_admin' => $nullc,
                        'p_active' => $nullc,
                        'type' => new Zend_Db_Expr("'G'"),
                        'b_site_admin' => $nullc,
                        'b_email' => $nullc,
                        'b_active' => $nullc,
                        'c_id' => $nullc,
                        'c_name' => new Zend_Db_Expr('parent_id::text'),
                        'c_hostname' => new Zend_Db_Expr('total_member_count::text'),
                        'vc_creation' => 'creation',
                        'vc_display' => 'name',
                        'vc_description' => 'description',
                        'vc_message' => $nullc,
                        'vc_auto_reciprocate' => $nullc,
                        'vc_status' => $nullc,
                        'vc_active' => 'active'
                      ))
                    ->where('obj.active = ?',  't')
                    ->where('obj.profile_id = ?',  $this->target->id);
                  
                $gselect1 = clone $gselect;
                  $gselect1->where('obj.name ~* ?', '^' . $this->_getParam('query'));
                    #->joinLeft(array('emtpy' => new Zend_Db_Expr('(SELECT 1 AS zzuzzorder)')), new Zend_Db_Expr('true'));
                $gselect2 = clone $gselect;
                  $gselect2->where('obj.name ~* ?', $this->_getParam('query'));
                    #->joinLeft(array('empty' => new Zend_Db_Expr('(SELECT 2 AS zzuzzorder)')), new Zend_Db_Expr('true'));
                
                $selects[] = $gselect1;
                $selects[] = $gselect2;
            }
              
            
            $select = $this->db->select()
              ->union($selects)
              ->limit($this->_getParam('w_limit', 10))
              ->order(array('p_name ASC', 'p_id ASC'));
            
            $data['Query'] = $this->_getParam('query');
            if ($results = $this->db->fetchAll($select, null, Zend_Db::FETCH_ASSOC)) {
                $data['ResultSet']['Result'] = $results;
            }
            else {
                $data['Error'] = 'No results';
                $data['ResultSet']['Result'] = array();
            }
            
            /*
            $data = array(
                'error' => 'error message',
                'blah' => 'blah message',
                'ResultSet' => array(
                    'Query' => $this->_getParam('query'),
                    'Result' => array(
                        array('first' => 'arthur', 'middle' => 'marshall', 'last' => 'kang', 'age' => 39),
                        array('first' => 'lori', 'middle' => 'hyeyoung', 'last' => 'kang', 'age' => 36),
                        array('first' => 'russell', 'middle' => 'cody', 'last' => 'kang', 'age' => 7),
                        array('first' => 'chloe', 'middle' => 'faith', 'last' => 'kang', 'age' => 3)
                    )
                )
            );
            */
            
            #Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')->setNoRender(true);
            #Zend_Layout::getMvcInstance()->disableLayout();
            
            #$response = Zend_Controller_Front::getInstance()->getResponse();
            #$response->setHeader('Content-Type', 'application/json');
            
            echo Zend_Json::encode($data);
            
            $this->_helper->layout->disableLayout();
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}
