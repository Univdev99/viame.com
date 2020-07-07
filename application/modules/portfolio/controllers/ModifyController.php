<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Portfolio_ModifyController extends ViaMe_Controller_Default_Edit
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    #public function indexAction()
    #{
    #    $this->_helper->viewRenderer->setNoRender();
    #   echo 'Index';
    #}
    
    
    public function addAction()
    {
        // Set some defaults
        if (!$this->_getParam('mid')) { $this->_setParam('mid', 0); }
        if (!$this->_getParam('id')) { $this->_setParam('iid', 0); }
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->_getParam('mid') . '/id/' . $this->_getParam('id') . '/' );
        }
        
        require dirname(dirname(__FILE__)) . '/models/positions_form.php';
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        $form->setMethod('post');
        
        // Matrix Counter
        $form->addElement('Hidden', 'mid', array('required' => true));
        // Item Counter
        $form->addElement('Hidden', 'id', array('required' => true));
        
        $form->addElement('Submit', 'submit', array('label' => 'Add Position', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        
        // Redirects
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $fields = array('position', 'shares', 'price', 'upper_limit', 'lower_limit', 'notes');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
            }
            
            // Purchase Date Field
            if ($this->_getParam('purchase_date')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('purchase_date'));
                $data['purchase_date'] = $date->get(Zend_Date::ISO_8601);
            }
            
            // Symbol ID Field
            if ($this->_getParam('symbol')  &&
                !($data['symbol_id'] = $this->db->fetchOne('SELECT id From quote_view_symbol_matrix WHERE UPPER(internal_symbol) = UPPER(?)', $this->_getParam('symbol')))) {
                $quotes = new ViaMe_Vm_Quotes();
                $results = $quotes->fetch($this->_getParam('symbol'));
                $data['symbol_id'] = $this->db->fetchOne('SELECT id From quote_view_symbol_matrix WHERE UPPER(internal_symbol) = UPPER(?)', $this->_getParam('symbol'));
            }
            
            $data['profile_id'] = $this->member->profile->id;
            $data[strtolower($this->target->type) . '_id'] = $this->target->id;   
            
            try {
                $portfolio_positions = new portfolio_models_positions();
                $portfolio_positions->insert($data);
                
                $this->log->INFO('New position added.');
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->_getParam('mid') . '/id/' . $this->_getParam('id') . '/' );
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That position was not successfully added.');
                #Zend_Debug::Dump($data);
            }
        }
        
        $this->view->form = $form;
        
        $this->renderScript('form.phtml');
    }
    
    
    #public function editAction()
    #{
    #    $this->_helper->viewRenderer->setNoRender();
    #    echo 'Edit';
    #}
    
    
    public function deleteAction()
    {
        if ($this->_getParam('mid', 0) && $this->_getParam('id', 0) && $this->_getParam('pid', 0)) {
            $data = array('active' => NULL);
            
            $where = array();
            $where[] = $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid', 0));
            $where[] = $this->db->quoteInto('item_counter=?', $this->_getParam('id', 0));
            $where[] = $this->db->quoteInto('counter=?', $this->_getParam('pid', 0));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);            
            
            $this->db->update(
                'portfolio_positions',
                $data,
                $where
            );
        }
        
        $this->_redirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/view/p/mid/' . $this->_getParam('mid', 0) . '/id/' . $this->_getParam('id', 0) . '/' );
    }
}