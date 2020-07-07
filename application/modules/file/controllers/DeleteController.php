<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class File_DeleteController extends ViaMe_Controller_Default_Delete
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction() {
        // Delete Any Symlink
        if ($this->_modObject->public_location) {
            // Delete Symlink
            $ext = preg_replace('/.*?(\.[^\.]*)$/', '${1}', $this->_modObject->file_name);
            unlink($this->config->upload->public_directory . '/' . $this->_modObject->file_dir . '/' . $this->_modObject->file_id . "$ext");
        }
        
        parent::indexAction(); // Takes care of deletion and redirects
    }
}