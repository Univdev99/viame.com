<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Admin_QuoteController extends ViaMe_Controller_Action
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
        if (
            !isset($this->member) ||
            !($this->member->site_admin || $this->member->profile->site_admin)
        ) { return $this->_denied(); }
        else {
            $this->view->headTitle('Admin', 'PREPEND');
            $this->view->headTitle('Quotes', 'PREPEND');
        
            // Change Sub Layout
            $this->_helper->ViaMe->setSubLayout('default');
        }
    }
    
    public function dupintsymsAction()
    {
        $this->view->headTitle('Duplicate Internal Symbols Report', 'PREPEND');
        
        $select = $this->db->select()
            ->from('quote_view_symbol_matrix', array('id', 'name', 'internal_symbol', 'delayed_symbol', 'type', 'typedisp', 'exch', 'exchdisp'))
            ->where('internal_symbol IN (SELECT internal_symbol FROM quote_view_symbol_matrix GROUP BY internal_symbol HAVING COUNT(*) > 1 ORDER BY internal_symbol)')
            ->order('internal_symbol')
        ;
        
        $paginator = Zend_Paginator::factory($select);
        $paginator->setCurrentPageNumber($this->_getParam('page', 1));
        $paginator->setItemCountPerPage($this->_getParam('limit', 100));
        $paginator->setPageRange(11);
        
        $this->view->paginator = $paginator;
    }
    
    
    public function symboleditAction()
    {
        $this->view->headTitle('Symbol Edit', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'symbol_edit_form',
                'method' => 'post',
                'id' => 'symbol_edit_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.symbol_edit_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'symbol' => array('Text', array(
                    'label' => 'Symbol',
                    'description' => 'Base stock symbol.',
                    'maxlength' => 16,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="merging symbol", message="The {label} field cannot be empty.", groups=[symbol_edit_form])',
                    'order' => 5,
                    'validators' => array(
                        array('StringLength', false, array(0, 16))
                    )
                )),
                'name' => array('Text', array(
                    'label' => 'Name',
                    'description' => 'Name of company or equity.',
                    'maxlength' => 256,
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="name", message="The {label} field cannot be empty.", groups=[symbol_edit_form])',
                    'order' => 10,
                    'validators' => array(
                        array('StringLength', false, array(0, 256))
                    )
                )),
                'type_id' => array('Select', array(
                    'label' => 'Type of Equity',
                    'required' => true,
                    'order' => 15,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'exchange_id' => array('Select', array(
                    'label' => 'Exchange',
                    'required' => true,
                    'order' => 20,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'delayed_symbol_override' => array('Text', array(
                    'label' => 'Delayed Symbol Override',
                    'description' => 'Override symbol for delayed data feed.',
                    'maxlength' => 32,
                    'order' => 25,
                    'validators' => array(
                        array('StringLength', false, array(0, 32))
                    )
                )),
                'realtime_symbol_override' => array('Text', array(
                    'label' => 'Realtime Symbol Override',
                    'description' => 'Override symbol for realtime data feed.',
                    'maxlength' => 32,
                    'order' => 30,
                    'validators' => array(
                        array('StringLength', false, array(0, 32))
                    )
                )),
                'description' => array('Textarea', array(
                    'label' => 'Description',
                    'description' => 'Description of the company or equity.',
                    'order' => 35
                )),
                'featured' => array('Checkbox', array(
                    'label' => 'Featured Stock',
                    'order' => 40
                )),
                'logo_url' => array('Text', array(
                    'label' => 'Logo URL',
                    'description' => 'URL of logo image.',
                    'maxlength' => 256,
                    'order' => 45,
                    'validators' => array(
                        array('StringLength', false, array(0, 256))
                    )
                )),
                'website_url' => array('Text', array(
                    'label' => 'Website URL',
                    'description' => 'URL of website.',
                    'maxlength' => 512,
                    'order' => 50,
                    'validators' => array(
                        array('StringLength', false, array(0, 512))
                    )
                )),
                'report_url' => array('Text', array(
                    'label' => 'Report URL',
                    'description' => 'URL of company report.',
                    'maxlength' => 512,
                    'order' => 55,
                    'validators' => array(
                        array('StringLength', false, array(0, 512))
                    )
                ))
            )
        ));
        
        // Load Up Types
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT id, display FROM quote_types WHERE active ORDER BY orderby, id") as $temp) {
            $multioptions[$temp->id] = $temp->display;
        }
        $form->getElement('type_id')->setMultiOptions($multioptions);
        
        // Load Up Exchanges
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT id, code, exchange FROM quote_exchanges WHERE active ORDER BY exchange, orderby, id") as $temp) {
            $multioptions[$temp->id] = $temp->exchange . ' (' . $temp->code . ')';
        }
        $form->getElement('exchange_id')->setMultiOptions($multioptions);
        
        $form->addElement('Submit', 'submit', array('label' => 'Update', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('symbol_edit_form').vivregval_canceled = true;"));
        
        $form->addElement('Hidden', 'id', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('id', 'vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && !$this->_getParam('ft') && $form->isValid($this->_getAllParams())) {
            $fields = array('symbol', 'name',  'type_id', 'exchange_id', 'delayed_symbol_override', 'realtime_symbol_override', 'description', 'featured', 'logo_url', 'website_url', 'report_url');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            
            $where = array();
            $where[] = $this->db->quoteInto('id=?', $this->_getParam('id'));
            
            try {
                $this->db->update('quote_symbols', $data, $where);
                
                $this->_autoredirect('/admin/');
            } catch (Exception $e) {
                $this->view->formErrors = array('An unexpected error has occurred and that stock edit was not successful.');
            }
        }
        elseif ($this->getRequest()->isPost() && $this->_getParam('id') && !preg_match('/[0-9]/', $this->_getParam('id'))) {
            foreach ($this->db->fetchAll("SELECT id, name, symbol FROM quote_symbols WHERE active AND lower(symbol)=lower(?)", $this->_getParam('id')) as $temp) {
                echo '<p><a href="?id=' . $temp->id . '">' . $temp->id . ' - ' . $temp->name . ' (' . $temp->symbol . ')</a></p>';
            }
            
            return $this->_helper->viewRenderer->setNoRender();
        }
        elseif (!$this->_getParam('id')) {
            $form = new Zend_Form();
            $form->setOptions(array(
                'attribs' => array(
                    'action' => '?',
                    'name' => 'symbol_edit_form',
                    'method' => 'post',
                    'id' => 'symbol_edit_form',
                    'class' => 'form',
                    'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.symbol_edit_form] }) && YAHOO.viame.dubsub.check(this));'
                ),
                'elementPrefixPath' => array(
                    'prefix' => 'ViaMe',
                    'path' => 'ViaMe/'
                ),
                'elements' => array(
                    'id' => array('Text', array(
                        'label' => 'Symbol ID',
                        'description' => 'Internal ID or symbol of company to edit.',
                        'required' => true,
                        'class' => 'regula-validation',
                        'data-constraints' => '@Required(label="symbol id ", message="The {label} field cannot be empty.", groups=[symbol_edit_form])',
                        'order' => 5,
                        'validators' => array(
                            array('Int')
                        )
                    ))
                )
            ));
            
            $form->addElement('Submit', 'submit', array('label' => 'Submit', 'order' => 996));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('symbol_edit_form').vivregval_canceled = true;"));
            
            $form->addElement('Hidden', 'ft', array('value' => 1));
            $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
            $form->addDisplayGroup(array('ft', 'vmpd_nar', 'redirect'), 'hidden');
        }
        else {
            if ($info = $this->db->fetchRow('SELECT * FROM quote_symbols WHERE active AND id=?', $this->_getParam('id'))) {
                $form->populate((array) $info);
            }
            else {
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Symbol Edit Error',
                    'hd2' => 'Symbol not found',
                    'bd' => '<p class="error">That symbol id could not be found.</p><p>That symbol could not be found or is deactivated.  You may want to check the database.</p><p>Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p>'
                ));
                return $this->_helper->viewRenderer->setNoRender();
            }
        }
        
        echo $form;
        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function stocksplitAction()
    {
        $this->view->headTitle('Stock Split', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'stock_split_form',
                'method' => 'post',
                'id' => 'stock_split_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.stock_split_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'id' => array('Text', array(
                    'label' => 'Splitting Symbol ID',
                    'description' => 'The ID of the stock that is splitting.',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="symbol id", message="The {label} field cannot be empty.", groups=[stock_split_form])',
                    'order' => 5,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'ratio1' => array('Text', array(
                    'label' => 'New Number of Shares',
                    'description' => 'First number of the split ratio.',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="new shares", message="The {label} field cannot be empty.", groups=[stock_split_form])',
                    'order' => 10,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'ratio2' => array('Text', array(
                    'label' => 'Old Number of Shares',
                    'description' => 'Second number of the split ratio.',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="old shares", message="The {label} field cannot be empty.", groups=[stock_split_form])',
                    'order' => 15,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'effective_date' => array('Text', array(
                    'label' => 'Effective Date',
                    'description' => 'Last day trading with the old pre-split adjusted price (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ') - Defaults to today.  Should ALWAYS be a weekday.',
                    'class' => 'vmfh_date',
                    'autocomplete' => 'off',
                    
                    'order' => 20,
                    'validators' => array(
                        #array('Date', false, array('YYYY-MM-DD')),
                        array('Regex', false, array(
                            'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . '$/',
                            'messages' => array(
                                Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                            )
                        )),
                        array('Date', false,
                            preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short')))
                        )
                    )
                )),
                'closed' => array('Checkbox', array(
                    'label' => 'Include Closed Positions',
                    'description' => 'Processing of already closed positions.',
                    'order' => 25
                )),
                'straddle' => array('Checkbox', array(
                    'label' => 'Attempt to Adjust For Straddlers',
                    'description' => 'Adjust opening prices only for straddlers.',
                    'value' => true,
                    'order' => 30
                ))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Split Stock', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('stock_split_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $this->db->beginTransaction();
            
            $date = new Zend_Date();
            if ($this->_getParam('effective_date')) {
                $date->set($this->_getParam('effective_date'));
            }
            $date = $date->toString('YYYY-MM-dd');
            $one_bd_before = "DATE(DATE('$date') - (CASE WHEN TO_CHAR(DATE('$date'), 'D')::Int=1 THEN ('2 Days'::INTERVAL) WHEN TO_CHAR(DATE('$date'), 'D')::Int=2 THEN ('3 Days'::INTERVAL) ELSE ('1 Day'::INTERVAL) END))";
            $date = "DATE('$date')";
            
            $updates = $wheres = array();
            
            $updates['open_temp_price'] = new Zend_Db_Expr('open_temp_price * ' . $this->_getParam('ratio2') . ' / ' . $this->_getParam('ratio1'));
            $updates['open_price'] = new Zend_Db_Expr('open_price * ' . $this->_getParam('ratio2') . ' / ' . $this->_getParam('ratio1'));
            $updates['close_temp_price'] = new Zend_Db_Expr('close_temp_price * ' . $this->_getParam('ratio2') . ' / ' . $this->_getParam('ratio1'));
            $updates['close_price'] = new Zend_Db_Expr('close_price * ' . $this->_getParam('ratio2') . ' / ' . $this->_getParam('ratio1'));
            
            $wheres[] = $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER');
            $wheres[] = "open_datestamp < ($date || ' 13:00:00')::TIMESTAMP";
            
            if ($this->_getParam('closed')) {
                $wheres[] = "close_datestamp ISNULL OR close_price ISNULL OR close_datestamp < ($date || ' 13:00:00')::TIMESTAMP";
            }
            else {
                $wheres[] = "close_datestamp ISNULL OR close_price ISNULL";
            }
            
            
            try {
                #Zend_Debug::Dump($updates);
                #Zend_Debug::Dump($wheres);
                $this->db->update('pick_picks',
                    $updates,
                    $wheres
                );
                
                if ($this->_getParam('straddle')) {
                    unset($updates['close_temp_price']);
                    unset($updates['close_price']);
                    unset($wheres[count($wheres) - 1]);
                    
                    $wheres[] = "close_price NOTNULL";
                    $wheres[] = "close_datestamp >= ($date || ' 13:00:00')::TIMESTAMP";
                    
                    #Zend_Debug::Dump($updates);
                    #Zend_Debug::Dump($wheres);
                    $this->db->update('pick_picks',
                        $updates,
                        $wheres
                    );
                }
                
                /* Portfolio Positions
                     - shares, price, upper_limit, lower_limit
                */
                /*
                $this->db->update('portfolio_positions',
                    $updates,
                    $wheres
                );
                */
                
                $this->db->commit();
                $this->_autoredirect('/admin/');
            } catch (Exception $e) {
                $this->db->rollBack();
                $this->view->formErrors = array('That stock split was not successful.');
            }
        }
        else {
            $form->populate($this->_getAllParams());
        }
        
        echo $form;
        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function symbolmergeAction()
    {
        $this->view->headTitle('Symbol Merge', 'PREPEND');
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect('/admin/');
        }
        
        // Load the Form
        $form = new Zend_Form();
        $form->setOptions(array(
            'attribs' => array(
                'action' => '?',
                'name' => 'symbol_merge_form',
                'method' => 'post',
                'id' => 'symbol_merge_form',
                'class' => 'form',
                'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.symbol_merge_form] }) && YAHOO.viame.dubsub.check(this));'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'id' => array('Text', array(
                    'label' => 'Merging Symbol ID',
                    'description' => 'Merge this symbol into another symbol.',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="merging symbol", message="The {label} field cannot be empty.", groups=[symbol_merge_form])',
                    'order' => 5,
                    'validators' => array(
                        array('Int')
                    )
                )),
                'merge' => array('Text', array(
                    'label' => 'Symbol To Be Merge Into ID',
                    'description' => 'This is the resulting symbol after merging.',
                    'required' => true,
                    'class' => 'regula-validation',
                    'data-constraints' => '@Required(label="resulting symbol", message="The {label} field cannot be empty.", groups=[symbol_merge_form])',
                    'order' => 10,
                    'validators' => array(
                        array('Int')
                    )
                ))
            )
        ));
        
        $form->addElement('Submit', 'submit', array('label' => 'Merge Symbols', 'order' => 996));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('symbol_merge_form').vivregval_canceled = true;"));

        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('vmpd_nar', 'redirect'), 'hidden');
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            /*
                Quote - Merge a Symbol (x) Into Another Symbol (z)
                
                    1. UPDATE module_template SET symbols=(array_except(symbols, ARRAY[x]::bigint[]) || ARRAY[z]::bigint[]) WHERE symbols @> ARRAY[x]::bigint[];
                    2. UPDATE pick_picks SET symbol_id=z WHERE symbol_id=x;
                    3. UPDATE portfolio_positions SET symbol_id=z WHERE symbol_id=x;
                    4. UPDATE quote_comments SET symbol_id=z WHERE symbol_id=x;
                    5. DELETE FROM quote_data WHERE symbol_id=x;
                    6. DELETE FROM quote_symbols WHERE id=x;
            */
            
            $this->db->beginTransaction();
            try {
                $query = 'UPDATE module_template SET symbols=' . $this->db->quoteInto('(array_except(symbols, ARRAY[?]::bigint[])', $this->_getParam('id'), 'INTEGER') . $this->db->quoteInto(' || ARRAY[?]::bigint[])', $this->_getParam('merge'), 'INTEGER') . $this->db->quoteInto(' WHERE symbols @> ARRAY[?]::bigint[]', $this->_getParam('id'), 'INTEGER');
                $this->db->getConnection()->exec($query);
                
                $this->db->update('pick_picks',
                    array(
                        'symbol_id' => $this->_getParam('merge')
                    ),
                    $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER')
                );
                
                $this->db->update('portfolio_positions',
                    array(
                        'symbol_id' => $this->_getParam('merge')
                    ),
                    $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER')
                );
                
                $this->db->update('quote_comments',
                    array(
                        'symbol_id' => $this->_getParam('merge')
                    ),
                    $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER')
                );
                
                // Need to delete the duplicate entries first so no duplicate key constraint
                $this->db->delete('quote_follow_matrix',
                    array(
                        $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER'),
                        $this->db->quoteInto('profile_id IN (SELECT profile_id FROM quote_follow_matrix WHERE symbol_id=?)', $this->_getParam('merge'), 'INTEGER'),
                    )
                );
                $this->db->update('quote_follow_matrix',
                    array(
                        'symbol_id' => $this->_getParam('merge')
                    ),
                    $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER')
                );
                
                $this->db->delete('quote_data', $this->db->quoteInto('symbol_id=?', $this->_getParam('id'), 'INTEGER'));
                
                $this->db->delete('quote_symbols', $this->db->quoteInto('id=?', $this->_getParam('id'), 'INTEGER'));
                
                $this->db->commit();
                $this->_autoredirect('/admin/');
            } catch (Exception $e) {
                $this->db->rollBack();
                $this->view->formErrors = array('That symbol merge was not successful.');
            }
        }
        else {
            $form->populate($this->_getAllParams());
        }
        
        echo $form;
        $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function symboldeactivateAction()
    {
        $this->view->headTitle('Deactivate Symbol', 'PREPEND');
        
        $this->db->update('quote_symbols', array('active' => 'f'), $this->db->quoteInto('id=?', $this->_getParam('id')));
        $this->_helper->viewRenderer->setNoRender();
        $this->_autoredirect('/admin/');
    }
    
    
    public function symboldeleteAction()
    {
        $this->view->headTitle('Delete Symbol', 'PREPEND');
        
        $this->_helper->viewRenderer->setNoRender();
        
        if ($this->db->fetchOne("SELECT 't'::bool from quote_view_symbol_matrix WHERE id=? AND id NOT IN (SELECT symbol_id from pick_picks UNION SELECT symbol_id FROM portfolio_positions UNION SELECT symbol_id FROM quote_comments) AND COALESCE((SELECT DISTINCT 'f'::bool FROM module_template WHERE symbols @> ARRAY[id]::bigint[]), 't'::bool)", $this->_getParam('id'))) {
            $this->db->delete('quote_symbols', $this->db->quoteInto('id=?', $this->_getParam('id')));
            $this->_autoredirect('/admin/');
        }
        else {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Symbol Deletion Error',
                'hd2' => 'Symbol in use',
                'bd' => '<p class="error">That symbol is in use.</p><p>It is recommended to not delete symbols that are in use by the system, but to deactivate or merge them instead.</p><p>Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p>'
            ));
        }
    }
}