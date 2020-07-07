<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class log_models_trans extends Zend_Db_Table_Abstract
{
    protected $_name = 'log_trans';
    
    protected $_sequence = false;
    
    //protected $_primary = 'id';
    
    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap    = array(
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

