<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class quote_models_data extends Zend_Db_Table_Abstract
{
    protected $_name = 'quote_data';
    
    protected $_sequence = false;
    
    protected $_primary = array('symbol_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Symbol' => array(
            'columns'           => 'symbol_id',
            'refTableClass'     => 'quote_models_symbols',
            'refColumns'        => 'id'
        )
    );
}

