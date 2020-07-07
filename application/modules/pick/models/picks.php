<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class pick_models_picks extends Zend_Db_Table_Abstract
{
    protected $_name = 'pick_picks';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'matrix_counter', 'counter');

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
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Module' => array(
            'columns'           => 'module_id',
            'refTableClass'     => 'module_models_modules',
            'refColumns'        => 'id'
        ),
        'Symbol' => array(
            'columns'           => 'symbol_id',
            'refTableClass'     => 'quote_models_symbols',
            'refColumns'        => 'id'
        ),
    );
}

