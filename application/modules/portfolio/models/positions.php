<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class portfolio_models_positions extends Zend_Db_Table_Abstract
{
    protected $_name = 'portfolio_positions';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'matrix_counter', 'item_counter', 'counter');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Module' => array(
            'columns'           => 'module_id',
            'refTableClass'     => 'module_models_modules',
            'refColumns'        => 'id'
        ),
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
        'Symbol' => array(
            'columns'           => 'symbol_id',
            'refTableClass'     => 'quote_models_symbols',
            'refColumns'        => 'id'
        ),
        #'Currency' => array(
        #    'columns'           => 'currency_code',
        #    'refTableClass'     => 'system_models_currencies',
        #    'refColumns'        => 'code'
        #)
    );
}

