<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Log_IndexController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    
    public function indexAction()
    {
        $select = $this->db->select()
            ->from(array('l' => 'log_logs'), array('*', "array_to_string(parameter_values, '" . $this->config->delimiter . "') as parameter_values_delimited"       ))
            ->join(array('m' => 'module_modules'), 'l.module_id = m.id', array('m_name' => 'name', 'm_display' => 'display', 'm_description' => 'description'))
            ->order(array('l.creation DESC'));
            
        switch($this->target->type) {
            case 'VIA':
                $select->where('via_id=?', $this->target->id);
                break;
            case 'NET':
                $select->where('net_id=?', $this->target->id);
                break;
            default:
                $select->where('com_id=?', $this->target->id);
                break;
        }
        
        $this->view->logs = $this->db->fetchAll($select);
        #Zend_Debug::Dump($this->view->logs);
    }
}