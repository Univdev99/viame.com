<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class VtestController extends ViaMe_Controller_Action
{
    public function indexAction()
    {
        /*
        $voice = new ViaMe_Vm_Im_Plivo();
        $response = $voice->make_call(array(
            array('key' => 2, 'id' => '16192465264')
        ), array(
            'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Wait length="2" %2F><Record recordSession="true" %2F><Speak language="en-GB">This is only a test.<%2FSpeak><Wait length="2" %2F><%2FResponse>',
            'answer_method' => 'GET'
        ));
        Zend_Debug::Dump($response);
        */
        
        /*
        $voice = new ViaMe_Vm_Im_Plivo();
        $response = $voice->make_call(array(
            array('key' => 2, 'id' => '16192465264'),
            array('key' => 2, 'id' => '16195990595'),
            array('key' => 2, 'id' => '18583128922'),
            array('key' => 2, 'id' => '16195736639'),
            array('key' => 2, 'id' => '16192465264'),
            array('key' => 2, 'id' => '16195990595'),
            array('key' => 2, 'id' => '18583128922'),
            array('key' => 2, 'id' => '16195736639'),
        ), array(
            'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak loop="2">This is only a test.<%2FSpeak><%2FResponse>',
            'answer_method' => 'GET'
        ));
        Zend_Debug::Dump($response);
        */
        
        /*
        $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
        $SMTP_Validator->debug = true;
        $results = $SMTP_Validator->validate(array('beavislovesapplepie@appixie.com'), $this->community->email);
        Zend_Debug::Dump($results);
        */
        
        /*
        $input = '<p>�Now is the time to say hello�s to everyone � including � your mom.�  �hi� is the way  1 - 2 - 3 � 4 �</p>';
        echo $input;
        echo iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $input);
        echo iconv('CP1252', 'ASCII//TRANSLIT//IGNORE', $input);
        */
        
        /*
        $sms = new ViaMe_Vm_Im_Plivo();
        $response = $sms->send_message(array(array('key' => 3, 'id' => '16198502807')), 'Test');
        Zend_Debug::Dump($response);
        */
        
        /*
        require_once dirname(__FILE__) . '/../../../library/Other/plivo/plivo.php';
        $plivo = new RestAPI('MANJVLODI0MTQYMGU4OD', 'MjZiMzJhM2RkNDBlM2IyZDg1Y2M3NmVlMTVhNWU2');
        
        #$plivo = new ViaMe_Vm_Im_Plivo();
        #$plivo->getApi()->send_message . . . 
        
        $response = $plivo->send_message(array(
            'src' => '16193831768',
            'dst' => '16198502807',
            'text' => 'Test : ' . time()
        ));
        
        Zend_Debug::Dump($response);
        */
        
        /*
        $voice = new ViaMe_Vm_Im_Plivo();
        $response = $voice->make_call(array(array('key' => 2, 'id' => '16192465264'), array('key' => 2, 'id' => '16195990595')), array(
            'answer_url' => 'http://www.viame.com/zfbp/xml/?xml=<Response><Speak loop="2">This is only a test.<%2FSpeak><%2FResponse>',
            'answer_method' => 'GET'
        ));
        Zend_Debug::Dump($response);
        */
        
        /*
        $quotes = new ViaMe_Vm_Quotes();
        $results = $quotes->fetchRT(array('AAPL', 'BRFH', 'K', 'CSCO', 'IBM', 'AMZN', 'TSLA', 'QDUFFF', 'K'));
        Zend_Debug::Dump($results);
        */
        
        /*
        $secret = 'OtUOZmToefk39Dal';
        $login = '5gL8rD5L';
        $trans = '2180991198';
        $amount = '29.99';
        
        echo "$secret$login$trans$amount";
        echo '<br /><br />';
        echo md5("$secret$trans$amount");
        echo '<br /><br />';
        echo md5("$secret$login$trans$amount");
        */
        
        /*
        Zend_Session::namespaceUnset('SLISession');
        echo '<p><strong>OpenID:</strong> <a href="/member/openid/login/?provider=yahoo">Yahoo</a> - <a href="/member/openid/login/?provider=google">Google</a></p>';
        echo '<p><strong>OAuth2:</strong> <a href="/member/oauth/login/?provider=google-oauth2">Google</a> - <a href="/member/oauth/login/?provider=facebook-oauth2">Facebook</a> - <a href="/member/oauth/login/?provider=linkedin-oauth2">LinkedIn</a> - <a href="/member/oauth/login/?provider=twitter-oauth1">Twitter</a></p>';
        */
    
        /*
        $sms = new ViaMe_Vm_Im_Plivo();
        
        $users = array(
            '16192465264',
            array('key' => 573, 'id' => '16192465264'),
            array('16192465264', 574),
            array('16192465264', 575)
        );
        
        $response = $sms->send_message($users, 'test message2');
        #$response = $sms->send_message($users, 'test message', array('log' => 'false'));
        Zend_Debug::Dump($response);
        */
        
        /*
        require_once $this->vars->APP_PATH . "/library/Other/plivo/plivo.php";
    
        $auth_id = "MANJVLODI0MTQYMGU4OD";
        $auth_token = "MjZiMzJhM2RkNDBlM2IyZDg1Y2M3NmVlMTVhNWU2";
    
        $p = new RestAPI($auth_id, $auth_token);
        
        for ($i = 0; $i < 5; $i++) {
            // Send a message
            $params = array(
                'src' => '13212096309',
                'dst' => '16192465264',
                'text' => "Hi, Message from Arturo #$i"
            );
            $response = $p->send_message($params);
            
            Zend_Debug::Dump($response);
        }
        */
    
        return $this->_helper->viewRenderer->setNoRender();
        
        return $this->render();
        
        /*
        $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
        // turn on debugging if you want to view the SMTP transaction
        $SMTP_Validator->debug = false;
        // do the validation
        $email = 'webmaster@levelogic.com';
        $results = $SMTP_Validator->validate(array($email), 'customerservice@smallcapnetwork.com');
        // view results
        echo $email.' is '.($results[$email][0] ? 'valid' : '<strong>invalid</strong>')."\n";
        
        // send email?
        if ($results[$email][0]) {
          //mail($email, 'Confirm Email', 'Please reply to this email to confirm', 'From:'.$sender."\r\n"); // send email
        } else {
          echo 'The email addresses you entered is not valid' . (isset($results[$email][1]) && $results[$email][1] ? $results[$email][1] : '');
        }
        */
        
        return $this->_helper->viewRenderer->setNoRender();
        
        // AUTHORIZE.NET TEST TRANSACTIONS
        require_once $this->vars->APP_PATH . "/library/Other/Array2XML.php";
        require_once $this->vars->APP_PATH . "/library/Other/XML2Array.php";
        
        $php_array = array(
            #'refId' => 'Reference ID',
            'subscription' => array(
                'name' => 'Test Subscription Name',
                'paymentSchedule' => array(
                    'interval' => array(
                        'length' => '1',
                        'unit' => 'months', // days, months
                    ),
                    'startDate' => '2013-12-15', // Mountain Time
                    'totalOccurrences' => '9999',
                    #'trialOccurrences' => '1'
                ),
                'amount' => '9.99',
                #'trialAmount' => '',
                'payment' => array(
                    'creditCard' => array(
                        'cardNumber' => '4007000000027',
                        'expirationDate' => '2014-12',
                        'cardCode' => '123'
                    ),
                    #'bankAccount' => array(
                    #    'accountType' => '', // checking, savings, business checking
                    #    'routingNumber' => '',
                    #    'accountNumber' => '',
                    #    'nameOnAccount' => '',
                    #    'echeckType' => '', //PPD, TEL, WEB, CCD
                    #    'bankName' => ''
                    #)
                ),
                'order' => array(
                    'invoiceNumber' => date('YmdHis') . '-' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT),
                    'description' => 'Description',
                ),
                'customer' => array(
                    'id' => 'Customer ID',
                    'email' => 'email@email.com',
                    'phoneNumber' => '619-555-1212',
                    #'faxNumber' => ''
                ),
                'billTo' => array(
                    'firstName' => 'Art',
                    'lastName' => 'Kang',
                    #'company' => '',
                    'address' => '101 Main St.',
                    'city' => 'San Diego',
                    'state' => 'CA',
                    'zip' => '92101',
                    'country' => 'US'
                ),
                #'shipTo' => array(
                #    'firstName' => '',
                #    'lastName' => '',
                #    'company' => '',
                #    'address' => '',
                #    'city' => '',
                #    'state' => '',
                #    'zip' => '',
                #    'country' => ''
                #)
            )
        );
        
        $php_array = array(
            #'refId' => 'Reference ID',
            'transactionRequest' => array(
                'transactionType' => 'authCaptureTransaction', //authOnlyTransaction, authCaptureTransaction, captureOnlyTransaction, refundTransaction, priorAuthCaptureTransaction, voidTransaction
                'amount' => '29.99',
                'payment' => array(
                    'creditCard' => array(
                        'cardNumber' => '4007000000027',
                        'expirationDate' => '12-2015',
                        'cardCode' => '123'
                    ),
                    #'bankAccount' => array(
                    #    'routingNumber' => '',
                    #    'accountNumber' => '',
                    #    'nameOnAccount' => '',
                    #    'echeckType' => '', //PPD, TEL, WEB, CCD
                    #    'bankName' => ''
                    #)
                ),
                #'authCode' => '',
                #'refTransId' => '',
                #'splitTenderId' => '',
                'order' => array(
                    'invoiceNumber' => date('YmdHis') . '-' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT),
                    'description' => 'Description of Product'
                ),
                #'lineItems' => array(
                #    'lineItem' => array(
                #        'itemId' => '',
                #        'name' => '',
                #        'description' => '',
                #        'quantity' => '',
                #        'unitPrice' => ''
                #    ),
                #    'lineItem' => array(
                #        'itemId' => '',
                #        'name' => '',
                #        'description' => '',
                #        'quantity' => '',
                #        'unitPrice' => ''
                #    )
                #),
                #'tax' => array(
                #    'amount' => '',
                #    'name' => '',
                #    'description' => ''
                #),
                #'duty' => array(
                #    'amount' => '',
                #    'name' => '',
                #    'description' => ''
                #),
                #'taxExempt' => '', // true, false
                #'poNumber' => '',
                'customer' => array(
                    #'type' => '', // Individual, Business
                    'id' => 'Customer ID',
                    'email' => 'akang@levelogic.com'
                ),
                'billTo' => array(
                    'firstName' => 'Art',
                    'lastName' => 'Kang',
                    'company' => 'Levelogic, Inc.',
                    'address' => '101 Main St.',
                    'city' => 'San Diego',
                    'state' => 'CA',
                    'zip' => '92101',
                    'country' => 'US',
                    'phoneNumber' => '619-555-1212',
                    'faxNumber' => '619-555-2121'
                ),
                #'shipTo' => array(
                #    'firstName' => '',
                #    'lastName' => '',
                #    'company' => '',
                #    'address' => '',
                #    'city' => '',
                #    'state' => '',
                #    'zip' => '',
                #    'country' => ''
                #),
                'customerIP' => '127.0.0.1',
            )
        );
        

        #$xml = Array2XML::createXML('ARBCreateSubscriptionRequest', $php_array);
        #Zend_Debug::Dump($xml->saveXML());

        $payment = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST')); // LIVE Is Production Environment Only
        $payment->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
        #$result = $payment->transact('ARBCreateSubscriptionRequest', $php_array);
        $result = $payment->transact('createTransactionRequest', $php_array);
        Zend_Debug::Dump($result);
        
        return $this->_helper->viewRenderer->setNoRender();
        
        // Other Community Credentials For Different Merchant Account
        #$payment->setOptions(array(
        #    'user'              => '',
        #    'password'          => '',
        #    'signature'         => '',
        #    'subject'           => '',
        #    'softdescriptor'    => ''
        #));
        #$trxn_result = $payment->transact($trxn);
        #if ($trxn_result['status']) {
        #    # Log $trxn_result['raw']
        #    echo 'STATUS: pass';
        #}
        #Zend_Debug::Dump($trxn_result);
        
        
        /*
        // PAYFLOW TEST TRANSACTIONS
        $trxn = array(
            'TRXTYPE' => 'S',
            
            'EMAIL' => 'akang@levelogic.com',
            'FIRSTNAME' => 'Arturo',
            'MIDDLENAME' => 'Marshallo',
            'LASTNAME' => 'Kango',
            'STREET' => 'P.O. Box 101',
            'STREET2' => '',
            'CITY' => 'San Diego',
            'STATE' => 'CA',
            'COUNTRYCODE' => 'US',
            'ZIP' => '92101',
            'PHONENUM' => '6195551212',
            
            'TENDER' => 'C',
            #'CREDITCARDTYPE' => 'Visa', // Visa, MasterCard, Amex, Discover, Diners Club, JCB
            'ACCT' => '4012888888881881',
            'EXPDATE' => '1115',
            'CVV2' => '123',
            
            'AMT' => '5.99',
            'CURRENCYCODE' => 'USD'
        );
        
        $trxn = array(
            'TRXTYPE' => 'R',
            'ACTION' => 'A',
            
            'PROFILENAME' => 'Profile Name',
            
            'EMAIL' => 'akang@levelogic.com',
            'FIRSTNAME' => 'Arturo',
            'MIDDLENAME' => 'Marshallo',
            'LASTNAME' => 'Kango',
            'STREET' => 'P.O. Box 101',
            'STREET2' => '',
            'CITY' => 'San Diego',
            'STATE' => 'CA',
            'COUNTRY' => 'US',
            'ZIP' => '92101',
            'PHONENUM' => '6195551212',
            
            'TENDER' => 'C',
            #'CREDITCARDTYPE' => 'Visa', // Visa, MasterCard, Amex, Discover, Diners Club, JCB
            'ACCT' => '4012888888881881',
            'EXPDATE' => '1115',
            'CVV2' => '123',
            
            'CURRENCYCODE' => 'USD',
            
            'OPTIONALTRX' => 'S',
            'OPTIONALTRXAMT' => '9.99',
            'FAILEDINITAMTACTION' => 'CancelOnFailure', // ContinueOnFailure, CancelOnFailure
            'FAILEDOPTIONALTRXACTION' => 'CancelOnFailure',
            
            
            'PROFILEREFERENCE' => 'Merchants Reference To This Profile',
            'DESC' => 'This is the description of what we are subscribing to.',
            'MAXFAILPAYMENTS' => '1',
            #'RETRYNUMDAYS' => '4',
            'AUTOBILLOUTAMT' => 'NoAutoBill', // NoAutoBill, AddToNextBilling
            'AUTOBILLOUTSTANDINGAMT' => 'NoAutoBill',
            
            'START' => '12252012',
            'PAYPERIOD' => 'MONT', // WEEK, BIWK, SMMO, FRWK, MONT, QTER, SMYR, YEAR
            'BILLINGFREQUENCY' => '1',
            'TERM' => '0',
            'AMT' => '29.95',
            
            'TRIALSTART' => '12222012',
            'TRIALPAYPERIOD' => 'WEEK',
            'TRIALBILLINGFREQUENCY' => '1',
            'TRIALTERM' => '1',
            'TRIALAMT' => '4.99'
        );
        
        $payment = new ViaMe_Vm_Payment_PayFlow(($this->config->debug == 0 ? 'LIVE' : 'TEST')); // LIVE Is Production Environment Only
        $payment->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
        // Other Community Credentials For Different Merchant Account
        #$payment->setOptions(array(
        #    'user'              => '',
        #    'password'          => '',
        #    'signature'         => '',
        #    'subject'           => '',
        #    'softdescriptor'    => ''
        #));
        $trxn_result = $payment->transact($trxn);
        if ($trxn_result['status']) {
            # Log $trxn_result['raw']
            echo 'STATUS: pass';
        }
        Zend_Debug::Dump($trxn_result);
        */
        
        
        /*
        // PayPal Payments NVP Example
        $trxn = array(
            'METHOD' => 'CreateRecurringPaymentsProfile',
            
            'SUBSCRIBERNAME' => 'Arturo Kango',
            'EMAIL' => 'akang@levelogic.com',
            #'PAYERID' => '',
            #'PAYERSTATUS' => '',
            #'BUSINESS' => '',
            #'SALUTATION' => '',
            'FIRSTNAME' => 'Arturo',
            'MIDDLENAME' => 'Marshallo',
            'LASTNAME' => 'Kango',
            #'SUFFIX' => '',
            'STREET' => 'P.O. Box 101',
            'STREET2' => '',
            'CITY' => 'San Diego',
            'STATE' => 'CA',
            'COUNTRYCODE' => 'US',
            'ZIP' => '92101',
            'PHONENUM' => '6195551212',
            
            'TOKEN' => '',
            'CREDITCARDTYPE' => 'Visa', // Visa, MasterCard, Amex, Discover, Diners Club, JCB
            'ACCT' => '4788009522701425',
            'EXPDATE' => '112015',
            'CVV2' => '123',
            
            #'SHIPTONAME' => '',
            #'SHIPTOSTREET' => '',
            #'SHIPTOSTREET2' => '',
            #'SHIPTOCITY' => '',
            #'SHIPTOSTATE' => '',
            #'SHIPTOZIP' => '',
            #'SHIPTOCOUNTRY' => '',
            #'SHIPTOPHONENUM' => '',
            
            'PROFILESTARTDATE' => gmdate('c'),
            'PROFILEREFERENCE' => 'Merchants Reference To This Profile',
            'DESC' => 'This is the description of what we are subscribing to.',
            'MAXFAILEDPAYMENTS' => '1',
            'AUTOBILLOUTAMT' => 'NoAutoBill', // NoAutoBill, AddToNextBilling
            
            'CURRENCYCODE' => 'USD',
            
            'BILLINGPERIOD' => 'Month', // Day, Week, SemiMonth, Month, Year
            'BILLINGFREQUENCY' => '1',
            'TOTALBILLINGCYCLES' => '',
            'AMT' => '29.95',
            
            'TRIALBILLINGPERIOD' => 'Week',
            'TRIALBILLINGFREQUENCY' => '1',
            'TRIALTOTALBILLINGCYCLES' => '1',
            'TRIALAMT' => '5.00',
            
            'SHIPPINGAMT' => '',
            'TAXAMT' => '',
            'INITAMT' => '9.99',
            'FAILEDINITAMTACTION' => 'CancelOnFailure', // ContinueOnFailure, CancelOnFailure
            
            'L_PAYMENTREQUEST_n_ITEMCATEGORY0' => 'Digital',
            'L_PAYMENTREQUEST_n_NAME0' => 'Item Name',
            'L_PAYMENTREQUEST_n_DESC0' => 'Item Description',
            'L_PAYMENTREQUEST_n_AMT0' => '29.95',
            'L_PAYMENTREQUEST_n_NUMBER0' => '12333',
            'L_PAYMENTREQUEST_n_QTY0' => '1',
            'L_PAYMENTREQUEST_n_TAXAMT0' => ''
        );
        
        $payment = new ViaMe_Vm_Payment_PayPal(($this->config->debug == 0 ? 'LIVE' : 'TEST')); // LIVE Is Production Environment Only
        $payment->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
        // Other Community Credentials For Different Merchant Account
        #$payment->setOptions(array(
        #    'user'              => '',
        #    'password'          => '',
        #    'signature'         => '',
        #    'subject'           => '',
        #    'softdescriptor'    => ''
        #));
        $trxn_result = $payment->transact($trxn);
        if ($trxn_result['status']) {
            # Log $trxn_result['raw']
            echo 'STATUS: pass';
        }
        Zend_Debug::Dump($trxn_result);
        */
        
        
        return $this->_helper->viewRenderer->setNoRender();
        
        
        /*
        $ch = curl_init('https://api-3t.sandbox.paypal.com/nvp/');
        curl_setopt($ch, CURLOPT_VERBOSE, true);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($ch, CURLOPT_USERPWD, "username:password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($trxn)); 
        
        $ch_raw = curl_exec($ch);
        parse_str($ch_raw, $result);
        
        Zend_Debug::Dump($ch_raw);
        Zend_Debug::Dump($result);
        */
        
        /* PayPal SOAP Example
        require_once 'services/PayPalAPIInterfaceService/PayPalAPIInterfaceServiceService.php';
        require_once 'PPLoggingManager.php';
        
        $logger = new PPLoggingManager('CreateRecurringPaymentsProfile');

        $currencyCode = "USD";
        
        $address = new AddressType();
        $address->Name = 'Arturo Kango';
        $address->Street1 = 'P.O. Box 101';
        $address->Street2 = '';
        $address->CityName = 'San Diego';
        $address->StateOrProvince = 'CA';
        $address->PostalCode = '92101';
        $address->Country = 'US';
        $address->Phone = '6195551212';
        
        $personName = new PersonNameType();
        $personName->Salutation = 'Mr.';
        $personName->FirstName = 'Arturo';
        $personName->MiddleName = 'Marshallo';
        $personName->LastName = 'Kango';
        $personName->Suffix = 'Jr.';
        
        $payer = new PayerInfoType();
        $payer->PayerName = $personName;
        $payer->Payer = 'akang@levelogic.com';
        $payer->Address = $address;
        $payer->PayerCountry = 'US';

        $creditCard = new CreditCardDetailsType();
        $creditCard->CardOwner = $payer;
        $creditCard->CreditCardType = 'Visa'; // Visa, MasterCard, Discover, Amex
        $creditCard->CreditCardNumber = '4788009522701425';
        $creditCard->CVV2 = '123';
        $creditCard->ExpMonth = '11';
        $creditCard->ExpYear = '2017';
        
        $RPProfileDetails = new RecurringPaymentsProfileDetailsType();
        $RPProfileDetails->SubscriberName = 'Arthur Kang';
        $RPProfileDetails->BillingStartDate = '2012-12-05T03:00:00';
        $RPProfileDetails->SubscriberShippingAddress  = $address;
        $RPProfileDetails->ProfileReference = 'Merchants Reference To This Profile';
        
        $activationDetails = new ActivationDetailsType();
        $activationDetails->InitialAmount = new BasicAmountType($currencyCode, '1.00');
        $activationDetails->FailedInitialAmountAction = 'CancelOnFailure'; // ContinueOnFailure, CancelOnFailure
        
        $paymentBillingPeriod =  new BillingPeriodDetailsType();
        $paymentBillingPeriod->BillingFrequency = '1';
        $paymentBillingPeriod->BillingPeriod = 'Day'; // Day, Week, SemiMonth, Month, Year
        $paymentBillingPeriod->TotalBillingCycles = '0';
        $paymentBillingPeriod->Amount = new BasicAmountType($currencyCode, '2.00');
        #$paymentBillingPeriod->ShippingAmount = new BasicAmountType($currencyCode, '1.23');
        #$paymentBillingPeriod->TaxAmount = new BasicAmountType($currencyCode, '1.23');
        
        $trialBillingPeriod =  new BillingPeriodDetailsType();
        $trialBillingPeriod->BillingFrequency = '1';
        $trialBillingPeriod->BillingPeriod = 'Day';
        $trialBillingPeriod->TotalBillingCycles = '1';
        $trialBillingPeriod->Amount = new BasicAmountType($currencyCode, '3.00');
        #$trialBillingPeriod->ShippingAmount = new BasicAmountType($currencyCode, '3.21');
        #$trialBillingPeriod->TaxAmount = new BasicAmountType($currencyCode, '3.21');
        
        $scheduleDetails = new ScheduleDetailsType();
        $scheduleDetails->Description = 'This is the description of what we are subscribing to.';
        $scheduleDetails->ActivationDetails = $activationDetails;
        $scheduleDetails->TrialPeriod  = $trialBillingPeriod;
        $scheduleDetails->PaymentPeriod = $paymentBillingPeriod;
        $scheduleDetails->MaxFailedPayments =  '1';
        $scheduleDetails->AutoBillOutstandingAmount = 'NoAutoBill'; // NoAutoBill, AddToNextBilling
        
        $createRPProfileRequestDetail = new CreateRecurringPaymentsProfileRequestDetailsType();
        $createRPProfileRequestDetail->CreditCard = $creditCard;
        //$createRPProfileRequestDetail->Token  = $_REQUEST['token'];
        $createRPProfileRequestDetail->ScheduleDetails = $scheduleDetails;
        $createRPProfileRequestDetail->RecurringPaymentsProfileDetails = $RPProfileDetails;
        $createRPProfileRequestDetail->SoftDescriptor = 'TEST*SOFT*DESC';
        
        $createRPProfileRequest = new CreateRecurringPaymentsProfileRequestType();
        $createRPProfileRequest->CreateRecurringPaymentsProfileRequestDetails = $createRPProfileRequestDetail;
        
        
        $createRPProfileReq =  new CreateRecurringPaymentsProfileReq();
        $createRPProfileReq->CreateRecurringPaymentsProfileRequest = $createRPProfileRequest;
        
        $paypalService = new PayPalAPIInterfaceServiceService();
        try {
        	$createRPProfileResponse = $paypalService->CreateRecurringPaymentsProfile($createRPProfileReq, 'akang_1352503873_biz_api1.levelogic.com');
        } catch (Exception $ex) {
            $ex_message = "";
            $ex_detailed_message = "";
            $ex_type = "Unknown";
            
            if(isset($ex)) {
            
            	$ex_message = $ex->getMessage();
            	$ex_type = get_class($ex);
            
            	if($ex instanceof PPConnectionException) {
            		$ex_detailed_message = "Error connecting to " . $ex->getUrl();
            	} else if($ex instanceof PPMissingCredentialException || $ex instanceof PPInvalidCredentialException) {
            		$ex_detailed_message = $ex->errorMessage();
            	} else if($ex instanceof PPConfigurationException) {
            		$ex_detailed_message = "Invalid configuration. Please check your configuration file";
            	}
            }
            echo "$ex_message - $ex_detailed_message - $ex_type";
            
            Zend_Debug::Dump($ex);
        }
        
        if(isset($createRPProfileResponse)) {
        	echo "<table>";
        	echo "<tr><td>Ack :</td><td><div id='Ack'>$createRPProfileResponse->Ack</div> </td></tr>";
        	echo "<tr><td>ProfileID :</td><td><div id='ProfileID'>".$createRPProfileResponse->CreateRecurringPaymentsProfileResponseDetails->ProfileID ."</div> </td></tr>";
        	echo "</table>";
        
        	echo "<pre>";
        	print_r($createRPProfileResponse);
        	echo "</pre>";
        	Zend_Debug::Dump($createRPProfileResponse);
        }
        #echo "Request:<br /><br />" . htmlspecialchars($paypalService->getLastRequest());
        #echo "Response:<br /><br />" . htmlspecialchars($paypalService->getLastResponse());
        */
        
        /* FirstData Global Gateway e4 v12 Example
        $trxn = array(
            'gateway_id'            => 'AD6903-05',
            'password'              => 'a1djh4y5',
            'transaction_type'      => '00',
            'amount'                => '21.34',
            'cardholder_name'       => 'John Follis',
            'credit_card_type'      => 'Visa',
            'cc_number'             => '4111111111111111', // Numeric Only
            'cc_expiry'             => '1113', // CCYY - Numeric Only
            'zip_code'              => '92101', // Numeric Only
            
            'cc_verification_str1'  => '111 Main St.|92101|San Diego|CA|USA', // Street Address|Zip/Postal|City|State/Prov|Country
            'cc_verification_str2'  => '111', // CVV2 or CVD Code on Back of Card
            'cvd_presence_ind'      => '1', // 0=Not Supported (Default), 1=Value Provided by Cardholder, 2= Value Provided on Card Is Illegible, 9=Cardholder States Data Is Not Available
            
            'client_ip'             => $_SERVER['REMOTE_ADDR'],
            'client_email'          => 'akang@levelogic.com',
            
            'reference_no'          => 'Merchant Reference No', // 20 Characters Max
            'customer_ref'          => 'Merchant Customer Ref', // 20 Characters Max
            'reference_3'           => 'Reference 3', // 20 Characters Max
            
            'ecommerce_flag'        => '2',
            
            'soft_descriptor'       => array(
                'dba_name'                  => 'sd dba name',
                'street'                    => 'sd street',
                'city'                      => 'sd city',
                'region'                    => 'sd region',
                'postal_code'               => 'sd postal code',
                'country_code'              => 'sd country code',
                'mid'                       => 'sd mid',
                'mcc'                       => 'sd mcc',
                'merchant_contact_info'     => 'sd merchant contact info'
            )
        );
        
        $content = json_encode($trxn);
        $content_sha1 = sha1($content);
        
        $datetime = gmdate('Y-m-d\TH:i:s\Z');
        $v12_id = '6269';
        $v12_key = 'uZbmxURw~nSsfRhmHnfbIfYTudJKEehX';
        
        $ch = curl_init('https://api.demo.globalgatewaye4.firstdata.com/transaction/v12/');
        #$ch = curl_init('http://requestb.in/yayo04ya');
        //curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        //curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        //curl_setopt($ch, CURLOPT_USERPWD, "username:password");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);   
        curl_setopt($ch, CURLOPT_POST, true); 
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "authorization: GGE4_API $v12_id:" . base64_encode(hash_hmac('sha1',
                'POST' . "\n" . 'application/json' . "\n" . $content_sha1 . "\n" . $datetime . "\n" . '/transaction/v12/',
                    $v12_key, true)),
            "x-gge4-date: $datetime",
            'x-gge4-content-sha1: ' . $content_sha1,
            'Content-Type: application/json',
            'Accept: application/json',
            'Content-Length: ' . strlen($content)
        ));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $content); 
        
        $result = curl_exec($ch);
        
        Zend_Debug::Dump(json_decode($result));
        */
        
        /*
        $data = array(
            'vmoti' => '19356',
            'vmote' => 'akang+scn_etelegis@levelogic.com',
            'vmotpw' => '85*%liDPj$24&',
            'mid' => '1',
            'vmpd_npr' => '1',
            'vmpd_nar' => '1',
            'allow_comments' => '1',
            'allow_ratings' => '1',
            'submit' => 'Publish Article',
            
            'title' => 'This is the title',
            'content' => '<p>HTML Content Goes Here</p>',
            'symbols' => 'CSCO,YHOO,AMZN,AAPL',
            
            'heading' => '',
            'summary' => ''
        );
        
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://www.smallcapnetwork.com/via/18950/article/create/");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $output = curl_exec($ch);
        curl_close($ch);
        */
          
        #echo $this->renderScript('../../../member/views/scripts/register/registersuccess/smallcapnetwork.phtml');
        
        #echo $this->VMAH->simpleDecrypt('hWXiyL7Z4rLWjqVjmoGhh4U=');
        
        #select profile_id,count(nullif(module_id != 15, 't'::bool)) as articles, count(nullif(module_id != 22, 't'::bool)) as blogs, count(*) as total from module_template where (module_id=15 or module_id=22) and active group by profile_id order by count(*) desc;
        
        /*
        $select = $this->db->select()
            ->from(array('obj' => 'module_template'), array(
                'profile_id' => 'profile_id',
                'total_articles' => new Zend_Db_Expr("count(nullif(obj.module_id != 15, 't'::bool))"),
                'total_blogs' => new Zend_Db_Expr("count(nullif(obj.module_id != 22, 't'::bool))"),
                'total_articles_and_blogs' => new Zend_Db_Expr("count(*)")
            ))
            ->where('obj.active=?', 't')
            
            ->join(array('m' => 'module_modules'), 'obj.module_id=m.id',
                array(
                )
            )
            ->where('m.active=?', 't')
            ->where('m.id=15 OR m.id=22')
            
            ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                array(
                    'p_id' => 'id',
                    'p_name' => 'name'
                )
            )
            ->where('p.active=?', 't')
            
            ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                array(
                'b_email' => 'email'
                )
            )
            ->where('b.active=?', 't')
            
            ->join(array('c' => 'system_communities'), 'p.community_id = c.id',
                array(
                )
            )
            ->where('c.active=?', 't')
            
            ->group(array('obj.profile_id', 'p.id', 'p.name', 'b.email'))
            ->order('count(*) desc')
            ->limit(100)
            ;
            
        $this->view->items = $this->db->fetchAll($select);
        
        echo '<table cellpadding=3 cellspacing=3 border=1><tr><th style="padding: 3px; border: 1px solid #000;">Name</th><th style="padding: 3px; border: 1px solid #000;">Email</th><th style="padding: 3px; border: 1px solid #000;">Total Articles</th><th style="padding: 3px; border: 1px solid #000;">Total Blogs</th><th style="padding: 3px; border: 1px solid #000;">Total Articles and Blogs</th></tr>';
        foreach ($this->view->items as $item) {
?>
    <tr>
        <td style="padding: 3px; border: 1px solid #000;"><a href="https://www.smallcapnetwork.com/via/<?= $item->p_id ?>/"><?= $item->p_name ?> (<?= $item->p_id ?>)</a></td>
        <td style="padding: 3px; border: 1px solid #000;"><a href="mailto:<?= $item->b_email ?>"><?= $item->b_email ?></a></td>
        <td style="padding: 3px; border: 1px solid #000;" align="center"><a href="https://www.smallcapnetwork.com/Article/s/via/<?= $item->p_id ?>/article/p/profile/1/"><?= $item->total_articles ?></a></td>
        <td style="padding: 3px; border: 1px solid #000;" align="center"><a href="https://www.smallcapnetwork.com/Blog/s/via/<?= $item->p_id ?>/blog/p/profile/1/"><?= $item->total_blogs ?></a></td>
        <td style="padding: 3px; border: 1px solid #000;" align="center"><?= $item->total_articles_and_blogs ?></td>
    </tr>
<?php
        }
        echo '</table>';
        */
        
        
        
        /*
        $select = $this->db->select()
            ->from(array('obj' => 'pick_picks'),
                array(
                    "com_id",
                    "net_id",
                    "via_id",
                    #"COALESCE(open_price, open_temp_price)",
                    #"COALESCE(close_price, COALESCE(close_temp_price, qd.last))",
                    #"position",
                    #"allocation",
                    "SUM(((COALESCE(close_price, COALESCE(close_temp_price, qd.last)) - COALESCE(open_price, open_temp_price)) * position) / COALESCE(open_price, open_temp_price) * allocation) AS return"
                )
            )
            ->where('obj.via_id > 0')
            ->where('open_price <> 0 AND open_temp_price <> 0')
            ->where("open_datestamp > ('now'::timestamp - '4 months'::interval)")
            
            ->join(array('qd' => 'quote_data'), 'obj.symbol_id=qd.symbol_id',
                array(
                    #'qd_last' => 'last'
                )
            )
            ->where('qd.active=?', 't')
            ->group(array('obj.com_id', 'obj.net_id', 'obj.via_id'))
            ->order('return DESC')
        ;
        
        echo $select;
        
        $select = $this->db->select()
            ->from(array('obj' => 'pick_picks'),
                array(
                    "com_id",
                    "net_id",
                    "via_id",
                    #"COALESCE(open_price, open_temp_price)",
                    #"COALESCE(close_price, COALESCE(close_temp_price, qd.last))",
                    #"position",
                    #"allocation",
                    "(((COALESCE(close_price, COALESCE(close_temp_price, qd.last)) - COALESCE(open_price, open_temp_price)) * position) / COALESCE(open_price, open_temp_price) * allocation) AS return"
                )
            )
            ->where('obj.via_id > 0')
            ->where('open_price <> 0 AND open_temp_price <> 0')
            ->where("open_datestamp > ('now'::timestamp - '4 months'::interval)")
            
            ->join(array('qd' => 'quote_data'), 'obj.symbol_id=qd.symbol_id',
                array(
                    #'qd_last' => 'last'
                )
            )
            ->where('qd.active=?', 't')
            #->group(array('obj.com_id', 'obj.net_id', 'obj.via_id'))
            ->order('return DESC')
        ;
        
        echo "<br /><br />" . $select;
        */
        
        
        #sendEmail ($toEmail='', $toName='', $subject='', $contentHTML='', $contentTEXT='', $fromEmail='', $fromName='')
        #$this->VMAH->sendEmail ('polkjuiop@gmail.com', 'Whoever You Are', 'This is only a test', 'Test HTML Body', 'Test Text Body', 'newsletter@smallcapnetwork.com', 'SmallCap Network');
        
        /*
        $form_config = array(
            'attribs' => array(
                'name' => 'member2_form',
                'method' => 'post',
                'id' => 'member2_form',
                'class' => 'form'
            ),
            'elements' => array(
                'dob' => array('Text', array(
                    'label' => 'Date of Birth',
                    #'description' => '(YYYY-MM-DD)',
                    'class' => 'vmfh_date',
                    'validators' => array(
                        array('Date')
                    )
                )),
                'dob2' => array('Text', array(
                    'label' => 'Date of Birth2',
                    'class' => 'vmfh_date vmfh_datetime',
                    'validators' => array(
                        array('Date')
                    ),
                    'value' => '05/17/1971 01:23:45'
                )),
            )
        );
        
        $a = new Zend_Validate_Date();
        $format = preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'));
        echo "* $format *";
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions($form_config);
        $form->addElement('Submit', 'submit', array('label' => 'Create Account', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'ignore' => true, 'order' => 1000, 'onClick' => "return (confirm('Are you sure you want to cancel registration?'));"));
        
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            echo 'ALL GOOD! ';
            
            $date = new Zend_Date();
            $date->set($this->_getParam('dob'));
            echo $date->get(Zend_Date::ISO_8601);
        } else {
            $date = new Zend_Date();
            #$date->set('1971-05-17');
            #$form->getElement('dob')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short'))));
            echo 'FAIL! ';
        }
        
        $this->view->FormHelper(array('internal' => $this->internal));
        echo $form;
        */

        #call_user_func_array('printf', array_merge((array)'%.2f %.2f %.2f', array(1.2, 2.3, 3.4))); 
        #$this->VMAH->log('log message', Zend_Log::EMERG, array('test1', 'test2', 'test3'));
        
        
        /* RESET 15
        $id = 15;
        if ($this->_getParam('reset')) {
            #$this->db->query("DELETE FROM module_matrix WHERE module_id > 0 AND via_id=$id");
            #$this->db->query("DELETE FROM widget_matrix WHERE via_id=$id");
            #$this->db->query("UPDATE profile_profiles SET page_layout=NULL, page_sublayout=NULL, grid_hd='{}', grid_ft='{}', grid_cx='{}', grid_c1='{}', grid_c2='{}', grid_c3='{}', grid_c4='{}' WHERE id=$id");
        }
        
        echo '<br /><br />';
        echo "<a href=\"/via/$id/profile/setup/p/vmpd_nar/1/\">Setup $id</a> - <a href=\"?reset=1\">Reset $id</a>";
        echo '<br /><br />';
        #echo Zend_Locale_Data::getContent($this->internal->locale, 'dateitem', 'yMEd');
        #echo Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'));
        $date = new Zend_Date();
        echo $date->toString(Zend_Locale_Data::getContent($this->internal->locale, 'dateitem', 'yMEd'));
        echo '<br /><br />';
        
        
        
        #$this->_helper->ViaMe->sendEmail('arthur@levelogic.com', 'Arturo Kango', 'Please Verify Your Member Account!', '<p>Howdy there!</p>', null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        
        echo $this->view->CM(array(
            'class' => 'cm decorated plain successmessage',
            'hd' => 'Verification Successful',
            'hd2' => 'Thank you for verifying your account',
            'bd' => '<p class="success">You have successfully verified your account and it is now active.</p><p><a href="' . $this->internal->target->pre . '/member/login/verify/vmpd_nar/1/">Continue &raquo;</a></p>'
        ));
        #return $this->_helper->viewRenderer->setNoRender();
        */
        
        /*
        try {
            $feeder = new Zend_Feed();
            $test = $feeder->import('http://feeds.feedburner.com/techcrunch');
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }
        */
        
        /*
        $multioptions = array();
        $temp = null;
        foreach ($this->db->fetchAll("SELECT currency, code FROM system_currencies WHERE active='t' ORDER BY orderby, currency") as $temp) {
            $multioptions[$temp->code] = $temp->currency;
        }

        // Translation
        $currency = new ViaMe_Currency
        $multioptions_nt = $multioptions;
        foreach ($multioptions as $key => $val) {
            $key = strtoupper($key);
            if (isset($this->vars->language) && !preg_match('/^en/', $this->vars->language)) {
                try {
                    $output = $this->locale->getCurrencyTranslation($key, $this->vars->language);
                    if (is_string($output) && $val != $output) {
                        $multioptions[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
            elseif (array_key_exists($key, $country_to_language) && $country_to_language[$key] !== false) {
                try {
                    $output = $this->locale->getCountryTranslation($key, strtolower($country_to_language[$key]));
                    if (is_string($output) && $val != $output) {
                        $multioptions[$key] = "$val ($output)";
                    }
                } catch (Exception $e) {}
            }
        }
        */
        
        /*
        $currencies = $this->db->fetchAll("SELECT * FROM system_currencies WHERE active='t' AND code like 'K%' LIMIT 10");
        foreach ($currencies as $currency) {
            $currency_object = new ViaMe_Currency($currency->code);
            echo $currency->code . ' : ';
            echo $currency_object->getName() . ' - ' . $currency_object->getSymbol() . ' - ' . $currency_object->getShortName();
            echo "<br />\n";
        }
        */
        #Zend_Debug::Dump($results);
        
        
        #$quotes = new ViaMe_Vm_Quotes();
        #Zend_Debug::Dump($this->VMAH->iPgArray($quotes->verify(array('CSCO', 'YHOO', 'ZMH.QT'))));
        #Zend_Debug::Dump($quotes->lookupById(array(243, 234)));
        
        /*
        $currency = new ViaMe_Currency('en_US');
        #echo $currency->getShortName();
        #echo $currency->getName();
        #Zend_Debug::Dump( $currency->getRegionList() );
        #Zend_Debug::Dump($currency->getCurrencyList());
        set_time_limit(0);
        $arr = array_keys($currency->getCurrencyList());
        #echo count($arr);
        for ( $i = 0; $i < 165; $i++ ) {
            echo $currency->getShortName($arr[$i], 'en_US') . ' - ' . $currency->getName($arr[$i], 'en_US') . "<br />\n";
            #echo "$key ";
        }
        #echo $currency->getShortName('EUR', 'en_US');
        */
        
        /*
        if (Zend_Registry::isRegistered('Zend_Navigation')) {
            $navigation = Zend_Registry::get('Zend_Navigation');
            $navigation->addPage(array(
                'label' => 'Home',
                'uri' => '/',
                'active' => true,
                'rel' => array(
                    'alternate' => array(
                        'label' => 'label',
                        #'type' => 'application/rss+xml',
                        'uri' => 'https://www.smallcapnetwork.com/rss.php'
                    ),
                    'start' => 'start',
                    'next' => 'next',
                    'prev' => 'prev',
                    'contents' => 'chapter'
                )
            ));
        }
        */

        #$internal = new StdClass;
        #$internal->vars = $this->vars;
        #$internal->config = $this->config;
        #$internal->community = $this->community;
        
        #$partial_array = array('email' => 'arthur@levelogic.com', 'name' => 'Art the Man Kang', 'id' => 123456, 'key' => 'keykeykeykey', 'internal' => $internal);
        
        #$this->_helper->ViaMe->sendEmail('arthur@levelogic.com', 'Art the Man Kang', 'Please Verify Your ' . $this->community->display . ' Member Account!', $this->view->partial('register/emails/verify-html.phtml', 'member', $partial_array), null, null, $this->community->display);
        
        #return $this->_helper->viewRenderer->renderBySpec('registration-success', array('module' => 'member', 'controller' => 'register')); 
        #$this->renderScript('../../../member/views/scripts/register/registration-success.phtml');
        
        #if ($this->getRequest()->isPost()) { print 'POSTED'; exit; }
        #if ($this->_getParam('REDIRECT_POST')) { print 'Redirected Post Method<BR>'; }
        #print "*".$this->_getParam('name')."*";
        /*
        return;
        
        $quotes = new ViaMe_Vm_Quotes();

        Zend_Debug::Dump($quotes->fetch(array('YHOO', 'CSCO', 'IBM', 'asdjfjf', 'YHOO', 'SPKL.ob', 'spng.ob')));
        $test = 'sl1d1t1c1ohgvsk3sb6sa5sf6s';
        Zend_Debug::Dump(preg_split('/\s+/', preg_replace(array('/(\d+)/', '/([^\d\s])/'), array('${1} ', ' ${1}'), $test), -1, PREG_SPLIT_NO_EMPTY));
        Zend_Debug::Dump($quotes->lookup('china en'));
        
        return;
        
        Zend_Debug::Dump($this->_getAllParams());
        
        $form = new Zend_Form(array(
            'method' => 'get',
            'elements' => array(
                'user' => array('Text', array(
                    'label' => 'User'
                )),
                'test' => array('Select', array(
                    'label' => 'Select Test',
                    'MultiOptions' => array(
                        'a' => 'b',
                        'c' => 'd'
                    )
                )),
                'tos' => array('Textarea', array(
                    'label' => 'Terms of Service',
                    'value' => 'this is the terms of service.'
                )),
                'submit' => 'submit'
            )
        ));
        #$form->removeDecorator('HtmlTag');
        */
        #$form->setElementDecorators(array('ViewHelper', 'Errors', array('Label'), 'HtmlTag'));
        #$form->setElementDecorators(array('ViewHelper', array('Label'), 'HtmlTag'));
        
        #$form->addDisplayGroup(array('email', 'password', 'persistent'), 'login_fs', array('legend' => 'Login Information'));
        #$form->addDisplayGroup(array('submit'), 'submit_fs', array('legend' => 'Submit'));
        #$form->setDisplayGroupDecorators(array('FormElements', 'FieldSet'));
        
        // Individual Element Overrides
        #$form->getElement('email')->setLabel($form->getElement('email')->getLabel() . ':')->setAttrib('style', 'width: 98%;');
        #$form->getElement('password')->setLabel($form->getElement('password')->getLabel() . ':')->setAttrib('style', 'width: 98%;');
        #$form->getElement('persistent')->setLabel('Keep me signed in for 2 weeks.')->setDecorators(array('ViewHelper', array('Label', array('placement' => 'append')), array('HtmlTag', array('style' => 'font-size: xx-small;'))));
        #$form->getElement('submit')->setDecorators(array('ViewHelper', array('HtmlTag', array('style' => 'text-align: center;'))));
        
        
        #$form->populate($this->_getAllParams());
        #Zend_Debug::Dump($form->getValues());
        /*
        $form = new Zend_Form(array(
            'action' => '/user/test',
            'method' => 'get',
            'attribs' => array('id'=>'testid', 'class'=>'testclass'),
            'elements' => array(
                'first_name' => array('Text', array(
                    'label' => 'First Name',
                    'description' => 'Your first name',
                    'required' => true,
                    'filters'  => array(
                        'StringTrim'
                    )
                )),
                'username' => array('text', array(
                    'validators' => array(
                        'alnum',
                        array('regex', false, array('/^[a-z]/i')),
                        array('stringLength', false, array(6, 20))
                    ),
                    'required' => true,
                    'filters'  => array('StringToLower')
                )),
                'submit' => 'submit'
            )
        ));
        $form->setDecorators(array(array(
            'decorator' => 'ViewScript',
            'options' => array('viewScript' => 'vtest/test1.phtml')
        )));
        */
        
        /*
        $form = new Zend_Form(array(
            'action'   => '/user/login',
            'method'   => 'post',
            'attribs' => array('id'=>'testid', 'class'=>'testclass'),
            'elements' => array(
                'username' => array('text', array(
                    'validators' => array(
                        'alnum',
                        array('regex', false, array('/^[a-z]/i')),
                        array('stringLength', false, array(6, 20))
                    ),
                    'required' => true,
                    'filters'  => array('StringToLower')
                )),
                'password' => array('password', array(
                    'validators' => array(
                        array('stringLength', false, array(6))
                    ),
                    'required' => true,
                )),
                'submit' => 'submit',
            ),
        ));
        $options = array('R' => 'red', 'G' => 'green', 'B' => 'blue');
        $colors = new Zend_Form_Element_Radio('colors');
        $colors->setRequired(true)
         ->setValue('R')
         ->setOptions(array('separator' => ''))
         ->setMultiOptions($options );
        $form->addElement($colors);
        */
        
        /*
        #$form->clearElements();
        #$this->view->form = $form;
        #$this->render('test1.phtml');
        #$this->render('partials/test2.phtml');
        
        #setlocale(LC_ALL, 'es_MX');
        #$l = 'es_MX';
        $locale = new Zend_Locale();
        #Zend_Locale::setDefault('es_MX');
        $locale->setLocale('es_MX');
        
        #$locale->setLocale($l); // and setDefault
        #print $locale;
        
        #print $locale->getLanguage();
        #print $locale->getRegion();
        #print $locale->getTranslation('de', 'language', 'ko');
        
        $date = new Zend_Date($locale);
        print $date;
        
        $locale = new Zend_Locale();
        // Return all default locales
        $found = $locale->getDefault();
        print_r($found);
        
        // Return only browser locales
        $found2 = $locale->getDefault(Zend_Locale::FRAMEWORK,TRUE);
        print_r($found2);
        */
    }
}
