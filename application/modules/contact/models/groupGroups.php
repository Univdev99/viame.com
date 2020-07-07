<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class contact_models_groupGroups extends Zend_Db_Table_Abstract
{
    protected $_name = 'contact_group_groups';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'counter');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Parent' => array(
            'columns'           => 'parent_id',
            'refTableClass'     => 'contact_models_groupGroups',
            'refColumns'        => 'counter'
        )
    );
}

