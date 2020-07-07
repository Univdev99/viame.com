<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_View_Helper_RelatedQuotes extends Zend_View_Helper_Abstract
{
    protected $_fileName = 'partials/_RelatedQuotes.phtml';
    protected $_moduleName = 'quote';
    
    public function RelatedQuotes($model = null)
    {
        if (isset($model['symbols'])) {
            if (!isset($this->_VMAH) && Zend_Controller_Action_HelperBroker::hasHelper('ViaMe')) {
                $this->_VMAH = Zend_Controller_Action_HelperBroker::getExistingHelper('ViaMe');
            }
            
            $ids = $this->_VMAH->ePgArray($model['symbols']);
            if (count($ids)) {
                // At least one time cache for same set
                if (isset($this->models[$model['symbols']])) {
                    $model['quote_data'] = $this->models[$model['symbols']]['quote_data'];
                    $model['featured'] = $this->models[$model['symbols']]['featured'];
                }
                else {
                    $quotes = new ViaMe_Vm_Quotes();
                    $model['quote_data'] = $quotes->fetchDelayedById($ids);
                    
                    if ($db = Zend_Registry::get('db')) {
                        // Are any featured stocks?
                        $whereClause = null;
                        foreach ($ids as $key) {
                            $whereClause[] = $db->quoteInto('obj.id=?', $key);
                        }
                        
                        $select = $db->select()
                            ->from(array('obj' => 'quote_symbols'), array('*', 'random() AS random'))
                            ->join(array('qvd' => 'quote_view_data'), 'obj.id = qvd.id')
                            ->where('obj.featured')
                            ->where(implode(' OR ', $whereClause))
                            ->order('random');
                            #->limit(1);
                        
                        $model['featured'] = $db->fetchAll($select);
                        
                        $this->models[$model['symbols']] = array();
                        $this->models[$model['symbols']]['quote_data'] = $model['quote_data'];
                        $this->models[$model['symbols']]['featured'] = $model['featured'];
                    }
                }
                
                return $this->view->partial($this->_fileName, $this->_moduleName, $model);
            }
        }
    }
}