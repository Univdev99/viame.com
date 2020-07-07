<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Quote_QfirmcController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function reportAction() {
        $autoredirect = "/quote/";
        
        if ($this->_getParam('s')) {
            $select = $this->db->select()
                ->from(array('obj' => 'quote_symbols'), array('obj.id', 'obj.name', 'obj.symbol','obj.report_url'))
                ->where('obj.id = ?', $this->_getParam('s'))
                ->where('obj.active = ?', 't');
            if ($data = $this->db->fetchRow($select)) {
                if ($data->report_url) {
                    $this->_redirect($data->report_url);
                }
                else {
                    $autoredirect = '/' . ($data->name ? $this->view->SEO_Urlify($data->name) . '/s/' : '') . 'quote/p/s/' . urlencode($data->symbol) . '/';
                }
            }
        }
        
        $this->_autoredirect($autoredirect);
    }
    
    public function followAction() {
        $autoredirect = "/quote/";
        
        if ($this->_getParam('s')) {
            $select = $this->db->select()
                ->from(array('obj' => 'quote_symbols'), array('obj.id', 'obj.name', 'obj.symbol','obj.report_url'))
                ->where('obj.id = ?', $this->_getParam('s'))
                ->where('obj.active = ?', 't');
                
            if ($data = $this->db->fetchRow($select)) {
                $follow_matrix = new quote_models_followMatrix();
                
                if ($this->_getParam('unfollow')) {
                    try {
                        $data = array();
                        $data[] = $this->db->quoteInto('profile_id=?', $this->member->profile->id);
                        $data[] = $this->db->quoteInto('symbol_id=?', $this->_getParam('s'));
                        
                        $follow_matrix->delete($data);
                    } catch (Exception $e) { }
                }
                else {
                    try {
                        $insertion['profile_id'] = $this->member->profile->id;
                        $insertion['symbol_id'] = $this->_getParam('s');
                        
                        
                        $follow_matrix->insert($insertion);
                    } catch (Exception $e) { }
                    
                    $autoredirect = '/' . ($data->name ? $this->view->SEO_Urlify($data->name) . '/s/' : '') . 'quote/p/s/' . urlencode($data->symbol) . '/';
                }
            }
        }
        
        $this->_autoredirect($autoredirect);
    }
}