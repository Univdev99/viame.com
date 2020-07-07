<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

class ViaMe_Controller_Router_Route_Regex extends Zend_Controller_Router_Route_Regex
{
    protected $_wildcardData = array();
    
    
    public function match($path)
    {
        $path = trim(urldecode($path), '/');
        $res = preg_match($this->_regex, $path, $values);

        if ($res === 0 || preg_last_error()) {
            // Let try to strip seo and try again - Could be backtrack limit error
            // Could also set backtrack limit higher
            #$old = ini_set('pcre.backtrack_limit', '200000');
            
            $path = preg_replace('#^.*?/s/#', '', $path);
            $res = preg_match($this->_regex, $path, $values);
            if ($res === 0 || preg_last_error()) {
                return false;
            }
        }

        // array_filter_key()? Why isn't this in a standard PHP function set yet? :)
        foreach ($values as $i => $value) {
            if (!is_int($i) || $i === 0) {
                unset($values[$i]);
            }
        }

        $this->_values = $values;

        $values = $this->_getMappedValues($values);
        $defaults = $this->_getMappedValues($this->_defaults, false, true);

        // ViaMe Fixups
        #Zend_Debug::Dump($this->_values);
        #Zend_Debug::Dump($values);
        if ($values['route_type']) {
            $values['route_type'] = strtolower($values['route_type']);
            
            foreach (array('module', 'controller', 'action') as $which) {
                if ($values[$which]) {
                    $values["sub$which"] = $values[$which];
                }
            }
            
            $values['module'] = 'default';
            if ($values['route_type'] != 'com') {
                $values['controller'] = $values['route_type'];
            }
            else {
                $values['controller'] = 'index';
            }
            $values['action'] = 'index';
            
            $values[$values['route_type'] . '_id'] = $values['route_id'];
            
            unset($values['route_type'], $values['route_id']);
        }
        else {
            $dispatcher = Zend_Controller_Front::getInstance()->getDispatcher();
            if ($dispatcher && !$dispatcher->isValidModule($values['module'])) {
                $values['action'] = $values['controller'];
                $values['controller'] = $values['module'];
                unset($values['module']);
            }
        }
        
        foreach (array('module', 'controller', 'action') as $which) {
            if (isset($values[$which]) && !$values[$which]) {
                unset($values[$which]);
            }
        }
            
        if (isset($values['PARAMS'])) {
            $parts = explode('/', $values['PARAMS']);
            
            for($i = 0; $i < count($parts); $i += 2) {
                $var = urldecode($parts[$i]);
                if ($var && !isset($this->_wildcardData[$var]) && !isset($this->_defaults[$var]) && !isset($values[$var])) {
                    $this->_wildcardData[$var] = (isset($parts[$i+1]) ? urldecode($parts[$i+1]) : null);
                }
            }
            
            unset($values['PARAMS']);                
        }
        
        foreach (array('module', 'controller', 'action', 'route_type', 'route_id', 'PARAMS') as $which) {
            if (isset($values[$which]) && !$values[$which]) {
                #unset($values[$which]);
            }
        }
        
        $return = $values + $this->_wildcardData + $defaults;
        
        #Zend_Debug::Dump($return);
        
        return $return;
    }
    
    protected function _getMappedValues($values, $reversed = false, $preserve = false)
    {
        if (count($this->_map) == 0) {
            return $values;
        }

        $return = array();

        foreach ($values as $key => $value) {
            if (is_int($key) && !$reversed) {
                if (array_key_exists($key, $this->_map)) {
                    $index = $this->_map[$key];
                } elseif (false === ($index = array_search($key, $this->_map))) {
                    $index = $key;
                }
                if (!isset($return[$index]) || !$return[$index]) {
                    $return[$index] = $values[$key];
                }
            } elseif ($reversed) {
                $index = (!is_int($key)) ? array_search($key, $this->_map, true) : $key;
                if (false !== $index) {
                    if (!isset($return[$index]) || !$return[$index]) {
                        $return[$index] = $values[$key];
                    }
                }
            } elseif ($preserve) {
                $return[$key] = $value;
            }
        }

        return $return;
    }
}



