<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class contact_models_groupMembers extends Zend_Db_Table_Abstract
{
    protected $_name = 'contact_group_members';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'group_counter_id', 'member_profile_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Group' => array(
            'columns'           => array('profile_id', 'group_counter_id'),
            'refTableClass'     => 'contact_models_groupGroups',
            'refColumns'        => array('profile_id', 'counter')
        ),
        'Member' => array(
            'columns'           => 'member_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}

