<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class mail_models_stats extends Zend_Db_Table_Abstract
{
    protected $_name = 'mail_stats';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}