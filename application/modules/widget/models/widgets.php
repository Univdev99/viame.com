<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class widget_models_widgets extends Zend_Db_Table_Abstract
{
    protected $_name = 'widget_widgets';
    
    protected $_sequence = true;
    
    protected $_primary = 'id';
    
    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Member' => array(
            'columns'           => 'member_id',
            'refTableClass'     => 'member_models_members',
            'refColumns'        => 'id'
        )
    );
}

