<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class profile_models_profiles extends Zend_Db_Table_Abstract
{
    protected $_name = 'profile_profiles';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Member' => array(
            'columns'           => 'member_id',
            'refTableClass'     => 'member_models_members',
            'refColumns'        => 'id'
        ),
        'Community' => array(
            'columns'           => 'community_id',
            'refTableClass'     => 'system_models_communities',
            'refColumns'        => 'id'
        )
    );
}

