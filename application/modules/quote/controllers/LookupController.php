<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Quote_LookupController extends ViaMe_Controller_Action
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
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        $form = new Zend_Form(array(
            'attribs' => array(
                'class' => 'quote_form',
                'method' => 'get',
                'action' => $this->internal->target->pre . '/quote/lookup/'
            ),
            'elements' => array(
                's' => array('Text', array(
                'label' => 'Search Term',
                'id' => 'search_term',
                #'description' => '<a href="/quote/">Get Quotes</a>',
                'description' => 'Enter a full or partial search term for the symbol, company, or name of the index or fund.',
                'order' => 1
            )),
            )
        ));
        $form->getElement('s')->getDecorator('description')->setEscape(false);
        $this->view->form = $form;
        
        $this->view->headTitle('Quote', 'PREPEND');
        $this->view->headTitle('Lookup', 'PREPEND');
        
        // Do a lookup first, then fetch from database - Display With Pager
        if (($this->_getParam('s') && $form->isValid($this->_getAllParams())) || strlen($this->_getParam('start_char'))) {
            $quotes = new ViaMe_Vm_Quotes();
            $feed_results = $quotes->lookup(strlen($this->_getParam('start_char')) ? $this->_getParam('start_char') : $this->_getParam('s'));
            
            $select = $this->db->select()
                ->from('quote_view_symbol_matrix')
                ->where('active');
                
            if (strlen($this->_getParam('start_char'))) {
                $s = $this->_getParam('start_char');
                
                $select->where('name ILIKE ?', "$s%");
                
                $this->view->headTitle($s, 'PREPEND');
                if (isset($this->view->results[1])) { $this->view->headMeta()->setName('description', "Symbols starting with $s"); }
                if (isset($this->view->results[0])) { $this->view->headMeta()->setName('keywords', $s); }
            }
            else {
                $s = $this->_getParam('s');
                
                // Fixup adjustments - must match autocomplete and database
                for ($i = 0; $i < count($feed_results); $i++) {
                    #if ($feed_results[$i]['type'] == 'I' && !preg_match('/^\^/', $feed_results[$i]['symbol'])) {
                    #    $feed_results[$i]['symbol'] = '^' . $feed_results[$i]['symbol'];
                    #}
                    #if ($feed_results[$i]['exch'] == 'OBB' || $resfeed_resultsults[$i]['exch'] == 'PNK' || $feed_results[$i]['exch'] == 'WCB') {
                    #    $feed_results[$i]['symbol'] = preg_replace('/\..*/', '', $feed_results[$i]['symbol']);
                    #}
                    
                    $symbols[] = $feed_results[$i]['symbol'];
                }
                
                $orWhereClause = $this->db->quoteInto('name ILIKE ?', "%$s%");
                $orWhereClause .= $this->db->quoteInto(' OR to_tsvector(name) @@ plainto_tsquery(?)', $s);
                $orWhereClause .= $this->db->quoteInto(' OR symbol ILIKE ?', "%$s%");
                
                /*
                $select
                    ->orWhere('name ILIKE ?', "%$s%")
                    ->orWhere('to_tsvector(name) @@ plainto_tsquery(?)', $s)
                    ->orWhere('symbol ILIKE ?', "%$s%");
                */
                
                if (count($symbols)) {
                    $temp = $quotes->fetch($symbols, 'sl1');
                    foreach ($temp as $temp_result) {
                        #$select->orWhere('delayed_symbol=?', $temp_result[0]);
                        $orWhereClause .= $this->db->quoteInto(' OR delayed_symbol=?', $temp_result[0]);
                    }
                }
                
                $select->where($orWhereClause);
                
                $this->view->headTitle($s, 'PREPEND');
                if (isset($this->view->results[1])) { $this->view->headMeta()->setName('description', 'Lookup results for ' . $s); }
                if (isset($this->view->results[0])) { $this->view->headMeta()->setName('keywords', $s); }
            }
            
            $select->order(array('UPPER(name)', 'internal_symbol'));
            
            $paginator = Zend_Paginator::factory($select);
            $paginator->setCurrentPageNumber($this->_getParam('page', 1));
            $paginator->setItemCountPerPage($this->_getParam('limit', 25));
            $paginator->setPageRange(11);
            
            $this->view->paginator = $paginator;
        }
    }
}
