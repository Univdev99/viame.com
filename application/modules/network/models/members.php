<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class network_models_members extends Zend_Db_Table_Abstract
{
    protected $_name = 'network_members';
    
    protected $_sequence = false;
    
    protected $_primary = array('network_id', 'member_profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Network' => array(
            'columns'           => 'network_id',
            'refTableClass'     => 'network_models_networks',
            'refColumns'        => 'id'
        ),
        'Member' => array(
            'columns'           => 'member_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}

