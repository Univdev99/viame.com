<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class ViaMe_Log_Writer_Db extends Zend_Log_Writer_Db
{
    public function __construct($db, $table, $columnMap = null)
    {
        $this->_db    = $db;
        $this->_table = $table;
        $this->_columnMap = $columnMap;
    }
    
    
    protected function _write($event)
    {
        if ($this->_db === null) {
            throw new Zend_Log_Exception('Database adapter instance has been removed by shutdown');
        }
        
        if ($this->_columnMap === null) {
            $dataToInsert = $event;
        } else {
            $dataToInsert = array();
            foreach ($this->_columnMap as $columnName => $fieldKey) {
                if (isset($event[$fieldKey])) { $dataToInsert[$columnName] = $event[$fieldKey]; }
                unset($event[$fieldKey]);
            }
            if (count($event)) {
                foreach ($event as $eventKey => $eventVal) {
                    $dataToInsert[$eventKey] = $eventVal;
                }
            }
        }
        
        $this->_db->insert($this->_table, $dataToInsert);
    }
}