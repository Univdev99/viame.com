<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class mail_models_folderMatrix extends Zend_Db_Table_Abstract
{
    protected $_name = 'mail_folder_matrix';
    
    protected $_sequence = false;
    
    protected $_primary = array('profile_id', 'folder_counter_id', 'from_profile_id', 'from_counter_id');

    //protected $_dependentTables = array('ModelClassName');
    
    protected $_referenceMap = array(
        'Profile' => array(
            'columns'           => 'profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Folder' => array(
            'columns'           => array('profile_id', 'folder_counter_id'),
            'refTableClass'     => 'mail_models_folderFolders',
            'refColumns'        => array('profile_id', 'counter')
        ),
        'From' => array(
            'columns'           => 'from_profile_id',
            'refTableClass'     => 'profile_models_profiles',
            'refColumns'        => 'id'
        ),
        'Mail' => array(
            'columns'           => array('from_profile_id', 'from_counter_id'),
            'refTableClass'     => 'mail_models_mails',
            'refColumns'        => array('profile_id', 'counter')
        )
    );
}