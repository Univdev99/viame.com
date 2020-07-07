<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class profile_models_profileAlerts extends Zend_Db_Table_Abstract
{
    protected $_name = 'profile_alerts';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'type', 'counter');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}