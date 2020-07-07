<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_FollowController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    
    public function indexAction() {
        if ($this->_getParam('id')) {
            $follow_matrix = new profile_models_profileFollowMatrix();
            
            if ($this->_getParam('unfollow')) {
                try {
                    $data = array();
                    $data[] = $this->db->quoteInto('profile_id=?', $this->member->profile->id);
                    $data[] = $this->db->quoteInto('follow_profile_id=?', $this->_getParam('id'));
                    
                    $follow_matrix->delete($data);
                } catch (Exception $e) { }
            }
            else {
                try {
                    $data = array();
                    $data['profile_id'] = $this->member->profile->id;
                    $data['follow_profile_id'] = $this->_getParam('id');
                    
                    $follow_matrix->insert($data);
                } catch (Exception $e) { }
            }
        }
        
        $this->_autoredirect('/');
    }
}