<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class System_FormToEmailController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_ADMIN;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = false;
    
    
    public function indexAction()
    {
        if (count($_POST)) {
            // Mail the email
            $partial_array = array('post' => $_POST, 'internal' => $this->internal);
            
            $temp = $this->view->partial('form-to-email/emails/index.phtml', $partial_array);
            
            $this->_helper->ViaMe->sendEmail(
                ($this->_getParam('recipient') && !strpos($this->_getParam('recipient'), '@') ? $this->_getParam('recipient').'@'.$this->vars->domain_name : $this->community->email),
                $this->community->display,
                ($this->_getParam('pre_spec') ? ' ['.$this->_getParam('pre_spec').'] ' : '') . $this->_getParam('subject', 'No Subject'),
                $temp,
                null,
                $this->_getParam('email'),
                $this->_getParam('realname')
            );
        }
        
        if ($this->_getParam('redirect')) {
            return $this->_redirect($this->_getParam('redirect'));
        }
        else {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain successmessage',
                'hd' => 'Thank You',
                'hd2' => 'Routing Your Request',
                'bd' => '<p class="success">Thank you for your submission.</p><p>We are attempting to route your request to the correct party.  If you are expecting a response and do not receive one within 48 hours, please try your request again.</p><p><a href="javascript:history.back();">Continue...</a>'
            ));
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}