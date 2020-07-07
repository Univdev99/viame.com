<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Zfbp_BbhpController extends ViaMe_Controller_Action
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
    
    
    public function init()
    {
        parent::init();
        
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
    }
    
    public function preDispatch() { }
    
    public function indexAction()
    {
        $lifetime = 3600 * 24 * 30; // 30 Days
        $the_time = time();
        
        $blocked = true;
        $visitor = ip2long($_SERVER['REMOTE_ADDR']);
        
        $doNotBlock = array(
            '64.233.160.0 - 64.233.191.255',            # Google (Googlebot)
            '66.102.0.0 - 66.102.15.255',               # Google (Googlebot)
            '66.249.64.0 - 66.249.95.255',              # Google (Googlebot)
            '72.14.192.0 - 72.14.255.255',              # Google (Googlebot)
            '74.125.0.0 - 74.125.255.255',              # Google (Googlebot)
            '209.85.128.0 - 209.85.255.255',            # Google (Googlebot)
            '216.239.32.0 - 216.239.63.255',            # Google (Googlebot)
            
            '8.12.144.0 - 8.12.144.255',                # Yahoo (Yahoo! Slurp)
            '66.196.64.0 - 66.196.127.255',             # Yahoo (Yahoo! Slurp)
            '66.228.160.0 - 66.228.191.255',            # Yahoo (Yahoo! Slurp)
            '67.195.0.0 - 67.195.255.255',              # Yahoo (Yahoo! Slurp)
            '68.142.192.0 - 68.142.255.255',            # Yahoo (Yahoo! Slurp)
            '72.30.0.0 - 72.30.255.255',                # Yahoo (Yahoo! Slurp)
            '74.6.0.0 - 74.6.255.255',                  # Yahoo (Yahoo! Slurp)
            '98.136.0.0 - 98.139.255.255',              # Yahoo (Yahoo! Slurp)
            '202.160.176.0 - 202.160.191.255',          # Yahoo (Yahoo! Slurp)
            '209.191.64.0 - 209.191.127.255',           # Yahoo (Yahoo! Slurp)

            '64.4.0.0 - 64.4.63.255',                   # MSN (msnbot)
            '65.52.0.0 - 65.55.255.255',                # MSN (msnbot)
            '131.253.21.0 - 131.253.47.255',            # MSN (msnbot)
            '157.54.0.0 - 157.60.255.255',              # MSN (msnbot)
            '207.46.0.0 - 207.46.255.255',              # MSN (msnbot)
            '207.68.128.0 - 207.68.207.255',            # MSN (msnbot)
        );
        
        // Bad Bot Honey Pot - Shouldn't be here - Log IP and Error Out
        $bbhp = array();
        if (isset($this->cache)) {
            if ( ($bbhp = $this->cache->memcache->load('bad_bot_honey_pot')) === false ) { $bbhp = array(); }
        }
        
        // Clean Out Older Entries
        foreach ($bbhp as $key => $val) {
            if ($the_time > ($val + $lifetime)) { unset($bbhp[$key]); }
        }
        
        // Check If IP Is On Whitelist
        foreach ($doNotBlock as $block) {
            @list($start, $end) = preg_split('/\s*-\s*/', $block);
            if ($start) { $start = @ip2long($start); }
            if ($end) { $end = @ip2long($end); }
            
            if (($end && ($visitor >= $start) && ($visitor <= $end)) || ($visitor == $start)) { $blocked = false; }
        }
        
        if ($blocked) {
            $bbhp[$_SERVER['REMOTE_ADDR']] = time();
            if (isset($this->cache)) { $this->cache->memcache->save($bbhp, null, array(), $lifetime); }
        }
        
        // Send an email
        #$this->_helper->ViaMe->sendEmail('technical@smallcapnetwork.com', null, ($blocked ? 'Bad Robot Eating From The Honey Pot' : 'Somebody Stopped By The Honey Pot'), '<pre>'.var_export($_SERVER, true).'</pre><br /><br /><pre>'.var_export($bbhp, true).'</pre>', null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
            
        
        return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
    }
}
