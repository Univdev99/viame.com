<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class quote_models_symbols extends Zend_Db_Table_Abstract
{
    protected $_name = 'quote_symbols';
    
    protected $_sequence = false;
    
    protected $_primary = array('id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Type' => array(
            'columns'           => 'type_id',
            'refTableClass'     => 'quote_models_types',
            'refColumns'        => 'id'
        ),
        'Exchange' => array(
            'columns'           => 'exchange_id',
            'refTableClass'     => 'quote_models_exchanges',
            'refColumns'        => 'id'
        )
    );
}