/*
# RouterRegexp Tests
$matches = array();
$regexp = '#
    
    (?:
        (?:
            ([^/]*)/s(?:\W?)
        )?
        (?:
            (com|net|via)/([^/]*)(?:/?)
        )?
        
        (?:
            (?:([^/]+) (?:/?))
            (?:
                (?:([^/]+) (?:/?))
                (?:
                    (?:([^/]+) (?:/?))
                )?
            )?
        )?
        
        \Wp/
        
        |
        
        (?:
            ([^/]*)/s(?:/?)
        )?
        (?:
            (com|net|via)/([^/]*)(?:/?)
        )?
        
        (?:
            (?:([^/]+) (?:/?))
            (?:
                (?:([^/]+) (?:/?))
                (?:
                    (?:([^/]+) (?:/?))
                )?
            )?
        )?
    )

    (.*)
    
#ix';
preg_match($regexp, 'blah/s/via/2/pmodpulep/pconptrollerp/pactpionp/a/b/c/d', $matches);

Zend_Debug::Dump($matches);




//  ROUTE : member/:membername/:module/:controller/:action/*
#$frontController->getRouter()->addRoute(
#  'default1',
#  new Zend_Controller_Router_Route(
#    ':module/:controller/:action/*',
#    array('module' => 'default')
#  )
#);

// Routes are matched in reverse order so make sure your most generic routes are defined first.
$frontController->getRouter()->addRoute(
  'default1',
  new Zend_Controller_Router_Route(
    ':module/:controller/:action/p/*')
);
$frontController->getRouter()->addRoute(
  'default2',
  new Zend_Controller_Router_Route(
    ':module/:controller/p/*')
);
$frontController->getRouter()->addRoute(
  'default3',
  new Zend_Controller_Router_Route(
    ':module/p/*')
);
$frontController->getRouter()->addRoute(
  'default4',
  new Zend_Controller_Router_Route(
    'p/*')
);

$frontController->getRouter()->addRoute(
  'com',
  new Zend_Controller_Router_Route(
    'com/:community/:submodule/:subcontroller/:subaction/*',
    array('module' => 'default',
          'controller' => 'index',
          'action' => 'index',
          'submodule' => '',
          'subcontroller' => '',
          'subaction' => '')
  )
);
$frontController->getRouter()->addRoute(
  'com1',
  new Zend_Controller_Router_Route(
    'com/:community/:submodule/:subcontroller/:subaction/p/*',
    array('module' => 'default',
          'controller' => 'index',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'com2',
  new Zend_Controller_Router_Route(
    'com/:community/:submodule/:subcontroller/p/*',
    array('module' => 'default',
          'controller' => 'index',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'com3',
  new Zend_Controller_Router_Route(
    'com/:community/:submodule/p/*',
    array('module' => 'default',
          'controller' => 'index',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'com4',
  new Zend_Controller_Router_Route(
    'com/:community/p/*',
    array('module' => 'default',
          'controller' => 'index',
          'action' => 'index')
  )
);

$frontController->getRouter()->addRoute(
  'net',
  new Zend_Controller_Router_Route(
    'net/:network/:submodule/:subcontroller/:subaction/*',
    array('module' => 'default',
          'controller' => 'net',
          'action' => 'index',
          'submodule' => '',
          'subcontroller' => '',
          'subaction' => '')
  )
);
$frontController->getRouter()->addRoute(
  'net1',
  new Zend_Controller_Router_Route(
    'net/:network/:submodule/:subcontroller/:subaction/p/*',
    array('module' => 'default',
          'controller' => 'net',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'net2',
  new Zend_Controller_Router_Route(
    'net/:network/:submodule/:subcontroller/p/*',
    array('module' => 'default',
          'controller' => 'net',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'net3',
  new Zend_Controller_Router_Route(
    'net/:network/:submodule/p/*',
    array('module' => 'default',
          'controller' => 'net',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'net4',
  new Zend_Controller_Router_Route(
    'net/:network/p/*',
    array('module' => 'default',
          'controller' => 'net',
          'action' => 'index')
  )
);

$frontController->getRouter()->addRoute(
  'via',
  new Zend_Controller_Router_Route(
    'via/:via/:submodule/:subcontroller/:subaction/*',
    array('module' => 'default',
          'controller' => 'via',
          'action' => 'index',
          'submodule' => '',
          'subcontroller' => '',
          'subaction' => '')
  )
);
$frontController->getRouter()->addRoute(
  'via1',
  new Zend_Controller_Router_Route(
    'via/:via/:submodule/:subcontroller/:subaction/p/*',
    array('module' => 'default',
          'controller' => 'via',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'via2',
  new Zend_Controller_Router_Route(
    'via/:via/:submodule/:subcontroller/p/*',
    array('module' => 'default',
          'controller' => 'via',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'via3',
  new Zend_Controller_Router_Route(
    'via/:via/:submodule/p/*',
    array('module' => 'default',
          'controller' => 'via',
          'action' => 'index')
  )
);
$frontController->getRouter()->addRoute(
  'via4',
  new Zend_Controller_Router_Route(
    'via/:via/p/*',
    array('module' => 'default',
          'controller' => 'via',
          'action' => 'index')
  )
);
*/