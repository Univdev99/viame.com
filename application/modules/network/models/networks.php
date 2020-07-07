<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class network_models_networks extends Zend_Db_Table_Abstract
{
    protected $_name = 'network_networks';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Parent' => array(
            'columns'           => 'parent_id',
            'refTableClass'     => 'network_models_networks',
            'refColumns'        => 'id'
        )
    );
}

