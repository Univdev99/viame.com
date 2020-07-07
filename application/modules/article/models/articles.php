<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class article_models_articles extends Zend_Db_Table_Abstract
{
    protected $_name = 'article_articles';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'matrix_counter', 'counter');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Community' => array(
            'columns'           => 'com_id',
            'refTableClass'     => 'system_models_communities',
            'refColumns'        => 'id'
        ),
        'Network' => array(
            'columns'           => 'net_id',
            'refTableClass'     => 'network_models_networks',
            'refColumns'        => 'id'
        ),
        'Via' => array(
            'columns'           => 'via_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        )
    );
}

