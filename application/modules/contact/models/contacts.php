<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class contact_models_contacts extends Zend_Db_Table_Abstract
{
    protected $_name = 'contact_contacts';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'contact_profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Contact' => array(
            'columns'           => 'contact_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}

