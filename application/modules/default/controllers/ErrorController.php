<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class ErrorController extends ViaMe_Controller_Action
{
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_READ;
    //protected $_mustBeOwner = false;
    
    
    const HTTP_ERROR_BAD_REQUEST                = 400;
    const HTTP_ERROR_UNAUTHORIZED               = 401;
    const HTTP_ERROR_PAYMENT_REQUIRED           = 402;
    const HTTP_ERROR_FORBIDDEN                  = 403;
    const HTTP_ERROR_NOT_FOUND                  = 404;
    const HTTP_ERROR_METHOD_NOT_ALLOWED         = 405;
    const HTTP_ERROR_NOT_ACCEPTABLE             = 406;
    const HTTP_ERROR_PROXY_AUTH_REQUIRED        = 407;
    const HTTP_ERROR_REQUEST_TIMEOUT            = 408;
    const HTTP_ERROR_CONFLICT                   = 409;
    const HTTP_ERROR_GONE                       = 410;
    const HTTP_ERROR_LENGTH_REQUIRED            = 411;
    const HTTP_ERROR_PRECONDITION_FAILED        = 412;
    const HTTP_ERROR_REQUEST_ENTITY_TOO_LARGE   = 413;
    const HTTP_ERROR_REQUEST_URI_TOO_LONG       = 414;
    const HTTP_ERROR_UNSUPPORTED_MEDIA_TYPE     = 415;
    
    const HTTP_ERROR_INTERNAL_SERVER_ERROR      = 500;
    const HTTP_ERROR_NOT_IMPLEMENTED            = 501;
    const HTTP_ERROR_BAD_GATEWAY                = 502;
    const HTTP_ERROR_SERVICE_UNAVAILABLE        = 503;
    const HTTP_ERROR_GATEWAY_TIMEOUT            = 504;
    const HTTP_ERROR_HTTP_VERSION_NOT_SUPPORTED = 505;
    
    
    public function errorAction()
    {
        // Just logged out users
        if ($this->_getParam('vmpd_jlo')) { $this->_redirect('/'); }
        
        
        // Error Code Parameter or Set Default
        $error_code = (int) Zend_Filter::filterStatic($this->_getParam('errorcode'), 'Digits');
        if (!isset($error_code) || !is_int($error_code) || !($error_code > 0)) {
            // Set Default to Not Found
            $error_code = self::HTTP_ERROR_NOT_FOUND;
        }
        $this->view->exceptions = array();
                    
        // Framework Thrown Exceptions
        $errors = $this->_getParam('error_handler');
        if ($errors) {            
            switch ($errors->type) {
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_CONTROLLER:
                    $error_code = self::HTTP_ERROR_NOT_FOUND   ;
                    $this->view->exceptions[] = 'The requested controller was not found';
                    break;
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_NO_ACTION:
                    $error_code = self::HTTP_ERROR_NOT_FOUND   ;
                    $this->view->exceptions[] = 'The requested action was not found';
                    break;
                case Zend_Controller_Plugin_ErrorHandler::EXCEPTION_OTHER:
                    $error_code = self::HTTP_ERROR_INTERNAL_SERVER_ERROR;
                    $this->view->exceptions[] = 'Other framework exception was thrown';
                    break;
                default:
            }
            
            // Should be for debug or admin only
            if ($this->config->debug >= 5 || (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin))) {
                foreach($this->getResponse()->getException() as $e) {
                    $this->view->exceptions[] = $e->getMessage();
                }
            }
        }
        
        // Server Errors
        switch ($error_code) {
            case self::HTTP_ERROR_BAD_REQUEST:
                $this->view->title = 'Bad Request';
                $this->view->message = 'There was a syntax error in your request.';
                break;
            case self::HTTP_ERROR_UNAUTHORIZED:
                $this->view->title = 'Authorization Required';
                $this->view->message = 'You are not authorized to access the requested object.';
                break;
            case self::HTTP_ERROR_PAYMENT_REQUIRED:
                $this->view->title = 'Payment Required';
                $this->view->message = 'Payment is required to access the requested object.';
                break;
            case self::HTTP_ERROR_FORBIDDEN:
                $this->view->title = 'Access Forbidden';
                $this->view->message = 'You do not have permission to access the requested object.';
                break;
            case self::HTTP_ERROR_NOT_FOUND:
                $this->view->title = 'Object Not Found';
                $this->view->message = "The requested object was not found.";
                break;
            case self::HTTP_ERROR_METHOD_NOT_ALLOWED:
                $this->view->title = 'Method Not Allowed!';
                $this->view->message = 'The supplied method is not allowed for the requested object.';
                break;
            case self::HTTP_ERROR_NOT_ACCEPTABLE:
                $this->view->title = 'No Acceptable Object Found';
                $this->view->message = 'An appropriate representation of the requested object could not be found.';
                break;
            case self::HTTP_ERROR_PROXY_AUTH_REQUIRED:
                $this->view->title = 'Proxy Authentication Required';
                $this->view->message = 'The proxy server needs to authorize this request.';
                break;
            case self::HTTP_ERROR_REQUEST_TIMEOUT:
                $this->view->title = 'Request Time-Out';
                $this->view->message = 'Disconnecting due to request time-out.';
                break;
            case self::HTTP_ERROR_CONFLICT:
                $this->view->title = 'Conflict';
                $this->view->message = 'The request conflicts with another request or the server configuration.';
                break;
            case self::HTTP_ERROR_GONE:
                $this->view->title = 'Object Gone';
                $this->view->message = 'The requested object is no longer available.';
                break;
            case self::HTTP_ERROR_LENGTH_REQUIRED:
                $this->view->title = 'Bad Content-Length';
                $this->view->message = 'The request requires a valid Content-Length header.';
                break;
            case self::HTTP_ERROR_PRECONDITION_FAILED:
                $this->view->title = 'Precondition Failed';
                $this->view->message = 'The precondition on the requested object failed positive evaluation.';
                break;
            case self::HTTP_ERROR_REQUEST_ENTITY_TOO_LARGE:
                $this->view->title = 'Request Entity Too Large';
                $this->view->message = 'The request entity body is too large.';
                break;
            case self::HTTP_ERROR_REQUEST_URI_TOO_LONG:
                $this->view->title = 'URI Too Long';
                $this->view->message = 'The requested URI is too long.';
                break;
            case self::HTTP_ERROR_UNSUPPORTED_MEDIA_TYPE:
                $this->view->title = 'Unsupported Media Type';
                $this->view->message = 'The requested media type is not supported.';
                break;
            
            case self::HTTP_ERROR_INTERNAL_SERVER_ERROR:
                $this->view->title = 'Internal Server Error';
                $this->view->message = 'The server encountered an internal error.';
                break;
            case self::HTTP_ERROR_NOT_IMPLEMENTED:
                $this->view->title = 'Action Not Implemented';
                $this->view->message = 'The requested action is not supported.';
                break;
            case self::HTTP_ERROR_SERVICE_UNAVAILABLE:
                $this->view->title = 'Service Unavailable';
                $this->view->message = 'The server is temporarily unable to service your request.';
                break;
            case self::HTTP_ERROR_GATEWAY_TIMEOUT:
                $this->view->title = 'Gateway Time-Out';
                $this->view->message = 'A gateway or proxy has timed-out.';
                break;
            case self::HTTP_ERROR_HTTP_VERSION_NOT_SUPPORTED:
                $this->view->title = 'HTTP Version Not Supported';
                $this->view->message = 'The requested HTTP version is not supported.';
                break;
            
            default:
                $error_code = self::HTTP_ERROR_INTERNAL_SERVER_ERROR; // Set Default
                $this->view->title = 'Server Error';
                $this->view->message = 'An unexpected server error occured.';
        }
        
        // Overrides from parameters
        if ($this->_getParam('title')) { $this->view->title = $this->_getParam('title'); }
        if ($this->_getParam('message')) { $this->view->message = $this->_getParam('message'); }
        
        // Set View Variables        
        $this->view->error_code = $error_code;
        $this->view->admin_name = $this->config->admin->name;
        $this->view->admin_email = $this->config->admin->email;
        if (isset($_SERVER['REQUEST_URI'])) {
            $this->view->request = $_SERVER['REQUEST_URI'];
            $this->view->exceptions[] = 'Request: ' . $_SERVER['REQUEST_URI'];
        }                
        if (isset($_SERVER['HTTP_REFERER'])) {
            $this->view->referrer = $_SERVER['HTTP_REFERER'];
            $this->view->exceptions[] = 'Referrer: ' . $_SERVER['HTTP_REFERER'];

        }
        
        // Log Info
        if ($error_code >= 500) {
            $this->log->EMERG("ERROR $error_code : " . $this->view->message . ' - ' . (implode(' - ', $this->view->exceptions)));
        }
        #else {
        #    $this->log->EMERG("ERROR $error_code : " . $this->view->message . ' - ' . (implode(' - ', $this->view->exceptions)));
        #}
        
        
        // Check to see if there are alternate access ACLs and redirect if there is
        if ($error_code == 401) {
            $dp = $this->_getParam('denyParams');
            
            if ($dp['type'] && $dp['id'] && $dp['priv']) {
                if (!($dp['mod'] > 0)) { $dp['mod'] = 0; }
                if (!($dp['mid'] > 0)) { $dp['mid'] = 0; }
                if (!($dp['iid'] > 0)) { $dp['iid'] = 0; }
                
                $query = "SELECT 't'::boolean FROM acl_acls WHERE (";
                    $query .= '(module_id=0 AND matrix_counter=0 AND item_counter=0' . ($dp['mod'] ? " AND recursive='t'" : '') . ')';
                    if ($dp['mod'] && $dp['mid']) {
                        $query .= ' OR (' . $this->db->quoteInto('module_id=?', $dp['mod']) . ' AND ' . $this->db->quoteInto('matrix_counter=?', $dp['mid']) . ($dp['iid'] ? " AND recursive='t'" : ''). ')';
                    }
                    if ($dp['mod'] && $dp['mid'] && $dp['iid']) {
                        $query .= ' OR (' . $this->db->quoteInto('module_id=?', $dp['mod']) . ' AND ' . $this->db->quoteInto('matrix_counter=?', $dp['mid']) . ' AND ' . $this->db->quoteInto('item_counter=?', $dp['iid']) . ')';
                    }
                    $query .= ') AND ';
                    $query .= $this->db->quoteInto("privilege >= ?", $dp['priv']);
                    $query .= " AND (expiration ISNULL OR expiration >= 'now') AND active='t' AND ";
                    switch($dp['type']) {
                        case 'VIA':
                            $query .= $this->db->quoteInto('via_id=?', $dp['id']);
                            break;
                        case 'NET':
                            $query .= $this->db->quoteInto('net_id=?', $dp['id']);
                            break;
                        default:
                            $query .= $this->db->quoteInto('com_id=?', $dp['id']);
                            break;
                    }
                    
                    $query2 = $query;
                    
                    $query .= ' AND ((w_member_amount NOTNULL AND w_member_quantity NOTNULL AND w_member_interval NOTNULL))';
                    $query2 .= ' AND (w_password NOTNULL OR (w_registration_status NOTNULL OR w_groups NOTNULL OR w_contact_profiles NOTNULL OR w_arb_profiles NOTNULL OR w_dos NOTNULL))';
                    
                    $query .= ' LIMIT 1';
                    $query2 .= ' LIMIT 1';
                
                if ($this->db->fetchOne($query)) {
                    if ($this->_getParam('vmpd_paid') && !(isset($this->member))) {
                        // Coming in off of a paid subscription link
                        return $this->_redirect('/member/login/' . ($this->_getParam('signup_entrance') ? 'p/signup_entrance/' . $this->_getParam('signup_entrance') . '/' : '') . '?redirect=' . urlencode($this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
                    }
                    else {
                        # Login display at the ACL
                        #if (isset($this->member)) {
                            #return $this->_forward('index', 'access', 'acl', array(
                            #    'type' => $dp['type'],
                            #    'id' => $dp['id'],
                            #    'mod' => $dp['mod'],
                            #    'mid' => $dp['mid'],
                            #    'iid' => $dp['iid'],
                            #    'priv' => $dp['priv']
                            #));
                            return $this->_redirect('http' . ($this->config->use_ssl ? 's' : '') . '://' . $this->vars->host . '/acl/access/?stype=' . $dp['type'] . '&sid=' . $dp['id'] . '&mod=' . $dp['mod'] . '&mid=' . $dp['mid'] . '&iid=' . $dp['iid'] . '&priv=' . $dp['priv'] . ($this->_getParam('signup_entrance') ? '&signup_entrance=' . urlencode($this->_getParam('signup_entrance')) : '') . '&redirect=' . urlencode($this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
                        #}
                        #else {
                        #    return $this->_forward('index', 'login', 'member', array('redirect' => $this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
                        #}
                    }
                }
                elseif (!(isset($this->member)) && $this->db->fetchOne($query2)) {
                    #return $this->_forward('index', 'login', 'member', array('redirect' => $this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
                    return $this->_redirect('/member/login/' . ($this->_getParam('signup_entrance') ? 'p/signup_entrance/' . $this->_getParam('signup_entrance') . '/' : '') . '?redirect=' . urlencode($this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
                }
            }
        }
        
        // HACK - Problem when view helper action throws error
        $this->getResponse()->clearBody(); 
        $this->view->headLink()->setStylesheet(null);
        $this->view->headScript()->setScript(null);
        $this->view->inlineScript()->setScript(null);
        
        // Remove any placeholders or flags
        #Zend_Debug::Dump(Zend_Registry::get(Zend_View_Helper_Placeholder_Registry::REGISTRY_KEY));
        #$this->view->placeholder('space_header')->set(null);
        Zend_View_Helper_Placeholder_Registry::getRegistry()->deleteContainer('space_header' );
        $this->internal->sublayout_with_header = false;
        
        // Enable Layout if it got disabled
        $layout = Zend_Layout::getMvcInstance();
        if (null !== $layout) {
            $layout->enableLayout();
            $this->_setParam('no_layout', '');
            $this->_setParam('layout', '');
            #$layout->setMvcSuccessfulActionOnly(false);
        }
        
        // If layouts manually off, turn them on??? - Right now, no because the error will display in the layout specified (if popup etc.)
        #$this->_setParam('no_layout', 0);
        
        // Reset the Content-Type
        $this->getResponse()->setHeader('Content-Type', 'text/html', true);
        
        // Change the response header
        $this->getResponse()->setHttpResponseCode((int) $error_code);
        
        // Change Layout
        $this->_helper->ViaMe->setLayout('error');
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        
        // No robots
        $this->view->headMeta()->setName('robots', 'noindex, noarchive, nofollow');
    }
    
    public function __call($method, $args)
    {
        return $this->_forward('error');
    }
}
