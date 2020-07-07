<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class member_models_openids extends Zend_Db_Table_Abstract
{
    protected $_name = 'member_openids';
    
    protected $_sequence = false;
    
    protected $_primary = array('url');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Member' => array(
            'columns'           => 'member_id',
            'refTableClass'     => 'member_models_members',
            'refColumns'        => 'id'
        )
    );
}

