<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class profile_models_profileFollowMatrix extends Zend_Db_Table_Abstract
{
    protected $_name = 'profile_follow_matrix';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'follow_profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Follow_Profile' => array(
            'columns'           => 'follow_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}