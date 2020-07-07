<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class quote_models_followMatrix extends Zend_Db_Table_Abstract
{
    protected $_name = 'quote_follow_matrix';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'symbol_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Symbol' => array(
            'columns'           => 'symbol_id',
            'refTableClass'     => 'quote_models_symbols',
            'refColumns'        => 'id'
        )
    );
}