<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class module_models_matrix extends Zend_Db_Table_Abstract
{
    protected $_name = 'module_matrix';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'module_id', 'counter');
    
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
        )
    );
}

