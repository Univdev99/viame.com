<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class widget_models_matrix extends Zend_Db_Table_Abstract
{
    protected $_name = 'widget_matrix';
    
    protected $_sequence = false;
    
    protected $_primary = array('com_id', 'net_id', 'via_id', 'widget_id', 'counter');
    
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
        'Widget' => array(
            'columns'           => 'widget_id',
            'refTableClass'     => 'widget_models_widgets',
            'refColumns'        => 'id'
        )
    );
}

