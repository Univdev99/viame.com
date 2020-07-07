<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Profile_SwitchController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_OWNER;
    //protected $_mustBeOwner = true;
    
    
    public function indexAction()
    {
        $switched = false;
        
        if ($this->_getParam('id')) {
            $sp = $this->_getParam('id');
            // Profile Selected
            foreach ($this->member->profiles as $profile) {
                if ($profile->id == $sp) {
                    // Can't redirect to /via/me/ with the same parameters, so we
                    //  have to replace me with the id
                    #if (stripos($this->_getParam('redirect'), '/via/me/')) {
                    #    $this->_setParam('redirect',  str_ireplace('/via/me/', '/via/' . $this->member->profile->id . '/', $this->_getParam('redirect')));
                    #}
                    #$this->member->profile = $profile;
                    $this->VMAH->setVMCookie($this->config->auth->cookie_name->profile, "$sp", (isset($this->cookie->persist) ? (time() + $this->config->auth->cookie_lifetime->login) : 0));
                    
                    $switched = true;
                }
            }
        }
        
        $this->_autoredirect($switched ? '/via/' . $this->_getParam('id') . '/' : $this->target->pre . '/');
    }
}