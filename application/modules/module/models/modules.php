<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class module_models_modules extends Zend_Db_Table_Abstract
{
    protected $_name = 'module_modules';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';
    
    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Member' => array(
            'columns'           => 'member_id',
            'refTableClass'     => 'member_models_members',
            'refColumns'        => 'id'
        )
    );
}

