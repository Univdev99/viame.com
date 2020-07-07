<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class member_models_members extends Zend_Db_Table_Abstract
{
    protected $_name = 'member_members';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Referrer' => array(
            'columns'           => 'referrer_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Community' => array(
            'columns'           => 'community_id',
            'refTableClass'     => 'system_models_communities',
            'refColumns'        => 'id'
        )
    );
}

