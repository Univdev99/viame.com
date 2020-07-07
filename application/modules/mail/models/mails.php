<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class mail_models_mails extends Zend_Db_Table_Abstract
{
    protected $_name = 'mail_mails';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'counter');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}