<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 *
 * XML Generator - Like a TwiML Replacement
 */

class Zfbp_XmlController extends ViaMe_Controller_Action
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
        
        // Load up the registry variables
        $this->registryLoader($this);
        
        $this->_helper->layout->disableLayout();
        $this->_helper->viewRenderer->setNoRender();
        $this->getResponse()->clearBody();
        $this->getResponse()->clearHeaders();
    }
    
    public function preDispatch() { }
    
    public function indexAction()
    {
        if ($this->_getParam('debug')) {
            // Send an email
            $this->_helper->ViaMe->sendEmail('akang@levelogic.com', null, 'XML Debug', '<pre>'.Zend_Debug::Dump($this->_getAllParams(), 'Params', false).'</pre>', null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        }
        
        // Office Closed
        if(
            (preg_match('/\_voice$/', $this->_getParam('xmlid')))
            && 
            (date('N') > 5 || date('G') < 9 || date('G') > 16)
        ) {
            $this->_setParam('xmlid', 'default_closed');
        }
        
        // Default Status
        if ($this->_getParam('xmlid') == 'default_callstatus') {
            if ($this->_getParam('CallStatus') == 'completed') {
                $this->_setParam('xmlid', 'default_hangup');
            }
            else {
                $this->_setParam('xmlid', 'default_voicemail');
            }
        }
        
        // Notify of Voicemail
        if ($this->_getParam('RecordUrl')) {
            $sms = new ViaMe_Vm_Im_Plivo();
            
            $response = $sms->getApi()->send_message(array(
                'src' => $this->_getParam('To'),
                'dst' => '16192465264',
                'text' => 'New Voicemail (' . $this->_getParam('From') . ' to ' . $this->_getParam('To') . ') : ' . $this->_getParam('RecordUrl')
            ));
            
            #$users = array(
            #    array('key' => 2, 'id' => '16192465264')
            #);
            #$response = $sms->send_message($users, 'New Voicemail (' . $this->_getParam('From') . ' to ' . $this->_getParam('To') . ') : ' . $this->_getParam('RecordUrl'));
        }
        
        $xml = null;
        $xml_array = array(
            'default_hangup' => '<Response><Hangup /></Response>',
            'default_message' => '<Response><Message src="{To}" dst="{From}">This is an automated reply.  For assistance, please contact our customer service department either by phone or email.  Thank you.</Message><Message src="{To}" dst="16192465264">Incoming SMS From {From} to {To}: {Text}</Message></Response>',
            'default_closed' => '<Response><Speak language="en-GB">Our office is currently closed.  Please leave your name, number and a detailed message after the beep, and someone will return your call as soon as possible.</Speak><Record action="https://www.viame.com/zfbp/xml/?xmlid=default_hangup" method="GET" redirect="true" maxLength="60" finishOnKey="*" /></Response>',
            'default_voicemail' => '<Response><Speak language="en-GB">All representatives are currently busy assisting other customers.  Please leave your name, number and a detailed message after the beep, and someone will return your call as soon as possible.</Speak><Record action="https://www.viame.com/zfbp/xml/?xmlid=default_hangup" method="GET" redirect="true" maxLength="60" finishOnKey="*" /></Response>',
            
            // Anything ending in _voice will be sent to voicemail during non-business hours
            'scn_voice' => '<Response><Speak language="en-GB">Thank you for calling SmallCap Network.  Your call is being transferred to a customer service representative.  Please wait.</Speak><Dial confirmSound="https://www.viame.com/zfbp/xml/?xml=&lt;Response&gt;&lt;Speak&gt;Press 5 to accept a SmallCap Network call.&lt;%2FSpeak&gt;&lt;%2FResponse&gt;" confirmKey="5" callerId="{To}" callerName="SmallCap Network" action="https://www.viame.com/zfbp/xml/?xmlid=default_callstatus" method="GET" timeout="25" redirect="true"><Number>16192465264</Number></Dial></Response>',
            'scnps_voice' => '<Response><Speak language="en-GB">Thank you for calling SmallCap Network, Premium Subscription Services.  Your call is being transferred to a customer service representative.  Please wait.</Speak><Dial confirmSound="https://www.viame.com/zfbp/xml/?xml=&lt;Response&gt;&lt;Speak&gt;Press 5 to accept a SmallCap Network, Premium Subscription Services call.&lt;%2FSpeak&gt;&lt;%2FResponse&gt;" confirmKey="5" callerId="{To}" callerName="SmallCap Network" action="https://www.viame.com/zfbp/xml/?xmlid=default_callstatus" method="GET" timeout="25" redirect="true"><Number>16192465264</Number></Dial></Response>',
            'eo_voice' => '<Response><Speak language="en-GB">Thank you for calling Elite Opportunity.  Your call is being transferred to a customer service representative.  Please wait.</Speak><Dial confirmSound="https://www.viame.com/zfbp/xml/?xml=&lt;Response&gt;&lt;Speak&gt;Press 5 to accept an Elite Opportunity call.&lt;%2FSpeak&gt;&lt;%2FResponse&gt;" confirmKey="5" callerId="{To}" callerName="Elite Opportunity" action="https://www.viame.com/zfbp/xml/?xmlid=default_callstatus" method="GET" timeout="25" redirect="true"><Number>16192465264</Number></Dial></Response>',
            'vc_voice' => '<Response><Speak language="en-GB">Thank you for calling Viking Crest.  Your call is being transferred to a customer service representative.  Please wait.</Speak><Dial confirmSound="https://www.viame.com/zfbp/xml/?xml=&lt;Response&gt;&lt;Speak&gt;Press 5 to accept a Viking Crest call.&lt;%2FSpeak&gt;&lt;%2FResponse&gt;" confirmKey="5" callerId="{To}" callerName="Viking Crest" action="https://www.viame.com/zfbp/xml/?xmlid=default_callstatus" method="GET" timeout="25" redirect="true"><Number>16192465264</Number></Dial></Response>'
        );
        if ($this->_getParam('xmlid') && isset($xml_array[$this->_getParam('xmlid')])) { $xml = $xml_array[$this->_getParam('xmlid')]; }
        else { $xml = $this->_getParam('xml'); }
        
        if ($xml) {
            $xml = preg_replace_callback('/\{.*?\}/', function($match) { $match = preg_replace('/\{(.*?)\}/', '$1', $match); return $this->_getParam($match[0]); }, $xml);
            
            $this->getResponse()->setHeader('Content-Type', 'text/xml; charset=utf-8');
            echo '<?xml version="1.0" encoding="utf-8"?>';
            echo $xml;
        }
        else {
            #return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
        }
    }
}