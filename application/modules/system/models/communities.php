<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class system_models_communities extends Zend_Db_Table_Abstract
{
    protected $_name = 'system_communities';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Member' => array(
            'columns'           => 'member_id',
            'refTableClass'     => 'member_models_members',
            'refColumns'        => 'id'
        ),
        'Parent' => array(
            'columns'           => 'parent_id',
            'refTableClass'     => 'system_models_communities',
            'refColumns'        => 'id'
        )
    );
}

