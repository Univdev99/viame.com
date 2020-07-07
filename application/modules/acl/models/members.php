<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class acl_models_members extends Zend_Db_Table_Abstract
{
    protected $_name = 'acl_members';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'module_id', 'matrix_counter', 'item_counter', 'profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Community' => array(
            'columns'           => 'com_id',
            'refTableClass'     => 'system_models_communities',
            'refColumns'        => 'id'
        ),
        'Network' => array(
            'columns'           => 'net_id',
            'refTableClass'     => 'network_models_networks',
            'refColumns'        => 'id'
        ),
        'Via' => array(
            'columns'           => 'via_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Module' => array(
            'columns'           => 'module_id',
            'refTableClass'     => 'module_models_modules',
            'refColumns'        => 'id'
        ),
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}

