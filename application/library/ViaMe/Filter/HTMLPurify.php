<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/


require_once 'Zend/Filter/Interface.php';

require_once dirname(__FILE__) . '/HTMLPurify/HTMLPurifier.standalone.php';
require_once dirname(__FILE__) . '/CSSTidy/class.csstidy.php';

class ViaMe_Filter_HTMLPurify implements Zend_Filter_Interface
{
    protected $_HTMLPurifier = null;

    protected $_config = null;

    
    public function __construct($options = null)
    {
        $this->_config = HTMLPurifier_Config::createDefault();
        
        #$this->_config->set('Cache.DefinitionImpl', null);
        $this->_config->set('Cache.SerializerPath', '/tmp');
        $this->_config->set('CSS.AllowTricky', true);
        $this->_config->set('Filter.ExtractStyleBlocks', true);
        $this->_config->set('HTML.SafeIframe', true);
        $this->_config->set('URI.SafeIframeRegexp', '%^(https?:)?(\/\/www\.youtube(?:-nocookie)?\.com\/embed\/|\/\/player\.vimeo\.com\/)%');
        $this->_config->set('CSS.Trusted', true);
        #$this->_config->set('Filter.YouTube', true);
        #$this->_config->set('CSS.Proprietary', true);

        if (!is_null($options)) {
            $this->setConfig($options);
        }
        
        $this->_HTMLPurifier = new HTMLPurifier($this->getConfig());
    }
    
    
    public function getConfig()
    {
        return $this->_config;
    }
    
    
    public function setConfig($options = null)
    {
        if (!is_null($options)) {
            foreach ($options as $option) {
                $this->_config->set($option[0], $option[1], $option[2]);
            }
            
            return $this->_config;
        }
    }
    
    
    public function filter($value)
    {
        return $this->_HTMLPurifier->purify($value);
    }
}
