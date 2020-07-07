<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_CreateController extends ViaMe_Controller_Default_Create
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'pick_picks';
    
    
    public function indexAction()
    {
        $this->view->headTitle('Create Pick', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/picks_form.php';
        $form = new Zend_Form();
        $form->setOptions($form_config);
        
        $form->addDisplayGroup(array('heading', 'summary', 'more_link'), 'options_content', array('legend' => 'Content'));
        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        #$form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        
        $form->addElement('Submit', 'submit', array('label' => 'Create Pick', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('pick_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');
        
        // Hidden Elements
        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_nar', 'redirect'), 'hidden');
        
        
        // Load allocation
        $max_alloc = 100 - $this->db->fetchOne('SELECT SUM(allocation) FROM pick_picks WHERE close_datestamp ISNULL AND ' . strtolower($this->target->type) . '_id = ' . $this->target->id . ' AND matrix_counter = ' . $this->_getParam('mid'));
        if ($max_alloc == 0) {
            // Portfolio Full
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Create Pick',
                'hd2' => 'Cannot create any more picks',
                'bd' => '<p class="error">Your allocation is at 100% and no further picks can be made.</p><p>You must first close one or more existing open picks before you can open a new pick.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->internal->target->pre . '/pick/p/mid/' . $this->_getParam('mid') . '/') . '">Continue &raquo;</a></p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
        for ($i = 1; $i <= $max_alloc; $i++) {
            $alloc_fill[$i] = $i;
        }
        #$form->getElement('allocation')->setMultiOptions($alloc_fill)->setValue($max_alloc);
        $form->getElement('allocation')->setMultiOptions($alloc_fill);
        
        // If posted and no title, set a title
        if ($this->getRequest()->isPost() && !$this->_getParam('title')) {
            $title = ($this->_getParam('position') == -1 ? 'Sell Short' : 'Buy Long');
            $title .= ' ' . strtoupper($this->_getParam('symbol'));
            $this->_setParam('title', $title);
        }
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db
        $this->view->formErrors = array();
        
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array(
                'title' => 'title', 'heading' => 'heading', 'summary' => 'summary', 'more_link' => 'more_link', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'content' => 'content', 'position' => 'position', 'allocation' => 'allocation', 'risk' => 'risk', 'target_price' => 'target_price', 'timeframe' => 'timeframe', 'suggested_stop_loss' => 'suggested_stop_loss', 'trailing_stop_loss' => 'trailing_stop_loss', 'trailing_stop_loss_type' => 'trailing_stop_loss_type', 'live_stop_loss' => 'live_stop_loss', 'notes' => 'notes', 'holding' => 'holding', 'disclosure' => 'disclosure', 'show_on_fail' => 'show_on_fail', 'mid' => 'matrix_counter'
            );
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
            }
            
            // Symbol & Open Temp Price
            if ($this->_getParam('symbol')) {
                $quotes = new ViaMe_Vm_Quotes();
                $verify = $quotes->verify($this->_getParam('symbol'));
                
                if ((isset($verify[strtoupper($this->_getParam('symbol'))])) && ($this->db->fetchOne("SELECT (type='M' OR type='S' OR type='E' OR type='C') FROM quote_view_symbol_matrix WHERE id=?", $verify[strtoupper($this->_getParam('symbol'))]->id))) {

                    $data['symbol_id'] = $verify[strtoupper($this->_getParam('symbol'))]->id;
                    $quote = $quotes->fetch($this->_getParam('symbol'), 'l1');
                    $quoteRT = $quotes->fetchRT($this->_getParam('symbol'));
                    #Zend_Debug::Dump($quoteRT);
                    $dt_now = new DateTime('now');
                    $dt_open = new DateTime('now');
                      $dt_open->setTime(06, 30, 00);
                    $dt_close = new DateTime('now');
                      $dt_close->setTime(13, 00, 00);
                    
                    # No more volume data with removal of igoogle
                    #if (
                    #    $quoteRT && isset($quoteRT[0]['l']) && $quoteRT[0]['l'] && $quoteRT[0]['l'] >= .01 && isset($quoteRT[0]['volume']) && $quoteRT[0]['volume'] > 0 &&
                    #    (date('N') >= 1 && date('N') < 6) &&
                    #    ($dt_now >= $dt_open && $dt_now <= $dt_close)
                    #) {
                    if (
                        $quoteRT && isset($quoteRT[0]['l']) && $quoteRT[0]['l'] && $quoteRT[0]['l'] >= .01 &&
                        (date('N') >= 1 && date('N') < 6) &&
                        ($dt_now >= $dt_open && $dt_now <= $dt_close)
                    ) {
                        $data['open_temp_price'] = $data['open_price'] = $quoteRT[0]['l'];
                    }
                    elseif ($quote[0][0]) {
                        if ($quote[0][0] >= .01) {
                            $data['open_temp_price'] = $quote[0][0];
                        }
                        else {
                            $this->view->formErrors[] = 'That stock (' . $form->getElement('symbol')->getValue() . ') does not meet the minimum price requirement.';
                            $form->getElement('symbol')->setValue('');
                            $form->getElement('title')->setValue('');
                        }
                    }
                    else {
                        $this->view->formErrors[] = 'Could not get a valid quote for that symbol.';
                    }
                }
                else {
                    // Reset and set any error messages
                    $form->getElement('symbol')->setValue('');
                    $form->getElement('title')->setValue('');
                    $this->view->formErrors[] = 'Invalid symbol specified.';
                }

            }
            
            #Zend_Debug::Dump($data);
            #$this->view->formErrors[] = 'TESTING';
            
            // Target Date
            if ($this->_getParam('target_date')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('target_date'));
                $data['target_date'] = $date->get(Zend_Date::ISO_8601);
            }
            
            /*
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
            }
            
            // Expiration Field
            if ($this->_getParam('expiration')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('expiration'));
                $data['expiration'] = $date->get(Zend_Date::ISO_8601);
            }
            */
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
            
            if (!(isset($this->view->formErrors) && count($this->view->formErrors))) { // No Errors - Insert
                try {
                    $pick_picks = new pick_models_picks();
                    $pick_picks->insert($data);
                    
                    $this->log->ERR('_pickPublished');
                    
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                    #Zend_Debug::Dump($data);
                } catch (Exception $e) {
                    $this->view->formErrors[] = 'That pick was not successfully created.';
                    #Zend_Debug::Dump($data);
                    #$profiler = $this->db->getProfiler();
                    #$query = $profiler->getLastQueryProfile();
                    #echo $query->getQuery();
                }
            }
        }
        else {
            $form->populate($_GET);
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Create A New Pick' . ($this->target->currentModule->display ? ' (' . $this->target->currentModule->display . ')' : '');
    }
}