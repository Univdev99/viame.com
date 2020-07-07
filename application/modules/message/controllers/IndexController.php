<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Message_IndexController extends ViaMe_Controller_Default_Index
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction($select = null)
    {
        if (isset($this->_allModuleSelect) && $this->_allModuleSelect) {
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $this->_allModuleSelect->__toString() . ')')), array('*'))
            ;
            
            $this->resetMID();
        }
        else {
            $select = $this->_buildComplexQuery($this->_tableName, $this->target->currentModule);
            
            $select = $this->addProfileSymbolClauses($select);
            
            // Wrap the select
            $select = $this->db->select()
              ->from(array('tt' => new Zend_Db_Expr('(' . $select->__toString() . ')')), array('*'))
            ;
        }
        
        
        // Topic or message view
        if ($this->_getParam('display') == 'topic') {
            // Topic View
            $select->where('parent_counter ISNULL');
        }
        
        // Should we show unmoderated messages?
        if (!(isset($this->masked) && $this->masked) && ($this->internal->target->acl->owner || ($this->internal->target->acl->privilege >= ViaMe_Controller_Action::ACL_MODERATE))) {
            $select->where('status ISNULL OR status <> ?', 'f');
        }
        else {
            $select->where('status=?', 't');
        }
        
        
        $this->_setParam('limit', $this->_getParam('limit', 40));
        
        parent::indexAction($select);
        
        if ($this->_getParam('display') == 'topic') {
            $this->render('tindex');
        }
    }
}