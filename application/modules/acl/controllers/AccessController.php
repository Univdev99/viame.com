<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Acl_AccessController extends ViaMe_Controller_Action
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
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_redirect($this->target->pre . '/');
        }
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Access Controls', 'PREPEND');
        #$this->view->headTitle('Access', 'PREPEND');
                
        if ($this->_getParam('stype') && $this->_getParam('sid')) {
            // Title
            $this->view->title = '';
            switch($this->_getParam('stype')) {
                case 'VIA':
                    $this->view->title .= $this->db->fetchOne($this->db->quoteInto('SELECT name FROM profile_profiles WHERE id=?', $this->_getParam('sid'), 'INTEGER'));
                    break;
                case 'NET':
                    $this->view->title .= $this->db->fetchOne($this->db->quoteInto('SELECT name FROM network_networks WHERE id=?', $this->_getParam('sid'), 'INTEGER'));
                    break;
                default:
                    $this->view->title .= $this->db->fetchOne($this->db->quoteInto('SELECT COALESCE(display, name) FROM system_communities WHERE id=?', $this->_getParam('sid'), 'INTEGER'));
                    break;
            }
            if ($this->_getParam('mod') && $this->_getParam('mid')) {
                $this->view->title .= $this->db->fetchOne(
                    "SELECT ' : ' || COALESCE(x.display, m.display, m.name) FROM module_matrix x, module_modules m WHERE x.module_id=m.id AND " .
                    $this->db->quoteInto('x.' . strtolower($this->_getParam('stype')) . '_id=?', $this->_getParam('sid')) .
                    $this->db->quoteInto(' AND x.module_id=?', $this->_getParam('mod')) .
                    $this->db->quoteInto(' AND x.counter=?', $this->_getParam('mid'))
                );
            }
            if ($this->_getParam('iid')) {
                $this->view->title .= $this->db->fetchOne(
                    "SELECT ' : ' || title FROM module_template WHERE " .
                    $this->db->quoteInto(strtolower($this->_getParam('stype')) . '_id=?', $this->_getParam('sid')) .
                    $this->db->quoteInto(' AND module_id=?', $this->_getParam('mod')) .
                    $this->db->quoteInto(' AND matrix_counter=?', $this->_getParam('mid')) .
                    $this->db->quoteInto(' AND counter=?', $this->_getParam('iid'))
                );
            }
            $this->view->headTitle($this->view->title, 'PREPEND');
            $this->view->headTitle('Access Controls', 'PREPEND');
            #$this->view->headTitle('Access', 'PREPEND');
        
            // Params
            $this->view->pps = $this->_getAllParams();
            
            
            // Load the Form
            require_once dirname(__FILE__) . '/../../member/controllers/includes/members_form.php';
            $form->getElement('first_name')->setRequired(true)->setAttrib('data-constraints', '@Required(label="first name", message="The {label} field cannot be empty.", groups=[member_form])');
            $form->getElement('last_name')->setRequired(true)->setAttrib('data-constraints', '@Required(label="last name", message="The {label} field cannot be empty.", groups=[member_form])');
            $form->getElement('postal_code')->setRequired(true)->setAttrib('data-constraints', '@Required(label="postal code", message="The {label} field cannot be empty.", groups=[member_form])');
            $form->getElement('country')->setRequired(true)->setAttrib('data-constraints', '@Required(label="country", message="The {label} field cannot be empty.", groups=[member_form])');
            // Remove Elements Early to Reduce Iteration
            $form->removeElement('middle_name');
            $form->removeElement('gender');
            $form->removeElement('dob');
            $form->removeElement('dob_month');
            $form->removeElement('dob_day');
            $form->removeElement('dob_year');
            $form->removeElement('middle_name');
            $form->removeElement('timezone');
            $form->removeElement('currency');
            $form->removeElement('language');
            
            $form->setAttrib('onsubmit', 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));');
            $form->setAttrib('data-constraints', '@PasswordsMatch(field1="password", field2="password_confirm", message="Your passwords do not match.", groups=[member_form])');
            
            $form->setMethod('post');
            $form->setAction('/acl/access/');
            
            $this->view->openForm = '<form id="member_form" name="member_form" enctype="application/x-www-form-urlencoded" class="form regula-validation" data-constraints="@PasswordsMatch(field1=&quot;password&quot;, field2=&quot;password_confirm&quot;, message=&quot;Your passwords do not match.&quot;, groups=[member_form])" onsubmit="return YAHOO.viame.access.checkAccessForm(this);" method="post" action="/acl/access/">';
            
            $form->addElement('Checkbox', 'tos', array(
                'order' => 905,
                #'label' => 'Terms of Service',
                'required' => true,
                'description' => "I have read and agree to the <a href=\"/page/view/p/mid/1/title/Terms+of+Use/\" target=\"_blank\" onclick=\"return YAHOO.viame.shadowbox.shadowto('/page/view/p/mid/1/title/Terms+of+Use/?vmpd_rqsv=no_layout,vmpd_nsslr,vmcd_ncr&no_layout=1&vmpd_nsslr=1&vmcd_ncr=1', {width: '600px', height: '500px', fixedcenter: true, close: true, draggable: false, modal: true, visible: false});\" title=\"Terms of Service\">Terms of Service</a>, <a href=\"/page/view/p/mid/1/title/Privacy+Policy/\" target=\"_blank\" onclick=\"return YAHOO.viame.shadowbox.shadowto('/page/view/p/mid/1/title/Privacy+Policy/?vmpd_rqsv=no_layout,vmpd_nsslr,vmcd_ncr&no_layout=1&vmpd_nsslr=1&vmcd_ncr=1', {width: '600px', height: '500px', fixedcenter: true, close: true, draggable: false, modal: true, visible: false});\" title=\"Privacy Policy\">Privacy Policy</a> and Disclaimer.",
                'class' => 'regula-validation',
                'data-constraints' => '@Required(message="You must agree to the Terms of Service.", groups=[member_form])',
                'validators' => array(
                    array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'You must agree to the Terms of Service.')))
                )
            ));
            $form->getElement('tos')->getDecorator('description')->setEscape(false);
            
            $form->addElement('Checkbox', 'billing_agreement', array(
                'order' => 906,
                #'label' => 'Billing Agreement',
                'required' => true,
                'description' => "I understand and acknowledge " . $this->community->display . "'s following billing and refund policy: All pricing, automatic recurring billing, trial periods, and billing cycles are as specified on this page and based upon my selection. Recurring subscriptions will automatically renew, unless canceled by me. After the initial billing, I may discontinue at any time for any reason with the understanding that I will NOT receive a refund - prorated or otherwise; however, my subscription will remain active until the end of the paid period. " . '<p class="netdown" style="font-size: 90%;">Note: Charges will appear on your statement as <strong>' . $this->community->display . '</strong>.</strong></p>',
                'class' => 'regula-validation',
                'data-constraints' => '@Required(message="You must agree to the Billing Agreement.", groups=[member_form])',
                'validators' => array(
                    array('GreaterThan', false, array('min' => 0, 'messages' => array(Zend_Validate_GreaterThan::NOT_GREATER => 'You must agree to the Billing Agreement.')))
                )
            ));
            $form->getElement('billing_agreement')->getDecorator('description')->setEscape(false);
            
            $form->addElement('Text', 'address1', array('label' => 'Address 1', 'required' => true, 'data-constraints' => '@Required(label="address", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 11));
            $form->addElement('Text', 'address2', array('label' => 'Address 2', 'order' => 12));
            $form->addElement('Text', 'city', array('label' => 'City', 'required' => true, 'data-constraints' => '@Required(label="city", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 13));
            $form->addElement('Text', 'state', array('label' => 'State/Province', 'required' => true, 'data-constraints' => '@Required(label="state", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 14));
            $form->addElement('Text', 'phone', array('label' => 'Phone', 'required' => true, 'class' => 'vmfh_phone', 'data-constraints' => '@Required(label="phone", message="The {label} field cannot be empty.", groups=[member_form]) @Pattern(regex=/^[\d\s\(\)\-\+\.]*$/, label="phone number", message="The {label} should only contain numbers.", groups=[member_form])', 'order' => 100));$form->addElement('Select', 'cc_type', array('label' => 'Card Type', 'required' => true, 'MultiOptions' => array('' => '--- Card Type ---', 'Visa' => 'Visa', 'MasterCard' => 'MasterCard', 'Amex' => 'American Express', 'Discover' => 'Discover', 'Diners Club' => 'Diners Club', 'JCB' => 'JCB'), 'data-constraints' => '@Required(label="card type", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 101));
            $form->addElement('Text', 'cc_num', array('label' => 'Card #', 'required' => true, 'data-constraints' => '@Required(label="card number", message="The {label} field cannot be empty.", groups=[member_form]) @Pattern(regex=/[\s\d\-]/, label="card number", message="The {label} should only contain numbers.", groups=[member_form])', 'order' => 102));
            $form->addElement('Select', 'cc_exp_month', array('label' => 'Exp. Date', 'required' => true, 'MultiOptions' => array('' => '--- Month ---', '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12'), 'data-constraints' => '@Required(label="card exp month", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 103));
            $form->getElement('cc_exp_month')->addErrorMessage('Required.');
            $next_years = array('' => '--- Year ---');
            $this_year = date('Y');
            for ($i = 0; $i <= 9; $i++) {
                $next_years[(string) $this_year + $i] = (string) $this_year + $i;
            }
            $form->addElement('Select', 'cc_exp_year', array('required' => true, 'MultiOptions' => $next_years, 'data-constraints' => '@Required(label="card exp year", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 104));
            $form->getElement('cc_exp_year')->addErrorMessage('Required.');
            $form->addElement('Text', 'cc_security_code', array('label' => 'Security Code', 'required' => true, 'maxlength' => 4, 'data-constraints' => '@Required(label="security code", message="The {label} field cannot be empty.", groups=[member_form]) @Numeric(label="security code", message="The {label} can only contain numbers.", groups=[member_form])', 'order' => 105));
            
            $form->addElement('Submit', 'submit', array('label' => 'Subscribe', 'class' => 'big green', 'ignore' => true, 'order' => 999));
            $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'class' => 'big', 'ignore' => true, 'order' => 1000, 'onClick' => "if (confirm('Are you sure you want to cancel?')) { document.getElementById('member_form').vivregval_canceled = true; return true; } else { return false; }"));
            
            $form->getElement('email')->setOrder(37); // Move Email Above Username
            $form->addDisplayGroup(array('first_name', 'last_name', 'email', 'username', 'password', 'password_confirm'), 'personal', array('legend' => 'Personal Information'));
            $form->addDisplayGroup(array('tos', 'billing_agreement'), 'terms', array('legend' => 'Terms of Service & Billing Agreement'));
            $form->addDisplayGroup(array('address1', 'address2', 'city', 'state', 'postal_code', 'country', 'phone'), 'billing', array('legend' => 'Billing Information'));
            $form->addDisplayGroup(array('cc_type', 'cc_num', 'cc_exp_month', 'cc_exp_year', 'cc_security_code'), 'credit_card', array('legend' => 'Payment Information'));
            
            $form->addElement('Text', 'promo_code', array('label' => 'Promo Code', 'description' => 'If you have a promotional or discount code, enter it here.', 'order' => 110));
            $form->addElement('Hidden', 'apply_promo');
            $form->addElement('Button', 'apply_promo_button', array('label' => 'Apply Code', 'class' => 'fakebutton blue', 'style' => 'float: right;', 'ignore' => true, 'order' => 111, 'onClick' => "if ($('#promo_code').val()) { document.getElementById('member_form').vivregval_canceled = true; $('#apply_promo').val('1'); HTMLFormElement.prototype.submit.call($('#member_form')[0]); } else { alert('Please enter a promo code.'); $('#promo_code').focus(); return false; }"));
            $form->addDisplayGroup(array('promo_code', 'apply_promo_button'), 'promo', array('legend' => 'Promotional Code'));
            
            // Things that need to be done outside of the cache due to parameters
            $form->addElement('Hidden', 'stype', array('value' => $this->_getParam('stype')));
            $form->addElement('Hidden', 'sid', array('value' => $this->_getParam('sid')));
            $form->addElement('Hidden', 'mod', array('value' => $this->_getParam('mod')));
            $form->addElement('Hidden', 'mid', array('value' => $this->_getParam('mid')));
            $form->addElement('Hidden', 'iid', array('value' => $this->_getParam('iid')));
            $form->addElement('Hidden', 'priv', array('value' => $this->_getParam('priv')));
            
            $form->addElement('Hidden', 'double_access_check', array('value' => 0));
            $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
            $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
            $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
            $form->addElement('Hidden', 'signup_entrance', array('value' => $this->_getParam('signup_entrance')));
            $form->addElement('Hidden', 'click_track', array('value' => $this->_getParam('click_track')));
            $form->addElement('Hidden', 'profile_referrer_id', array('value' => $this->_getParam('profile_referrer_id')));
            
            $form->addDisplayGroup(array('stype', 'sid', 'mod', 'mid', 'iid', 'priv', 'double_access_check', 'vmpd_npr', 'vmpd_nar', 'redirect', 'signup_entrance', 'click_track', 'profile_referrer_id', 'apply_promo'), 'hidden');
            
            
            $password_confirm_validator = new Zend_Validate_Identical($this->_getParam('password'));
            $password_confirm_validator->setMessages(array(
                Zend_Validate_Identical::NOT_SAME => 'Password was not confirmed.', Zend_Validate_Identical::MISSING_TOKEN => 'Password was not confirmed.'
            ));
            $form->getElement('password_confirm')->addValidator($password_confirm_validator);
            
            if (isset($this->locale) && is_string($this->locale->getRegion()) && $this->locale->getRegion() != 'US') {
                $form->getElement('country')->setValue($this->locale->getRegion());
            }
            
            $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
            
            
            $this->view->formErrors = array();
            
            
            // Apply promo code
            if ($this->_getParam('promo_code')) {
                #Zend_Debug::Dump($this->getAllParams());
                // Get and apply any promo code
                $valid_promo_code = false;
                
                // Get a valid promo
                $query = 'SELECT * FROM acl_promos WHERE active AND (activation ISNULL OR activation <= now()) AND (expiration ISNULL OR expiration >= now()) AND ' . $this->db->quoteInto('lower(code)=lower(?)', $this->_getParam('promo_code')) . ' AND ';
                switch($this->_getParam('stype')) {
                    case 'VIA':
                        $query .= $this->db->quoteInto('via_id=?', $this->_getParam('sid'));
                        break;
                    case 'NET':
                        $query .= $this->db->quoteInto('net_id=?', $this->_getParam('sid'));
                        break;
                    default:
                        $query .= $this->db->quoteInto('com_id=?', $this->_getParam('sid'));
                        break;
                }
                $query .= ' LIMIT 1';
                #echo $query;
                
                try {
                    $this->view->promo = $this->db->fetchRow($query);
                } catch (Exception $e) { }
                
                if (isset($this->view->promo) && $this->view->promo) {
                    $valid_promo_code = true;
                    #Zend_Debug::Dump($this->view->promo);
                    
                    $form->getElement('promo_code')
                        ->setDescription(isset($this->view->promo->description) && $this->view->promo->description ? $this->view->promo->description : 'Your discount has been applied')
                        ->setDecorators( array('ViewHelper', array('Errors'), array('Description', array('tag' => 'p', 'class' => 'description', 'style' => 'margin-top: 4px; font-weight: normal;')), array('HtmlTag', array('tag' => 'dd', 'style' => 'margin-top: 4px; font-weight: bold;')), array('Label', array('tag' => 'dt'))) )
                        ->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_ISSET_HIDDEN);
                    $form->removeElement('apply_promo');
                    $form->removeElement('apply_promo_button');
                }
                
                if ($this->_getParam('apply_promo')) {
                    $form->getElement('password')->renderPassword = true;
                    $form->getElement('password_confirm')->renderPassword = true;
                    #Zend_Debug::Dump($this->getAllParams());
                    
                    if ($valid_promo_code) {
                        $this->view->formErrors[] = 'Promo code accepted and applied.';
                        
                        $switch_promo_selector = '';
                        if ($this->view->promo->com_id) { $switch_promo_selector = 'COM-' . $this->view->promo->com_id . '-'; }
                        elseif ($this->view->promo->net_id) { $switch_promo_selector = 'NET-' . $this->view->promo->net_id . '-'; }
                        elseif ($this->view->promo->via_id) { $switch_promo_selector = 'VIA-' . $this->view->promo->via_id . '-'; }
                        $switch_promo_selector .= $this->view->promo->module_id . '-' . $this->view->promo->matrix_counter . '-' . $this->view->promo->item_counter . '-' . $this->view->promo->acl_counter;
                
                        $this->_setParam('access_selection', $switch_promo_selector);
                        $this->internal->params->access_selection = $switch_promo_selector;
                        $this->view->pps['access_selection'] = $switch_promo_selector;
                    }
                    else {
                        $this->view->formErrors[] = 'Invalid promo code.  Please try again.';
                        $this->_setParam('promo_code', '');
                    }
                }
            }
            
            // Logged in member
            if (isset($this->member)) {
                $form->removeElement('username');
                $form->removeElement('password');
                $form->removeElement('password_confirm');
                $this->view->openForm = '<form id="member_form" name="member_form" enctype="application/x-www-form-urlencoded" class="form regula-validation" onsubmit="return YAHOO.viame.access.checkAccessForm(this);" method="post" action="/acl/access/">';
                $form->getElement('first_name')->setValue($this->member->first_name);
                $form->getElement('last_name')->setValue($this->member->last_name);
                
                #$form->getElement('email')->setValue($this->member->email);
	            $form->getElement('email')->setValue($this->member->email)->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN)->setDecorators( array('ViewHelper', array('Errors'), array('HtmlTag', array('tag' => 'dd', 'style' => 'font-weight: bold; margin-top: 4px; margin-bottom: 0;')), array('Label', array('tag' => 'dt'))) );
	            $form->addElement('Text', 'notmemessage', array(
	                'value' => '<a href="/member/logout/full/vmpd_far/1/?redirect=' . urlencode(isset($this->vars->new_php_self) ? $this->vars->new_php_self : $_SERVER['PHP_SELF']) . '%3F' . urlencode(isset($this->vars->new_query_string) ? $this->vars->new_query_string : $_SERVER['QUERY_STRING'] ) . '">Not my email address</a>',
	                'order' => 38,
	                'ignore' => true
	            ));
	            
	            $form->getElement('notmemessage')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
	            $form->removeDisplayGroup('personal');
	            $form->addDisplayGroup(array('first_name', 'last_name', 'email', 'notmemessage'), 'personal', array('legend' => 'Personal Information'));
	            
                $form->getElement('postal_code')->setValue($this->member->postal_code);
                $form->getElement('country')->setValue($this->member->country);                
            }
            // Member not logged in
            elseif ($this->_getParam('email') && !$this->_getParam('password')) {
                $this->internal->params->exmemnli = $this->db->fetchOne('SELECT 1 FROM member_members WHERE lower(email) = lower(?)', $this->_getParam('email'));
                
                if ($this->internal->params->exmemnli) {
                    $form->getElement('username')->setRequired(false)->clearValidators();
                    $form->getElement('password')->setRequired(false)->clearValidators();
                    $form->getElement('password_confirm')->setRequired(false)->clearValidators();
                }
            }
            elseif ($this->_getParam('email')) {
                // Do LIVE email check
                $SMTP_Validator = new ViaMe_Vm_ValidateEmail();
                #$SMTP_Validator->debug = true;
                $results = $SMTP_Validator->validate(array($this->_getParam('email')), $this->community->email);
                
                if (isset($results[$this->_getParam('email')]) && ($results[$this->_getParam('email')][0] === false)) {
                    // Came back false
                    if (isset($results[$this->_getParam('email')][1]) && $results[$this->_getParam('email')][1]) {
                        $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('email') . ' (' . $results[$this->_getParam('email')][1] . ') - Please correct or select an alternate email address.';
                    }
                    else {
                        $this->view->formErrors[] = 'Invalid Email Address: ' . $this->_getParam('email');
                    }
                    $this->_setParam('email', '');
                }
            }
            
            
            // Trying to pay with PayPal
            if ($this->_getParam('paypal') || $this->_getParam('paypal_x') || $this->_getParam('paypal_y')) {
                echo 'Trying to Pay with PayPal';
                foreach (array('address1', 'address2', 'city', 'state', 'postal_code', 'country', 'phone', 'cc_type', 'cc_num', 'cc_exp_month', 'cc_exp_year', 'cc_security_code') as $key) {
                    $this->_setParam($key, null);
                    unset($_POST[$key]);
                }
                
                $this->internal->params->paypal = 1;
            }
            
            if ($this->getRequest()->isPost() && !$this->_getParam('access_selection')) {
                $this->view->formErrors[] = 'You must select a valid subscription type.';
            }
            
            /* No longer send phone number for ARB subscriptions.  Just AuthCapture and store in member_members
            if ($this->getRequest()->isPost() && $this->_getParam('phone') && (preg_match_all('/[0-9]/', $this->_getParam('phone')) > 10)) {
                $this->view->formErrors[] = 'A maximum of 10 digits are allowed for phone numbers.  Do not include country codes.';
            }
            */
            
            // Posted - Keep apply_promo before isValidPartial to short circuit and not display error messages
            if ($this->getRequest()->isPost() && !count($this->view->formErrors) && !$this->_getParam('apply_promo') && $form->isValidPartial($this->_getAllParams())) {
                // Get the correct subscription - DIRECTLY FROM DB - selection is param access_selection
                $access_selected = null;
                if ($access_select_tokens = explode('-', $this->_getParam('access_selection'))) {
                    if (count($access_select_tokens) == 6) {
                        $acl_acls = new acl_models_acls();
                        $access_selected = $acl_acls->fetchRow(
                            $acl_acls->select()
                                ->where('com_id = ?', ($access_select_tokens[0] == 'COM' ? $access_select_tokens[1] : '0'))
                                ->where('net_id = ?', ($access_select_tokens[0] == 'NET' ? $access_select_tokens[1] : '0'))
                                ->where('via_id = ?', ($access_select_tokens[0] == 'VIA' ? $access_select_tokens[1] : '0'))
                                ->where('module_id = ?', $access_select_tokens[2])
                                ->where('matrix_counter = ?', $access_select_tokens[3])
                                ->where('item_counter = ?', $access_select_tokens[4])
                                ->where('counter = ?', $access_select_tokens[5])
                                ->where('active')
                                ->limit(1)
                        );
                    }
                }
                if (!$access_selected) {
                    $this->view->formErrors[] = 'There was an error with the subscription you selected.';
                }
                else {
                    // Apply any discounts - SYNC this up with the displayed choices
                    if (isset($this->view->promo) && $this->view->promo) {
                            if (
                                $access_selected->com_id == $this->view->promo->com_id &&
                                $access_selected->net_id == $this->view->promo->net_id &&
                                $access_selected->via_id == $this->view->promo->via_id &&
                                ($this->view->promo->module_id == 0 || ($access_selected->module_id == $this->view->promo->module_id)) &&
                                ($this->view->promo->matrix_counter == 0 || ($access_selected->matrix_counter == $this->view->promo->matrix_counter)) &&
                                ($this->view->promo->item_counter == 0 || ($access_selected->item_counter == $this->view->promo->item_counter)) &&
                                ($this->view->promo->acl_counter == 0 || ($access_selected->counter == $this->view->promo->acl_counter))
                            ) {
                                // Got a matching promo
                                #echo 'APPLY PROMO';
                                foreach (array('w_member_initial_amount', 'w_member_trial_amount', 'w_member_amount') as $discount_type) {
                                    $discdisc = $discount_type . '_discount';
                                    $disctype = $discount_type . '_discount_type';
                                    
                                    if ($this->view->promo->{$discdisc} > 0 && $access_selected->{$discount_type} > 0) {
                                        $discount = 0;
                                        if ($this->view->promo->{$disctype} == '%') {
                                            $discount = $access_selected->{$discount_type} * $this->view->promo->{$discdisc} / 100;
                                        }
                                        else {
                                            $discount = $this->view->promo->{$discdisc};
                                        }
                                        
                                        $new_amount = round(($access_selected->{$discount_type} - $discount), 2);
                                        
                                        if ($new_amount < 0) { $new_amount = 0; }
                                        $access_selected->{$discount_type} = $new_amount;
                                    }
                                }
                            }
                    }
                    
                    // Start the TRANSACTION
                    // $this->db->beginTransaction();
                    
                    $member_id = $profile_id = $email_addresss = $referrer_community_id = $referrer_profile_id = null;
                    // Referrers
                    if (isset($_COOKIE[$this->config->auth->cookie_name->community_referrer_id]) && $_COOKIE[$this->config->auth->cookie_name->community_referrer_id] > 0) {
                        $referrer_community_id = $_COOKIE[$this->config->auth->cookie_name->community_referrer_id];
                    }
                    if ($this->_getParam('profile_referrer_id')) { $referrer_profile_id = $this->_getParam('profile_referrer_id'); }
                    if (isset($_COOKIE[$this->config->auth->cookie_name->profile_referrer_id]) && $_COOKIE[$this->config->auth->cookie_name->profile_referrer_id] > 0) {
                        $referrer_profile_id = $_COOKIE[$this->config->auth->cookie_name->profile_referrer_id];
                    }
                    // OVERRIDE the referrer profile with the id in the promo code, if there is one
                    if (isset($this->view->promo) && $this->view->promo && isset($this->view->promo->referrer_profile_id) && $this->view->promo->referrer_profile_id) {
                        $referrer_profile_id = $this->view->promo->referrer_profile_id;
                    }
                    
                    // Handle Member Portion
                    if (isset($this->member)) {
                        // Logged in member - Check for existing Access
                        $member_id = $this->member->id;
                        $profile_id = $this->member->profile->id;
                        $email_address = $this->member->email;
                        
                        if (!$this->_getParam('double_access_check')) {
                            $dbl_check = $this->db->select()
                                ->from(array('obj' => 'acl_members'), array(new Zend_Db_Expr('1')))
                                ->where('obj.active NOTNULL')
                                
                                ->join(array('a' => 'acl_acls'), 'obj.com_id=a.com_id AND obj.net_id=a.net_id AND obj.via_id=a.via_id AND obj.module_id=a.module_id AND obj.matrix_counter=a.matrix_counter AND obj.item_counter=a.item_counter AND obj.acl_counter=a.counter',
                                    array(
                                    )
                                )
                                ->where('a.active NOTNULL')
                                ->where('a.com_id=?', $access_selected->com_id)
                                ->where('a.net_id=?', $access_selected->net_id)
                                ->where('a.via_id=?', $access_selected->via_id)
                                ->where('a.module_id=?', $access_selected->module_id)
                                ->where('a.matrix_counter=?', $access_selected->matrix_counter)
                                ->where('a.item_counter=?', $access_selected->item_counter)
                                #->where('a.counter=?', $access_selected->counter) // Don't check counter
                                
                                ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                                    array(
                                    )
                                )
                                ->where('p.active=?', 't')
                                
                                ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                                    array(
                                    )
                                )
                                ->where('b.active = ?', 't')
                                ->where('b.id = ?', $member_id)
                            ;
                            
                            if ($this->db->fetchOne($dbl_check)) {
                                $this->view->formErrors[] = "You may already have access.  If you are sure you don't, please submit again.";
                            }
                        }
                        
                        $form->getElement('double_access_check')->setValue('1');
                    }
                    elseif ($this->_getParam('email') && !$this->_getParam('password')) {
                        // Existing member not logged in - Get default ID - Check for existing Access
                        
                        $select = $this->db->select()
                            ->from(array('obj' => 'member_members'), array('member_id' => 'id', 'email_address' => 'email'))
                            #->where('obj.active = ?', 't')
                            ->where('lower(obj.email) = lower(?)', $this->_getParam('email'))
                            
                            ->join(array('p' => 'profile_profiles'), 'obj.id = p.member_id',
                                array(
                                    'profile_id' => 'id'
                                )
                            )
                            ->where('p.active=?', 't')
                            
                            ->order(array('p.base DESC', 'p.id'))
                            ->limit(1)
                        ;
                    
                        if (($tempMember = $this->db->fetchRow($select))) {
                            $member_id = $tempMember->member_id;
                            $profile_id = $tempMember->profile_id;
                            $email_address = $tempMember->email_address;
                            
                            if (!$this->_getParam('double_access_check')) {
                                $dbl_check = $this->db->select()
                                    ->from(array('obj' => 'acl_members'), array(new Zend_Db_Expr('1')))
                                    ->where('obj.active NOTNULL')
                                    
                                    ->join(array('a' => 'acl_acls'), 'obj.com_id=a.com_id AND obj.net_id=a.net_id AND obj.via_id=a.via_id AND obj.module_id=a.module_id AND obj.matrix_counter=a.matrix_counter AND obj.item_counter=a.item_counter AND obj.acl_counter=a.counter',
                                        array(
                                        )
                                    )
                                    ->where('a.active NOTNULL')
                                    ->where('a.com_id=?', $access_selected->com_id)
                                    ->where('a.net_id=?', $access_selected->net_id)
                                    ->where('a.via_id=?', $access_selected->via_id)
                                    ->where('a.module_id=?', $access_selected->module_id)
                                    ->where('a.matrix_counter=?', $access_selected->matrix_counter)
                                    ->where('a.item_counter=?', $access_selected->item_counter)
                                    #->where('a.counter=?', $access_selected->counter) // Don't check counter
                                    
                                    ->join(array('p' => 'profile_profiles'), 'obj.profile_id = p.id',
                                        array(
                                        )
                                    )
                                    ->where('p.active=?', 't')
                                    
                                    ->join(array('b' => 'member_members'), 'p.member_id = b.id',
                                        array(
                                        )
                                    )
                                    ->where('b.active = ?', 't')
                                    ->where('b.id = ?', $member_id)
                                ;
                                
                                if ($this->db->fetchOne($dbl_check)) {
                                    $this->view->formErrors[] = "You may already have access.  If you are sure you don't, please submit again.";
                                }
                                
                                $form->getElement('double_access_check')->setValue('1');
                            }
                        }
                        else {
                            $this->view->formErrors[] = 'There was an issue pulling up your member account. Try using a different email address.';
                        }
                    }
                    elseif ($this->_getParam('email') && $this->_getParam('username') && $this->_getParam('password')) {
                        // Create account, profile, space - Get default ID - Send confirmation email??? - SYNC UP WITH MEMBER REGISTER
                        
                        $fields = array('first_name', 'last_name', 'postal_code', 'email', 'phone');
                        $params = (object) $form->getValues();
                        $data['password'] = md5($params->password);
                        $data['password_salt'] = md5(uniqid(rand(), true));
                        foreach ($fields as $field) {
                            if (isset($params->$field) && $params->$field !== '') {
                                $data[$field] = $params->$field;
                            }
                        }
                        
                        // Community ID
                        $data['community_id'] = $this->community->id;
                        // Signup Entrace
                        $data['signup_entrance'] = 'member_subscribe-' . $this->_getParam('access_selection');
                        if ($this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'))) {
                            $data['signup_entrance'] = $this->getRequest()->getCookie('signup_entrance', $this->_getParam('signup_entrance'));
                        }
                        // Click Track
                        if ($this->getRequest()->getCookie('click_track', $this->_getParam('click_track'))) {
                            $data['click_track'] = $this->getRequest()->getCookie('click_track', $this->_getParam('click_track'));
                        }
                        
                        // Update Any Referrers
                        if (isset($referrer_community_id) && $referrer_community_id) {
                            $data['referrer_community_id'] = $referrer_community_id;
                        }
                        if (isset($referrer_profile_id) && $referrer_profile_id) {
                            $data['referrer_profile_id'] = $referrer_profile_id;
                        }
                        
                        // Check for duplicate Email
                        if ($this->db->fetchOne('SELECT 1 FROM member_members WHERE lower(email)=lower(?)', $data['email'])) {
                            $form->getElement('email')->setValue('');
                            $this->view->formErrors[] = 'That email address is already registered.';
                        }
                        else {
                            $this->db->beginTransaction();
                            try {
                                $member_members = new member_models_members();
                                $data['ip_address'] = $_SERVER['REMOTE_ADDR'];
                                $member_members->insert($data);
                                $id = $this->db->lastInsertId('member_members', 'id');
                                
                                // Check for duplicate profile name
                                // Checks - duplicate email or squashed name
                                
                                if (isset($data['first_name'])) {
                                    $name = $data['first_name'];
                                    
                                    if (isset($data['middle_name'])) {
                                        $name .= ' ' . $data['middle_name'];
                                    }
                                    if (isset($data['last_name'])) {
                                        $name .= ' ' . $data['last_name'];
                                    }
                                }
                                else {
                                    $name = $this->_getParam('username');
                                }
                                
                                $email_address = $email = $data['email'];
                                
                                $profile_profiles = new profile_models_profiles();
                                $profile_profiles->insert(array('member_id' => $id, 'name' => $this->_getParam('username'), 'base' => 't', 'default_profile' => 't', 'community_id' => $this->community->id));
                                
                                $pid = $this->db->lastInsertId('profile_profiles', 'id');
                                $this->log->setEventItem('profile_id', $pid);
                                $this->log->ALERT("New member ($id) and profile (" . $this->_getParam('username') . ") successfully created.");
                                
                                // Mail the email with confirmation
                                $partial_array = array('email' => $email, 'name' => $name, 'id' => $id, 'key' => md5($data['password_salt'] . $data['password']), 'no_email_add' => '1', 'internal' => $this->internal);
                                
                                // Variable Output
                                if (is_file($this->view->getScriptPath('../../../member/views/scripts/register/emails/verify/' . $this->community->name . '.phtml'))) {
                                    $temp = $this->view->partial('../../../member/views/scripts/register/emails/verify/' . $this->community->name . '.phtml', $partial_array);
                                }
                                else {
                                    $temp = $this->view->partial('../../../member/views/scripts/register/emails/verify.phtml', $partial_array);
                                }
                                
                                $this->_helper->ViaMe->sendEmail($email, $name, 'Please Verify Your ' . $this->community->display . ' Member Account!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                                
                                
                                // Member Created
                                $this->db->commit();
                                
                                // Prevent email address registered error
                                $this->internal->params->exmemnli = 1;
                                
                                $member_id = $id;
                                $profile_id = $pid;
                                
                                // Blank the referrer
                                #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->community_referrer_id);
                                #$this->_helper->ViaMe->setVMCookie($this->config->auth->cookie_name->profile_referrer_id);
                            } catch (Exception $e) {
                                $this->db->rollBack();
                                $this->log->EMERG('New member creation failed! ' . var_export($this->_getAllParams(), true) . ' : ' . var_export($data, true));
                                $this->view->formErrors[] = 'Account creation failed.  Please try again.';
                            }
                        }
                    }
                    
                    
                    // TRANSACT - determine what type of transaction (straight or recurring)
                    //  If pass, create access, add to lm, commit, send email, thank you page
                    //  Else Redisplay
                    $dateTimeServer = new DateTime();
                    $dateTimeServer->setTimeZone(new DateTimeZone('America/Denver')); // Authorize.Net
                    
                    $activaction_greater_than_today = false;
                    if (isset($access_selected->w_member_start) && ($access_selected->w_member_start)) {
                        if (strtotime($access_selected->w_member_start) > strtotime(date($dateTimeServer->format('Y-m-d')))) {
                            $activaction_greater_than_today = true;
                        }
                    }
                    $this->view->activaction_greater_than_today = $activaction_greater_than_today;
                    
                    $initial_amount_to_charge = 0;
                    if ((isset($access_selected->w_member_initial_amount) && $access_selected->w_member_initial_amount) || !$activaction_greater_than_today) {
                        // If there is an initial charge OR activation starts now, we must process the first transaction
                        if (isset($access_selected->w_member_initial_amount) && $access_selected->w_member_initial_amount) {
                            $initial_amount_to_charge += $access_selected->w_member_initial_amount;
                        }
                        
                        if (!$activaction_greater_than_today) {
                            if (isset($access_selected->w_member_trial_amount) && (!is_null($access_selected->w_member_trial_amount)) && is_numeric($access_selected->w_member_trial_amount) && ($access_selected->w_member_trial_amount >= 0)) {
                                $initial_amount_to_charge += $access_selected->w_member_trial_amount;
                            }
                            else {
                                $initial_amount_to_charge += $access_selected->w_member_amount;
                            }
                        }
                    }
                    
                    // Either Start Date or Today + 1 Interval (Trial or Regular)
                    $init_exp = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                        "now() + ('" . $access_selected->w_member_trial_quantity . ' ' . $access_selected->w_member_trial_interval . "')::interval"
                        :
                        "now() + ('" . $access_selected->w_member_quantity . ' ' . $access_selected->w_member_interval . "')::interval"
                    );
                    $recur_start_date = $this->db->fetchOne("SELECT DATE($init_exp)"); 
                    
                    // Check to make sure the credit card doesn't expire before the start of the recurring billing
                    //   If so, just add a year - They will get the CC expired notice and such...
                    $recurring_billing_cc_exp = $this->_getParam('cc_exp_month') . '-' . $this->_getParam('cc_exp_year');
                    if (
                        new DateTime($this->_getParam('cc_exp_year') . '-' . $this->_getParam('cc_exp_month') . '-01')
                        < 
                        new DateTime(preg_replace('/(\d\d\d\d\-\d\d)\-.*/', '$1', ($activaction_greater_than_today ? $access_selected->w_member_start : $recur_start_date)))
                    ) {
                        if ($activaction_greater_than_today) {
                            $this->view->formErrors[] = 'Please use a credit card that expires after the activation date (' . $access_selected->w_member_start . ').';
                        }
                        else {
                            $recurring_billing_cc_exp = $this->_getParam('cc_exp_month') . '-' . ($this->_getParam('cc_exp_year') + 1);
                        }
                    }
                    
                    if (!count($this->view->formErrors) && $member_id && $profile_id && $email_address) {
                        // Begin the process
                        set_time_limit(120); // Should complete within 2 minutes...
                        
                        // PAYMENT GATEWAY SELECTOR
                        $payment = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST'), $this->community->name); // LIVE Is Production Environment Only
                        $payment->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
                        
                        // Lets charge or do an authOnly
                        $initialInvoiceNumber = date('YmdHis') . '-' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
                        $trxn_data = array(
                            #'refId' => 'Reference ID',
                            'transactionRequest' => array(
                                'transactionType' => ($initial_amount_to_charge ? 'authCaptureTransaction' : 'authOnlyTransaction'),
                                'amount' => ($initial_amount_to_charge ? $initial_amount_to_charge : (($this->_getParam('cc_type') == 'Visa') ? 0 : 1)),
                                'payment' => array(
                                    'creditCard' => array(
                                        'cardNumber' => preg_replace('/[^\d]/', '', $this->_getParam('cc_num')),
                                        'expirationDate' => $this->_getParam('cc_exp_month') . '-' . $this->_getParam('cc_exp_year'),
                                        'cardCode' => $this->_getParam('cc_security_code')
                                    ),
                                ),
                                'order' => array(
                                    'invoiceNumber' => $initialInvoiceNumber,
                                    'description' => (isset($access_selected->display) && $access_selected->display ? $access_selected->display : $access_selected->title) . ' : ' . $profile_id . '-' . $this->_getParam('access_selection') . '-I'
                                ),
                                'customer' => array(
                                    'id' => $profile_id,
                                    'email' => $this->_getParam('email')
                                ),
                                'billTo' => array(
                                    'firstName' => $this->_getParam('first_name'),
                                    'lastName' => $this->_getParam('last_name'),
                                    'address' => $this->_getParam('address1') . ($this->_getParam('address2') ? ' ' . $this->_getParam('address2') : ''),
                                    'city' => $this->_getParam('city'),
                                    'state' => $this->_getParam('state'),
                                    'zip' => $this->_getParam('postal_code'),
                                    'country' => $this->_getParam('country'),
                                    'phoneNumber' => $this->_getParam('phone')
                                ),
                                'customerIP' => $_SERVER['REMOTE_ADDR']
                            )
                        );
                        #Zend_Debug::Dump($trxn_data);
                        
    
                        $trxn_result = $payment->transact('createTransactionRequest', $trxn_data);
                        
                        
                        #Zend_Debug::Dump($trxn_result);
                        if ($trxn_result['status'] && isset($trxn_result['res']['transactionResponse']['responseCode']) && $trxn_result['res']['transactionResponse']['responseCode'] == 1) {
                            // Initial Charge or Auth has processed
                            
                            // Log the initial transaction
                            // Log the transaction
                            $this->db->insert('log_trans', array(
                                'identifier' => (isset($trxn_result['res']['transactionResponse']['transId']) && $trxn_result['res']['transactionResponse']['transId'] ? $trxn_result['res']['transactionResponse']['transId'] : null),
                                'com_id' => $access_selected->com_id,
                                'net_id' => $access_selected->net_id,
                                'via_id' => $access_selected->via_id,
                                'module_id' => $access_selected->module_id,
                                'matrix_counter' => $access_selected->matrix_counter,
                                'item_counter' => $access_selected->item_counter,
                                'acl_counter' => $access_selected->counter,
                                'profile_id' => $profile_id,
                                'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'amount' => $initial_amount_to_charge,
                                'referrer_profile_id' => ((isset($referrer_profile_id) && $referrer_profile_id) ? $referrer_profile_id : null),
                                'message' => 'Initial Payment',
                                'raw_result' => $trxn_result['raw'],
                                'serialized_result' => serialize($trxn_result['res'])
                            ));
                            
                            // Create the recurring billing profile
                            
                            // Auth net fixes - Day Week Month Year to days or months
                            $interval_length = '';
                            $interval_unit = '';
                            switch ($access_selected->w_member_interval) {
                                case 'Day':
                                    $interval_length = $access_selected->w_member_quantity;
                                    $interval_unit = 'days';
                                    break;
                                case 'Week':
                                    $interval_length = $access_selected->w_member_quantity * 7;
                                    $interval_unit = 'days';
                                    break;
                                case 'Month':
                                    $interval_length = $access_selected->w_member_quantity;
                                    $interval_unit = 'months';
                                    break;
                                case 'Year':
                                    $interval_length = $access_selected->w_member_quantity * 12;
                                    $interval_unit = 'months';
                                    break;
                            }
                            
                            $trxn_data = array(
                                #'refId' => 'Reference ID',
                                'subscription' => array(
                                    'name' => (isset($access_selected->display) && $access_selected->display ? $access_selected->display : $access_selected->title),
                                    'paymentSchedule' => array(
                                        'interval' => array(
                                            'length' => $interval_length,
                                            'unit' => $interval_unit, // days, months
                                        ),
                                        'startDate' => ($activaction_greater_than_today ? $access_selected->w_member_start : $recur_start_date),
                                        'totalOccurrences' => ($access_selected->w_member_auto_renew ? '9999' : 1),
                                        'trialOccurrences' => 0
                                    ),
                                    'amount' => $access_selected->w_member_amount,
                                    'trialAmount' => 0,
                                    'payment' => array(
                                        'creditCard' => array(
                                            'cardNumber' => preg_replace('/[^\d]/', '', $this->_getParam('cc_num')),
                                            'expirationDate' => $recurring_billing_cc_exp,
                                            'cardCode' => $this->_getParam('cc_security_code')
                                        ),
                                    ),
                                    'order' => array(
                                        'invoiceNumber' => date('YmdHis') . '-' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT),
                                        'description' => (isset($access_selected->display) && $access_selected->display ? $access_selected->display : $access_selected->title) . ' : ' . $profile_id . '-' . $this->_getParam('access_selection') . '-C',
                                    ),
                                    'customer' => array(
                                        'id' => $profile_id,
                                        'email' => $this->_getParam('email'),
                                        //'phoneNumber' => $this->_getParam('phone'),
                                    ),
                                    'billTo' => array(
                                        'firstName' => $this->_getParam('first_name'),
                                        'lastName' => $this->_getParam('last_name'),
                                        'address' => $this->_getParam('address1') . ($this->_getParam('address2') ? ' ' . $this->_getParam('address2') : ''),
                                        'city' => $this->_getParam('city'),
                                        'state' => $this->_getParam('state'),
                                        'zip' => $this->_getParam('postal_code'),
                                        'country' => $this->_getParam('country')
                                    )
                                )
                            );
                            if ($activaction_greater_than_today && $access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0) {
                                $trxn_data['subscription']['paymentSchedule']['trialOccurrences'] = 1;
                                $trxn_data['subscription']['trialAmount'] = $access_selected->w_member_trial_amount;
                            }
                            #Zend_Debug::Dump($trxn_data);
                            
                            $trxn_result = $payment->transact('ARBCreateSubscriptionRequest', $trxn_data);
                            
                            #Zend_Debug::Dump($trxn_result);
                            if ($trxn_result['status']) {
                                // Recurring billing profile has been successfully Created
                                
                                // Create the access
                                if ($activaction_greater_than_today) {
                                    $act = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp"
                                        :
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp"
                                    );
                                    /*
                                    $exp = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp + (w_member_trial_quantity || ' ' || w_member_trial_interval)::interval"
                                        :
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp + (w_member_quantity || ' ' || w_member_interval)::interval"
                                    );
                                    */
                                    /*
                                    $exp = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp + (w_member_trial_quantity || ' ' || w_member_trial_interval)::interval"
                                        :
                                        "('" . $access_selected->w_member_start . "' ' ' || now()::time)::timestamp + (w_member_quantity || ' ' || w_member_interval)::interval"
                                    );
                                    */
                                    // For starts later than today, set expiration to same as activation and rely on webhook to increment it out
                                    $exp = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp"
                                        :
                                        "('" . $access_selected->w_member_start . "' || ' ' || now()::time)::timestamp"
                                    );
                                }
                                else {
                                    $act = 'null';
                                    $exp = (($access_selected->w_member_trial_amount >= 0 && $access_selected->w_member_trial_quantity > 0 && $access_selected->w_member_trial_interval) ?
                                        "now() + (w_member_trial_quantity || ' ' || w_member_trial_interval)::interval"
                                        :
                                        "now() + (w_member_quantity || ' ' || w_member_interval)::interval"
                                    );
                                }
                                
                                $stmt = $this->db->prepare("INSERT INTO acl_members (identifier, com_id, net_id, via_id, module_id, matrix_counter, item_counter, acl_counter, profile_id, activation, expiration, member_amount, member_quantity, member_interval, member_auto_renew, cc_exp_month, cc_exp_year, serialized_result, click_track, referrer_profile_id) SELECT ?, com_id, net_id, via_id, module_id, matrix_counter, item_counter, counter, ?, $act, $exp, ?, w_member_quantity, w_member_interval, w_member_auto_renew, ?, ?, ?, ?, ? FROM acl_acls WHERE com_id=? AND net_id=? AND via_id=? AND module_id=? AND matrix_counter=? and item_counter=? AND counter=?");
                                try {
                                    $stmt->execute(array((isset($trxn_result['res']['subscriptionId']) ? $trxn_result['res']['subscriptionId'] : $trxn_result['res']['subscriptionId']), $profile_id, $access_selected->w_member_amount, $this->_getParam('cc_exp_month', null), $this->_getParam('cc_exp_year', null), serialize($trxn_result), ($this->getRequest()->getCookie('click_track', $this->_getParam('click_track', null))), ((isset($referrer_profile_id) && $referrer_profile_id) ? $referrer_profile_id : null), $access_selected->com_id, $access_selected->net_id, $access_selected->via_id, $access_selected->module_id, $access_selected->matrix_counter, $access_selected->item_counter, $access_selected->counter));
                                } catch (Exception $e) {
                                    // Duplicate entry in database - Will FAIL
                                    $this->log->EMERG('New subscription access creation failed!');
                                    #Zend_Debug::Dump($e);
                                    echo $this->view->CM(array(
                                        'class' => 'cm decorated plain errormessage',
                                        'hd' => 'Access Creation Failed',
                                        'hd2' => 'Could not create access',
                                        'bd' => '<p class="error">An error has occurred. Your access creation has failed.</p><p>An unexpected error has occurred and has caused your subscription process to not fully complete.  You may already have a duplicate subscription.  Please contact our customer service department so that we can get the error corrected.</p><p>Please <strong>DO NOT</strong> go back and/or re-try the trasaction.</p>'
                                    ));
                                    return $this->_helper->viewRenderer->setNoRender();
                                }
                                
                                // Log the transaction
                                $this->db->insert('log_trans', array(
                                    'identifier' => (isset($trxn_result['res']['subscriptionId']) && $trxn_result['res']['subscriptionId'] ? $trxn_result['res']['subscriptionId'] : null),
                                    'com_id' => $access_selected->com_id,
                                    'net_id' => $access_selected->net_id,
                                    'via_id' => $access_selected->via_id,
                                    'module_id' => $access_selected->module_id,
                                    'matrix_counter' => $access_selected->matrix_counter,
                                    'item_counter' => $access_selected->item_counter,
                                    'acl_counter' => $access_selected->counter,
                                    'profile_id' => $profile_id,
                                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                                    'amount' => 0,
                                    'referrer_profile_id' => ((isset($referrer_profile_id) && $referrer_profile_id) ? $referrer_profile_id : null),
                                    'message' => 'Subscription Created',
                                    'raw_result' => $trxn_result['raw'],
                                    'serialized_result' => serialize($trxn_result['res'])
                                ));
                                
                                // Update any missing information in member_members
                                $this->db->update('member_members',
                                    array(
                                        'first_name' =>  new Zend_Db_Expr($this->db->quoteInto("COALESCE(first_name, NULLIF(?, ''))", $this->_getParam('first_name'))),
                                        'last_name' => new Zend_Db_Expr($this->db->quoteInto("COALESCE(last_name, NULLIF(?, ''))", $this->_getParam('last_name'))),
                                        'postal_code' => new Zend_Db_Expr($this->db->quoteInto("COALESCE(postal_code, NULLIF(?, ''))", $this->_getParam('postal_code'))),
                                        'phone' => new Zend_Db_Expr($this->db->quoteInto("COALESCE(phone, NULLIF(?, ''))", $this->_getParam('phone')))
                                    ),
                                    array(
                                        $this->db->quoteInto('id=?', $member_id)
                                    )
                                );
                                
                                
                                // Add to lm if needed
                                /*
                                    NOTE: Adding it here for now, but may have to be moved to the webhook.  Future start date users
                                      should be added to lm on the commencement of their subscription...
                                      
                                      OR, create it here for everyone except those with future start dates - those do in webhook... ???
                                */
                                if (isset($access_selected->greenarrow_list_id) && $access_selected->greenarrow_list_id) {
                                    try {
                                        require_once $this->vars->APP_PATH . "/library/Other/GreenArrowStudioAPI.php";
                                        $GA_API = new GreenArrowStudioAPI();
                                        
                                        $result_add = $GA_API->call_method('subscriberAdd', Array(
                                    		'email'         => $this->_getParam('email'),
                                    		'listid'        => $access_selected->greenarrow_list_id,
                                    		'reactivate'    => 1,
                                    		'requestip'     => $_SERVER['REMOTE_ADDR']
                                    	));
                                    	
                                    	// Unsubscribe SCNEO Members From SCN Regular (5) and SCN Old (6) and SCNEO Free Picks (31) and Unsub From Free Picks DB
                                    	// Do it for all subscribers - Sync with CronController
                                    	if (true || $access_selected->greenarrow_list_id == 28) {
                                    	    $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                                        		'email'         => $this->_getParam('email'),
                                        		'listid'        => 5,
                                        		'requestip'     => $_SERVER['REMOTE_ADDR']
                                        	));
                                            $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                                        		'email'         => $this->_getParam('email'),
                                        		'listid'        => 6,
                                        		'requestip'     => $_SERVER['REMOTE_ADDR']
                                            ));
                                            $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                                        		'email'         => $this->_getParam('email'),
                                        		'listid'        => 31,
                                        		'requestip'     => $_SERVER['REMOTE_ADDR']
                                            ));
                                            $this->db->update('scneol_members', array('active' => 'f'), $this->db->quoteInto("email ILIKE ?", $this->_getParam('email')));
                                    	}
                                    } catch (Exception $e) { }
                                }
                                
                                
                                // Send email receipt
                                $attachments = array();
                                $partial_array = array('email' => $this->_getParam('email'), 'access_selected' => $access_selected, 'title' => $this->view->title, 'trxn' => $trxn_data, 'trxn_result' => $trxn_result, 'internal' => $this->internal);
                                // Variable Output
                                # Specific with acl counter - 21066-0-0-0-3.phtml
                                if (is_file($this->view->getScriptPath('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '-' . $access_selected->counter . '.phtml'))) {
                                    $temp = $this->view->partial('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '-' . $access_selected->counter . '.phtml', $partial_array);
                                }
                                # Specific up to item counter - 21066-0-0-0.phtml
                                elseif (is_file($this->view->getScriptPath('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '.phtml'))) {
                                    $temp = $this->view->partial('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '.phtml', $partial_array);
                                }
                                # Specific up to matrix counter - 21066-0-0.phtml
                                elseif (is_file($this->view->getScriptPath('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '.phtml'))) {
                                    $temp = $this->view->partial('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '.phtml', $partial_array);
                                }
                                # Specific up to space - 21066.phtml
                                elseif (is_file($this->view->getScriptPath('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '.phtml'))) {
                                    $temp = $this->view->partial('access/emails/subscribed/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '.phtml', $partial_array);
                                }
                                # Community
                                elseif (is_file($this->view->getScriptPath('access/emails/subscribed/' . $this->community->name . '.phtml'))) {
                                    $temp = $this->view->partial('access/emails/subscribed/' . $this->community->name . '.phtml', $partial_array);
                                }
                                # General
                                else {
                                    $temp = $this->view->partial('access/emails/subscribed.phtml', $partial_array);
                                }
                                
                                // EOP Special Trump Report Attachment
                                /*
                                if ($access_selected->via_id == 21066) {
                                    $content = file_get_contents("/usr/local/www/websites/viame.com/files/private/2016/12/21/0e19720b75d570bdf672b00b543de892");
                                    $attachment = new Zend_Mime_Part($content);
                                    $attachment->type = 'application/pdf';
                                    $attachment->disposition = Zend_Mime::DISPOSITION_ATTACHMENT;
                                    $attachment->encoding = Zend_Mime::ENCODING_BASE64;
                                    $attachment->filename = 'Trump-Research-Report.pdf'; // name of file
                                    
                                    $attachments[] = $attachment;
                                }
                                */
                                
                                // Send Welcome Email
                                $this->_helper->ViaMe->sendEmail($this->_getParam('email'), ($this->_getParam('first_name') . ' ' . $this->_getParam('last_name')), 'Details About Your New ' . (isset($access_selected->title) && $access_selected->title ? ' ' . $access_selected->title . ' ' : '') . 'Subscription!', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display, $attachments);
                                
                                
                                // Display thank you page
                                $this->view->access_selected = $access_selected;
                                // Should we track the value or the actual amount?
                                //$this->view->conversion_value = (isset($initial_amount_to_charge) && $initial_amount_to_charge ? $initial_amount_to_charge : $access_selected->w_member_amount);
                                $this->view->conversion_value = (isset($initial_amount_to_charge) && $initial_amount_to_charge ? $initial_amount_to_charge : 0);
                                $this->view->initial_invoice_number = $initialInvoiceNumber;
                                // Variable Output
                                if (is_file($this->view->getScriptPath('access/subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '-' . $access_selected->counter . '.phtml'))) {
                                    return $this->render('subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '-' . $access_selected->counter);
                                }
                                elseif (is_file($this->view->getScriptPath('access/subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter . '.phtml'))) {
                                    return $this->render('subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '-' . $access_selected->item_counter);
                                }
                                elseif (is_file($this->view->getScriptPath('access/subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter . '.phtml'))) {
                                    return $this->render('subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '-' . $access_selected->module_id . '-' . $access_selected->matrix_counter);
                                }
                                elseif (is_file($this->view->getScriptPath('access/subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)) . '.phtml'))) {
                                    return $this->render('subscribesuccess/' . $this->community->name . '/' . ($access_selected->com_id ? 'com' : ($access_selected->net_id ? 'net' : 'via')) . '/' . ($access_selected->com_id ? $access_selected->com_id : ($access_selected->net_id ? $access_selected->net_id : $access_selected->via_id)));
                                }
                                elseif (is_file($this->view->getScriptPath('access/subscribesuccess/' . $this->community->name . '.phtml'))) {
                                    return $this->render('subscribesuccess/' . $this->community->name);
                                }
                                else {
                                    return $this->render('subscribesuccess');
                                }
                            }
                            else {
                                // Error Creating the Recurring Billing Profile
                                $this->log->EMERG('New recurring subscription profile creation failed!');
                                echo $this->view->CM(array(
                                    'class' => 'cm decorated plain errormessage',
                                    'hd' => 'Subscription Profile Creation Failed',
                                    'hd2' => 'Could not create subscription profile.',
                                    'bd' => '<p class="error">An error has occurred. Your subscription profile was not created.</p><p>An unexpected error has occurred and has caused the creation of your recurring subscription to fail.  Please contact our customer service department so that we can get the error corrected.</p><p>It is advisable that you <strong>DO NOT</strong> go back and/or re-try the trasaction.</p>'
                                ));
                                return $this->_helper->viewRenderer->setNoRender();
                            }
                        }
                        elseif (isset($trxn_result['errstr']) && $trxn_result['errstr']) {
                            $this->view->formErrors[] = $trxn_result['errstr'] . ((isset($trxn_result['errno']) && $trxn_result['errno']) ? ' (Code: ' . $trxn_result['errno'] . ')' : '');
                            
                        }
                        else {
                            $this->view->formErrors[] = 'There was an error while processing your transaction.  Please try again.';
                            #Zend_Debug::Dump($trxn_result);
                        }
    
                    }
                }
            }
            else {
                $form->populate($this->_getAllParams());
                if (isset($this->member)) {
                    $form->getElement('email')->setValue($this->member->email);
                }
            }
            
            $query = "SELECT * FROM acl_acls WHERE (NOT invisible";
            // Pulling Up ACLs Matching Promo Even If invisible
            if (isset($this->view->promo) && $this->view->promo) {
                $query .= ' OR (';
                $query .= $this->db->quoteInto('com_id=?', $this->view->promo->com_id) . ' AND ' .
                          $this->db->quoteInto('net_id=?', $this->view->promo->net_id) . ' AND ' .
                          $this->db->quoteInto('via_id=?', $this->view->promo->via_id) . ' AND ' .
                          $this->db->quoteInto('module_id=?', $this->view->promo->module_id) . ' AND ' .
                          $this->db->quoteInto('matrix_counter=?', $this->view->promo->matrix_counter) . ' AND ' .
                          $this->db->quoteInto('item_counter=?', $this->view->promo->item_counter) . ' AND ' .
                          $this->db->quoteInto('counter=?', $this->view->promo->acl_counter);
                $query .= ')';
            }
            $query .= ") AND (";
            $query .= '(module_id=0 AND matrix_counter=0 AND item_counter=0' . ($this->_getParam('mod') ? " AND recursive='t'" : '') . ')';
            if ($this->_getParam('mod') && $this->_getParam('mid')) {
                $query .= ' OR (' . $this->db->quoteInto('module_id=?', $this->_getParam('mod')) . ' AND ' . $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid')) . ($this->_getParam('iid') ? " AND recursive='t'" : ''). ')';
            }
            if ($this->_getParam('mod') && $this->_getParam('mid') && $this->_getParam('iid')) {
                $query .= ' OR (' . $this->db->quoteInto('module_id=?', $this->_getParam('mod')) . ' AND ' . $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid')) . ' AND ' . $this->db->quoteInto('item_counter=?', $this->_getParam('iid')) . ')';
            }
            $query .= ') AND (w_password NOTNULL OR (w_member_amount NOTNULL AND w_member_quantity NOTNULL AND w_member_interval NOTNULL)) AND ';
            $query .= $this->db->quoteInto("privilege >= ?", $this->_getParam('priv'));
            $query .= " AND (expiration ISNULL OR expiration >= 'now') AND active='t' AND ";
            switch($this->_getParam('stype')) {
                case 'VIA':
                    $query .= $this->db->quoteInto('via_id=?', $this->_getParam('sid'));
                    break;
                case 'NET':
                    $query .= $this->db->quoteInto('net_id=?', $this->_getParam('sid'));
                    break;
                default:
                    $query .= $this->db->quoteInto('com_id=?', $this->_getParam('sid'));
                    break;
            }
            $query .= ' ORDER BY orderby';
            
            try {
                $this->view->access = $this->db->fetchAll($query);
            } catch (Exception $e) {
                $this->_redirect('/');
            }
            
            if (!count($this->view->access)) {
                $this->_redirect('/');
            }
            
            if (isset($this->view->promo) && $this->view->promo) {
                #Zend_Debug::Dump($this->view->promo);
                
                for ($i = 0; $i < count($this->view->access); $i++) {
                    #Zend_Debug::Dump($this->view->access[$i]);
                    
                    if (
                        $this->view->access[$i]->com_id == $this->view->promo->com_id &&
                        $this->view->access[$i]->net_id == $this->view->promo->net_id &&
                        $this->view->access[$i]->via_id == $this->view->promo->via_id &&
                        ($this->view->promo->module_id == 0 || ($this->view->access[$i]->module_id == $this->view->promo->module_id)) &&
                        ($this->view->promo->matrix_counter == 0 || ($this->view->access[$i]->matrix_counter == $this->view->promo->matrix_counter)) &&
                        ($this->view->promo->item_counter == 0 || ($this->view->access[$i]->item_counter == $this->view->promo->item_counter)) &&
                        ($this->view->promo->acl_counter == 0 || ($this->view->access[$i]->counter == $this->view->promo->acl_counter))
                    ) {
                        // Got a matching promo
                        #echo 'APPLY PROMO';
                        foreach (array('w_member_initial_amount', 'w_member_trial_amount', 'w_member_amount') as $discount_type) {
                            $discdisc = $discount_type . '_discount';
                            $disctype = $discount_type . '_discount_type';
                            
                            if ($this->view->promo->{$discdisc} > 0 && $this->view->access[$i]->{$discount_type} > 0) {
                                $discount = 0;
                                if ($this->view->promo->{$disctype} == '%') {
                                    $discount = $this->view->access[$i]->{$discount_type} * $this->view->promo->{$discdisc} / 100;
                                }
                                else {
                                    $discount = $this->view->promo->{$discdisc};
                                }
                                
                                $new_amount = round(($this->view->access[$i]->{$discount_type} - $discount), 2);
                                
                                if ($new_amount < 0) { $new_amount = 0; }
                                $this->view->access[$i]->{$discount_type} = $new_amount;
                            }
                        }
                    }
                }
            }
            
            $this->view->form = $form;
        }
        else {
            return $this->_redirect('/');
        }
    }
    
    
    public function unsubscribeAction()
    {
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Access Controls', 'PREPEND');
        $this->view->headTitle('Unsubscribe', 'PREPEND');
        
        if (isset($this->member)) {
            if ($this->_getParam('identifier') || ($this->_getParam('stype') && $this->_getParam('sid')>=0 && $this->_getParam('mod')>=0 && $this->_getParam('mid')>=0 && $this->_getParam('iid')>=0 && $this->_getParam('aid')>=0)) {
                $select = $this->db->select()
                    ->from(array('obj' => 'acl_members'))
                    ->where('obj.active=?', 't')
                    ->where('obj.member_auto_renew')
                    ->where('obj.profile_id=?', $this->member->profile->id)
                    
                    ->join(array('a' => 'acl_acls'), 'obj.com_id=a.com_id AND obj.net_id=a.net_id AND obj.via_id=a.via_id AND obj.module_id=a.module_id AND obj.matrix_counter=a.matrix_counter AND obj.item_counter=a.item_counter AND obj.acl_counter=a.counter',
                        array(
                            'title',
                            'display'
                        )
                    )
                    ->limit(1);
                
                if ($this->_getParam('identifier')) {
                    $select->where('obj.identifier=?', $this->_getParam('identifier'));
                }
                else {
                    $select
                        ->where('obj.' . strtolower($this->_getParam('stype', 'via')) . '_id=?', $this->_getParam('sid'))
                        ->where('obj.module_id=?', $this->_getParam('mod'))
                        ->where('obj.matrix_counter=?', $this->_getParam('mid'))
                        ->where('obj.item_counter=?', $this->_getParam('iid'))
                        ->where('obj.acl_counter=?', $this->_getParam('aid'))
                    ;
                }
                if ($subscription = $this->db->fetchRow($select)) {
                    if ($this->_getParam('unsub_confirmed')) {
                        #$trxn_data = array(
                        #    'subscriptionId' => $subscription->identifier
                        #);
                        $manage = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST'), $this->community->name); // LIVE Is Production Environment Only
                        #$trxn_result = $manage->transact('ARBGetSubscriptionStatusRequest', $trxn_data);
                        #if ($trxn_result['status']) { // $trxn_result['res']['STATUS'] = Active | Canceled | Suspended
                            $trxn_data = array(
                                'subscriptionId' => $subscription->identifier
                            );
                            #Zend_Debug::Dump($trxn_data);
                            $trxn_result = $manage->transact('ARBCancelSubscriptionRequest', $trxn_data);
                            #Zend_Debug::Dump($trxn_result);
                            if ($trxn_result['status']) {
                                // Update the DB
                                //  Need to update the acl_members table, but I don't really know what to do...  :-)
                                //    Set active to false?  Also need to monitor end of subscription life and getting them out of the mailing list...
                                $this->db->update('acl_members',
                                    array(
                                        'active' => 'f'
                                    ),
                                    array(
                                        $this->db->quoteInto('com_id=?', $subscription->com_id),
                                        $this->db->quoteInto('net_id=?', $subscription->net_id),
                                        $this->db->quoteInto('via_id=?', $subscription->via_id),
                                        $this->db->quoteInto('module_id=?', $subscription->module_id),
                                        $this->db->quoteInto('matrix_counter=?', $subscription->matrix_counter),
                                        $this->db->quoteInto('item_counter=?', $subscription->item_counter),
                                        $this->db->quoteInto('acl_counter=?', $subscription->acl_counter),
                                        $this->db->quoteInto('profile_id=?', $subscription->profile_id),
                                        $this->db->quoteInto('active=?', 't')
                                    )
                                );
                                
                                // Log the transaction
                                $this->db->insert('log_trans', array(
                                    'identifier' => $subscription->identifier,
                                    'com_id' => $subscription->com_id,
                                    'net_id' => $subscription->net_id,
                                    'via_id' => $subscription->via_id,
                                    'module_id' => $subscription->module_id,
                                    'matrix_counter' => $subscription->matrix_counter,
                                    'item_counter' => $subscription->item_counter,
                                    'acl_counter' => $subscription->acl_counter,
                                    'profile_id' => $subscription->profile_id,
                                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                                    'amount' => 0,
                                    'referrer_profile_id' => $subscription->referrer_profile_id,
                                    'message' => 'Subscription Canceled',
                                    'raw_result' => $trxn_result['raw'],
                                    'serialized_result' => serialize($trxn_result['res'])
                                ));
                                
                               // Send email receipt
                                $partial_array = array('subscription' => $subscription, 'internal' => $this->internal);
                                // Variable Output
                                if (isset($this->member->first_name) && $this->member->first_name) {
                                    $name = $this->member->first_name;
                                    
                                    if (isset($this->member->middle_name) && $this->member->middle_name) {
                                        $name .= ' ' . $this->member->middle_name;
                                    }
                                    if (isset($this->member->last_name)) {
                                        $name .= ' ' . $this->member->last_name;
                                    }
                                }
                                else {
                                    $name = $this->member->profile->name;
                                }
                                $temp = $this->view->partial('access/emails/unsubscribed.phtml', $partial_array);
                                $this->_helper->ViaMe->sendEmail($this->member->email, $name, 'Your Subscription Has Been Canceled', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                                
                                // Send an email notice to admin about this cancellation
                                $this->_helper->ViaMe->sendEmail($this->community->email, null, "$name Canceled Their Subscription", "<p><strong>Name: $name</strong></p>" . Zend_Debug::Dump($subscription, null, false), null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                                
                                // Display success
                                echo $this->view->CM(array(
                                    'class' => 'cm decorated plain successmessage',
                                    'hd' => 'Unsubscribe Complete',
                                    'hd2' => 'You Have Successfully Unsubscribed',
                                    'bd' => '<p class="success">Your unsubscribe request has been completed.</p><p>Your unsubscribe request has been processed.  You will not be billed again.  Thank you.</p>'
                                ));
                            }
                            else {
                                echo $this->view->CM(array(
                                    'class' => 'cm decorated plain errormessage',
                                    'hd' => 'Unsubscribe Error',
                                    'hd2' => 'Trouble Unsubscribing',
                                    'bd' => '<p class="error">An error has occurred. You have not unsubscribed yet.</p><p>Your subscription was found and validated, however, there seems to have been a technical problem when attempting to unsubscribe you. Please hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems unsubscribing, please contact our customer service department.</p>'
                                ));
                            }
                        #}
                        #else {
                        #    echo $this->view->CM(array(
                        #        'class' => 'cm decorated plain errormessage',
                        #        'hd' => 'Unsubscribe Error',
                        #        'hd2' => 'Subscription Not Found',
                        #        'bd' => '<p class="error">An error has occurred. Your subscription could not be found.</p><p>A valid subscription for you with the submitted #parameters could not be found.</p><p>Please use the correct link with subscription parameters to successfully unsubscribe. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems unsubscribing, please contact #our customer service department.</p>'
                        #    ));
                        #}
                    }
                    else {
                        // Get a confirmation
                        echo $this->view->CM(array(
                            'class' => 'cm decorated',
                            'hd' => 'Unsubscribe Confirm',
                            'hd2' => 'Confirm Your Unsubscribe Request',
                            'bd' => '<p style="font-weight: bold;">Please confirm that you would like to cancel your subscription to: '.(isset($subscription->display) && $subscription->display ? $subscription->display : $subscription->title).'</p><p align="center" style="margin: 2em 0; text-align: center;"><a href="?identifier='.$subscription->identifier.'&unsub_confirmed=1" class="fakebutton">Confirmed - I Would Like To Unsubscribe</a></p>'
                        ));
                    }
                }
                else {
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain errormessage',
                        'hd' => 'Unsubscribe Error',
                        'hd2' => 'Subscription Not Found',
                        'bd' => '<p class="error">An error has occurred. Your subscription could not be found.</p><p>A valid subscription for you with the submitted parameters could not be found.</p><p>Please use the correct link with subscription parameters to successfully unsubscribe. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems unsubscribing, please contact our customer service department.</p>'
                    ));
                }
            }
            else {
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Input Error',
                    'hd2' => 'Subscription Required',
                    'bd' => '<p class="error">An error has occurred. Subscription information required.</p><p>Parameters that are required for us to find the product to unsubscribe you from were not supplied and this transaction cannot proceed any further.</p><p>Please use the correct link with subscription parameters to successfully unsubscribe. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems unsubscribing, please contact our customer service department.</p>'
                ));
            }
        }
        else {
            #return $this->_redirect('/member/login/?redirect=' . urlencode($this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
            return $this->_redirect('/member/login/');
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
    
    
    public function updatepaymentAction()
    {
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_redirect($this->target->pre . '/');
        }
        
        $this->_helper->ViaMe->setSubLayout('default');
        
        $this->view->headTitle('Access Controls', 'PREPEND');
        $this->view->headTitle('Update Payment Information', 'PREPEND');
        
        if (isset($this->member)) {
            if ($this->_getParam('identifier') || ($this->_getParam('stype') && $this->_getParam('sid')>=0 && $this->_getParam('mod')>=0 && $this->_getParam('mid')>=0 && $this->_getParam('iid')>=0 && $this->_getParam('aid')>=0)) {
                $select = $this->db->select()
                    ->from(array('obj' => 'acl_members'))
                    ->where('obj.member_auto_renew')
                    ->where('obj.profile_id=?', $this->member->profile->id)
                    
                    ->join(array('a' => 'acl_acls'), 'obj.com_id=a.com_id AND obj.net_id=a.net_id AND obj.via_id=a.via_id AND obj.module_id=a.module_id AND obj.matrix_counter=a.matrix_counter AND obj.item_counter=a.item_counter AND obj.acl_counter=a.counter',
                        array(
                            'title',
                            'display',
                            'greenarrow_list_id'
                        )
                    )
                    
                    ->join(array('p' => 'profile_profiles'), 'obj.profile_id=p.id',
                        array(
                        )
                    )
                    
                    ->join(array('m' => 'member_members'), 'p.member_id=m.id',
                        array(
                            'm_first_name' => 'first_name',
                            'm_last_name' => 'last_name',
                            'm_email' => 'email'
                        )
                    )
                    
                    ->limit(1);
                    
                // Have to allow for reactivation of expired subscriptions
                if (!$this->_getParam('reactivate')) {
                    $select->where('obj.active=?', 't'); 
                }
                
                if ($this->_getParam('identifier')) {
                    $select->where('obj.identifier=?', $this->_getParam('identifier'));
                }
                else {
                    $select
                        ->where('obj.' . strtolower($this->_getParam('stype', 'via')) . '_id=?', $this->_getParam('sid'))
                        ->where('obj.module_id=?', $this->_getParam('mod'))
                        ->where('obj.matrix_counter=?', $this->_getParam('mid'))
                        ->where('obj.item_counter=?', $this->_getParam('iid'))
                        ->where('obj.acl_counter=?', $this->_getParam('aid'))
                    ;
                }
                if ($subscription = $this->db->fetchRow($select)) {
                    #Zend_Debug::Dump($subscription);
                    $reactivation_flag = ($this->_getParam('reactivate') && !$subscription->active);
                    
                    #ART
                    $manage = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST'), $this->community->name); // LIVE Is Production Environment Only
                    #$manage = new ViaMe_Vm_Payment_Authorize('LIVE', $this->community->name); // LIVE Is Production Environment Only
                    $trxn_data = array(
                        'subscriptionId' => $subscription->identifier
                    );
                    $trxn_result = $manage->transact('ARBGetSubscriptionStatusRequest', $trxn_data);
                    #Zend_Debug::Dump($trxn_result);
                    if ($trxn_result['status']) { // $trxn_result['res']['STATUS'] = Active | Canceled | Suspended
                        require_once dirname(__FILE__) . '/../../member/controllers/includes/members_form.php';
                        #$form->getElement('postal_code')->setRequired(true)->setAttrib('data-constraints', '@Required(label="postal code", message="The {label} field cannot be empty.", groups=[member_form])');
                        #$form->getElement('country')->setRequired(true)->setAttrib('data-constraints', '@Required(label="country", message="The {label} field cannot be empty.", groups=[member_form])');
                        // Remove Elements Early to Reduce Iteration
                        $form->removeElement('first_name');
                        $form->removeElement('last_name');
                        $form->removeElement('middle_name');
                        $form->removeElement('username');
                        $form->removeElement('email');
                        $form->removeElement('password');
                        $form->removeElement('password_confirm');
                        $form->removeElement('gender');
                        $form->removeElement('dob');
                        $form->removeElement('dob_month');
                        $form->removeElement('dob_day');
                        $form->removeElement('dob_year');
                        $form->removeElement('middle_name');
                        $form->removeElement('timezone');
                        $form->removeElement('currency');
                        $form->removeElement('language');
                        #$form->removeElement('postal_code');
                        #$form->removeElement('country');
                        
                        $form->getElement('postal_code')->setAttrib('data-constraints', '@Required(label="postal code", message="The {label} field cannot be empty.", groups=[member_form])');
                        $form->getElement('country')->setAttrib('data-constraints', '@Required(label="country", message="The {label} field cannot be empty.", groups=[member_form])');
                        
                        $form->removeAttrib('data-constraints');
                        $form->setAttrib('onsubmit', 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));');
                        
                        ###########################################
                        #$form = new Zend_Form(array(
                        #    'attribs' => array(
                        #        'name' => 'member_form',
                        #        'id' => 'member_form',
                        #        'class' => 'form regula-validation',
                        #        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));'
                        #    )
                        #));
                        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
                        
                        $form->setMethod('post');
                        $form->setAction('/acl/access/update_payment/');
                        
                        $form->addElement('Text', 'address1', array('label' => 'Address 1', 'required' => true, 'data-constraints' => '@Required(label="address", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 11));
                        $form->addElement('Text', 'address2', array('label' => 'Address 2', 'order' => 12));
                        $form->addElement('Text', 'city', array('label' => 'City', 'required' => true, 'data-constraints' => '@Required(label="city", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 13));
                        $form->addElement('Text', 'state', array('label' => 'State/Province', 'required' => true, 'data-constraints' => '@Required(label="state", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 14));
                        $form->addElement('Text', 'phone', array('label' => 'Phone', 'required' => true, 'class' => 'vmfh_phone', 'data-constraints' => '@Required(label="phone", message="The {label} field cannot be empty.", groups=[member_form]) @Pattern(regex=/^[\d\s\(\)\-\+\.]*$/, label="phone", message="The {label} should only contain numbers.", groups=[member_form])', 'order' => 100));
                        
                        $form->addElement('Select', 'cc_type', array('label' => 'Card Type', 'required' => true, 'MultiOptions' => array('' => '--- Card Type ---', 'Visa' => 'Visa', 'MasterCard' => 'MasterCard', 'Amex' => 'American Express', 'Discover' => 'Discover', 'Diners Club' => 'Diners Club', 'JCB' => 'JCB'), 'data-constraints' => '@Required(label="card type", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 101));
                        $form->addElement('Text', 'cc_num', array(
                            'label' => 'Card #',
                            'required' => true,
                            'data-constraints' => '@Pattern(regex=/[\s\d\-]/, label="card number", message="The {label} should only contain numbers.", groups=[member_form])',
                            'order' => 102
                        ));
                        $form->addElement('Select', 'cc_exp_month', array('label' => 'Exp. Date', 'required' => true, 'MultiOptions' => array('' => '--- Month ---', '01' => '01', '02' => '02', '03' => '03', '04' => '04', '05' => '05', '06' => '06', '07' => '07', '08' => '08', '09' => '09', '10' => '10', '11' => '11', '12' => '12'), 'data-constraints' => '@Required(label="card exp month", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 103));
                        $form->getElement('cc_exp_month')->addErrorMessage('Required.');
                        $next_years = array('' => '--- Year ---');
                        $this_year = date('Y');
                        for ($i = 0; $i <= 9; $i++) {
                            $next_years[(string) $this_year + $i] = (string) $this_year + $i;
                        }
                        $form->addElement('Select', 'cc_exp_year', array('required' => true, 'MultiOptions' => $next_years, 'data-constraints' => '@Required(label="card exp year", message="The {label} field cannot be empty.", groups=[member_form])', 'order' => 104));
                        $form->getElement('cc_exp_year')->addErrorMessage('Required.');
                        
                        /*
                        $form->addElement('Text', 'exponlymessage', array(
                            'value' => '<p><span class="netdown" style="font-weight: bold;">Note:</span> If you would like to keep your current payment method and only update the expiration date, choose values for month and year and disregard the other fields.</p>',
                            'order' => 110,
                            'ignore' => true
                        ));
                        $form->getElement('exponlymessage')->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE);
                        */
                        
                        $form->addElement('Text', 'cc_security_code', array(
                            'label' => 'Security Code',
                            'required' => true,
                            'maxlength' => 4,
                            'data-constraints' => '@Numeric(label="security code", message="The {label} can only contain numbers.", groups=[member_form])',
                            #'data-constraints' => '@Numeric(label="security code", ignoreEmpty="true",  message="The {label} can only contain numbers.", groups=[member_form])',
                            'order' => 120
                        ));
                        
                        $form->addDisplayGroup(array('cc_type', 'cc_num', 'cc_exp_month', 'cc_exp_year', 'exponlymessage', 'cc_security_code'), 'billing', array('legend' => 'Update Payment Information'));
                        $form->addDisplayGroup(array('address1', 'address2', 'city', 'state', 'postal_code', 'country', 'phone'), 'address', array('legend' => 'Update Billing Address : <input type="checkbox" name="need_to_update_billing_address"' . ($this->_getParam('need_to_update_billing_address') ? ' checked="checked"' : '') . '" onclick="' . "if (this.checked) { $('#fieldset-address dl:first').fadeIn(); regula.bind({elements: [document.getElementById('address1'), document.getElementById('city'), document.getElementById('state'), document.getElementById('postal_code'), document.getElementById('country'), document.getElementById('phone')]}); } else { $('#fieldset-address dl:first').fadeOut(); regula.unbind({elements: [document.getElementById('address1'), document.getElementById('city'), document.getElementById('state'), document.getElementById('postal_code'), document.getElementById('country'), document.getElementById('phone')]}); }" . '"> <span style="font-size: 12px; font-weight: normal;">Yes, I also need to update my billing address.</span>'));
                        $form->getDisplayGroup('address')->getDecorator('Fieldset')->setOption('escape', false);
                        
                        if (!$this->_getParam('need_to_update_billing_address')) {
                            $form->getElement('address1')->setRequired(false);
                            $form->getElement('city')->setRequired(false);
                            $form->getElement('state')->setRequired(false);
                            $form->getElement('postal_code')->setRequired(false);
                            $form->getElement('country')->setRequired(false);
                            $form->getElement('phone')->setRequired(false);
                            
                            $form->getDisplayGroup('address')->getDecorator('HtmlTag')->setOption('style', 'display: none;');
                            // Remove the regular binds on the address parts - Same as the uncheck action below on the checkbox
                            $this->view->inlineScript()->appendScript("YAHOO.util.Event.onDOMReady(function() { regula.unbind({elements: [document.getElementById('address1'), document.getElementById('city'), document.getElementById('state'), document.getElementById('postal_code'), document.getElementById('country'), document.getElementById('phone')]}); })");
                        }
                        
                        $form->addElement('Submit', 'submit', array('label' => 'Update Payment Information', 'class' => 'big green', 'ignore' => true, 'order' => 999));
                        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'class' => 'big', 'ignore' => true, 'order' => 1000, 'onClick' => "if (confirm('Are you sure you want to cancel?')) { document.getElementById('member_form').vivregval_canceled = true; return true; } else { return false; }"));
                        $form->addDisplayGroup(array('cancel', 'submit'), 'buttons');
                        
                        // Things that need to be done outside of the cache due to parameters
                        $form->addElement('Hidden', 'identifier', array('value' => $subscription->identifier));
                        
                        $form->addElement('Hidden', 'double_access_check', array('value' => 0));
                        $form->addElement('Hidden', 'reactivate', array('value' => $this->_getParam('reactivate')));
                        $form->addElement('Hidden', 'vmpd_npr', array('value' => 1));
                        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
                        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
                        $form->addDisplayGroup(array('double_access_check', 'reactivate', 'vmpd_npr', 'vmpd_nar', 'redirect'), 'hidden');
                        
                        $formErrors = array();
                        if ($this->getRequest()->isPost() && $form->isValidPartial($this->_getAllParams())) {
                            if ($this->_getParam('cc_type') && $this->_getParam('cc_num') && $this->_getParam('cc_exp_month') && $this->_getParam('cc_exp_year') && $this->_getParam('cc_security_code')) {
                                // Full Update - Do An Auth First
                                #$formErrors[] = 'Full Update';
                                $trxn_data = array(
                                    'transactionRequest' => array(
                                        'transactionType' => ($reactivation_flag ? 'authCaptureTransaction' : 'authOnlyTransaction'),
                                        #'amount' => ($reactivation_flag ? $subscription->member_amount : (($this->_getParam('cc_type') == 'Visa') ? 0 : 1)),
                                        #  Visa's Zero Dollar Auth requires address and zip so change to $1 auth
                                        'amount' => ($reactivation_flag ? $subscription->member_amount : 1),
                                        'payment' => array(
                                            'creditCard' => array(
                                                'cardNumber' => preg_replace('/[^\d]/', '', $this->_getParam('cc_num')),
                                                'expirationDate' => $this->_getParam('cc_exp_month') . '-' . $this->_getParam('cc_exp_year'),
                                                'cardCode' => preg_replace('/[^\d]/', '', $this->_getParam('cc_security_code'))
                                            ),
                                        ),
                                        #'customerIP' => $_SERVER['REMOTE_ADDR']
                                    )
                                );
                                if ($reactivation_flag) {
                                    $description = (isset($subscription->display) && $subscription->display ? $subscription->display : $subscription->title) . ' : ' . $subscription->profile_id . '-';
                                    if (isset($subscription->com_id) && $subscription->com_id) {
                                        $description .= 'COM-' . $subscription->com_id;
                                    }
                                    elseif (isset($subscription->net_id) && $subscription->net_id) {
                                        $description .= 'NET-' . $subscription->net_id;
                                    }
                                    elseif (isset($subscription->via_id) && $subscription->via_id) {
                                        $description .= 'VIA-' . $subscription->via_id;
                                    }
                                    $description .= '-' . $subscription->module_id . '-' . $subscription->matrix_counter . '-' . $subscription->item_counter . '-' . $subscription->acl_counter . '-REACTIVATE';
                                    
                                    $trxn_data['transactionRequest']['order']['invoiceNumber'] = date('YmdHis') . '-' . str_pad(rand(0, pow(10, 5) - 1), 5, '0', STR_PAD_LEFT);
                                    $trxn_data['transactionRequest']['order']['description'] = $description;
                                }
                                $trxn_data['transactionRequest']['customer']['id'] = $subscription->profile_id;
                                if (isset($subscription->m_email) && $subscription->m_email) {
                                    $trxn_data['transactionRequest']['customer']['email'] = $subscription->m_email;
                                }
                                if (isset($subscription->m_first_name) && $subscription->m_first_name) {
                                    $trxn_data['transactionRequest']['billTo']['firstName'] = $subscription->m_first_name;
                                }
                                if (isset($subscription->m_last_name) && $subscription->m_last_name) {
                                    $trxn_data['transactionRequest']['billTo']['lastName'] = $subscription->m_last_name;
                                }
                                // Insert for an address change too
                                if ($this->_getParam('need_to_update_billing_address')) {
                                    $trxn_data['transactionRequest']['billTo']['address'] = $this->_getParam('address1') . ($this->_getParam('address2') ? ' ' . $this->_getParam('address2') : '');
                                    $trxn_data['transactionRequest']['billTo']['city'] = $this->_getParam('city');
                                    $trxn_data['transactionRequest']['billTo']['state'] = $this->_getParam('state');
                                    $trxn_data['transactionRequest']['billTo']['zip'] = $this->_getParam('postal_code');
                                    $trxn_data['transactionRequest']['billTo']['country'] = $this->_getParam('country');
                                    $trxn_data['transactionRequest']['billTo']['phoneNumber'] = $this->_getParam('phone');
                                }
                                // Put the customerIP last - Does the order matter?  Looks like it.  I don't know
                                $trxn_data['transactionRequest']['customerIP'] = $_SERVER['REMOTE_ADDR'];
                                
                                #Zend_Debug::Dump($trxn_data);
                                $trxn_result = $manage->transact('createTransactionRequest', $trxn_data);
                                #Zend_Debug::Dump($trxn_result);
                                if ($trxn_result['status']) {
                                    // Auth Success - Update
                                    $trxn_data = array(
                                        'subscriptionId' => $subscription->identifier,
                                        'subscription' => array(
                                            'payment' => array(
                                                'creditCard' => array(
                                                    'cardNumber' => preg_replace('/[^\d]/', '', $this->_getParam('cc_num')),
                                                    'expirationDate' => $this->_getParam('cc_exp_month') . '-' . $this->_getParam('cc_exp_year'),
                                                    'cardCode' => preg_replace('/[^\d]/', '', $this->_getParam('cc_security_code'))
                                                ),
                                            )
                                        )
                                    );
                                    // Insert for an address change too
                                    if ($this->_getParam('need_to_update_billing_address')) {
                                        $trxn_data['subscription'] = array_merge($trxn_data['subscription'], array(
                                            //'customer' => array(
                                            //    'phoneNumber' => $this->_getParam('phone'),
                                            //),
                                            'billTo' => array(
                                                'address' => $this->_getParam('address1') . ($this->_getParam('address2') ? ' ' . $this->_getParam('address2') : ''),
                                                'city' => $this->_getParam('city'),
                                                'state' => $this->_getParam('state'),
                                                'zip' => $this->_getParam('postal_code'),
                                                'country' => $this->_getParam('country')
                                            )
                                        ));
                                    }
                                    #Zend_Debug::Dump($trxn_data);
                                    $trxn_result = $manage->transact('ARBUpdateSubscriptionRequest', $trxn_data);
                                    #Zend_Debug::Dump($trxn_result);
                                    if ($trxn_result['status']) {
                                        // Log the transaction
                                        $this->db->insert('log_trans', array(
                                            'identifier' => $subscription->identifier,
                                            'com_id' => $subscription->com_id,
                                            'net_id' => $subscription->net_id,
                                            'via_id' => $subscription->via_id,
                                            'module_id' => $subscription->module_id,
                                            'matrix_counter' => $subscription->matrix_counter,
                                            'item_counter' => $subscription->item_counter,
                                            'acl_counter' => $subscription->acl_counter,
                                            'profile_id' => $subscription->profile_id,
                                            'ip_address' => $_SERVER['REMOTE_ADDR'],
                                            'amount' => ($reactivation_flag ? $subscription->member_amount : 0),
                                            'referrer_profile_id' => $subscription->referrer_profile_id,
                                            'message' => ($reactivation_flag ? 'Subscription Reactivated - ' : '') . 'Payment Information Updated',
                                            'raw_result' => $trxn_result['raw'],
                                            'serialized_result' => serialize($trxn_result['res'])
                                        ));
                                        
                                        // Update New CC Exp
                                        $this->db->update('acl_members',
                                            array(
                                                'cc_exp_month' => $this->_getParam('cc_exp_month'),
                                                'cc_exp_year' => $this->_getParam('cc_exp_year')
                                            ),
                                            array(
                                                $this->db->quoteInto('com_id=?', $subscription->com_id),
                                                $this->db->quoteInto('net_id=?', $subscription->net_id),
                                                $this->db->quoteInto('via_id=?', $subscription->via_id),
                                                $this->db->quoteInto('module_id=?', $subscription->module_id),
                                                $this->db->quoteInto('matrix_counter=?', $subscription->matrix_counter),
                                                $this->db->quoteInto('item_counter=?', $subscription->item_counter),
                                                $this->db->quoteInto('acl_counter=?', $subscription->acl_counter),
                                                $this->db->quoteInto('profile_id=?', $subscription->profile_id),
                                                $this->db->quoteInto('identifier=?', $subscription->identifier)
                                            )
                                        );
                                        
                                        if ($reactivation_flag) {
                                            // Update the acl_member table
                                            $this->db->update('acl_members',
                                                array(
                                                    'active' => 't',
                                                    #'expiration' => new Zend_Db_Expr("expiration + (member_quantity || ' ' || member_interval)::interval")
                                                    'expiration' => new Zend_Db_Expr("'now'::timestamp + (member_quantity || ' ' || member_interval)::interval")
                                                ),
                                                array(
                                                    $this->db->quoteInto('com_id=?', $subscription->com_id),
                                                    $this->db->quoteInto('net_id=?', $subscription->net_id),
                                                    $this->db->quoteInto('via_id=?', $subscription->via_id),
                                                    $this->db->quoteInto('module_id=?', $subscription->module_id),
                                                    $this->db->quoteInto('matrix_counter=?', $subscription->matrix_counter),
                                                    $this->db->quoteInto('item_counter=?', $subscription->item_counter),
                                                    $this->db->quoteInto('acl_counter=?', $subscription->acl_counter),
                                                    $this->db->quoteInto('profile_id=?', $subscription->profile_id),
                                                    $this->db->quoteInto('identifier=?', $subscription->identifier)
                                                )
                                            );
                                            
                                            // Make sure they are in the mailing database still
                                            if (isset($subscription->greenarrow_list_id) && $subscription->greenarrow_list_id) {
                                                try {
                                                    require_once $this->vars->APP_PATH . "/library/Other/GreenArrowStudioAPI.php";
                                                    $GA_API = new GreenArrowStudioAPI();
                                                    
                                                    $result_add = $GA_API->call_method('subscriberAdd', Array(
                                                		'email'         => $this->member->email,
                                                		'listid'        => $subscription->greenarrow_list_id,
                                                		'reactivate'    => 1,
                                                		'requestip'     => $_SERVER['REMOTE_ADDR']
                                                	));
                                                	
                                                	// Unsubscribe SCNEO Members From SCN Regular (5) and SCN Old (6)
                                                	if ($subscription->greenarrow_list_id == 28) {
                                                	    $result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                                                    		'email'         => $this->member->email,
                                                    		'listid'        => 5,
                                                    		'requestip'     => $_SERVER['REMOTE_ADDR']
                                                    	));
                                                    	$result_unsub = $GA_API->call_method('subscriberUnsubscribe', Array(
                                                    		'email'         => $this->member->email,
                                                    		'listid'        => 6,
                                                    		'requestip'     => $_SERVER['REMOTE_ADDR']
                                                    	));
                                                	}
                                                } catch (Exception $e) { }
                                            }
                                        }
                                        
                                        // Update postal and phone in member_members
                                        $this->db->update('member_members',
                                            array(
                                                'postal_code' => new Zend_Db_Expr($this->db->quoteInto("COALESCE(NULLIF(?, ''), postal_code)", $this->_getParam('postal_code'))),
                                                'phone' => new Zend_Db_Expr($this->db->quoteInto("COALESCE(NULLIF(?, ''), phone)", $this->_getParam('phone')))
                                            ),
                                            array(
                                                $this->db->quoteInto('id=?', $this->member->id)
                                            )
                                        );
                                        
                                        // Send Email Notification
                                        
                                        // Display success
                                        echo $this->view->CM(array(
                                            'class' => 'cm decorated plain successmessage',
                                            'hd' => 'Payment Information Updated',
                                            'hd2' => 'You Have Successfully Updated Your Payment Information',
                                            'bd' => '<p class="success">Your update request has been completed.</p><p>Your request to update the payment information on file for your subscription has been successfully completed.</p>'
                                        ));
                                        return $this->_helper->viewRenderer->setNoRender();
                                    }
                                    elseif (isset($trxn_result['errstr']) && $trxn_result['errstr']) {
                                        $formErrors[] = $trxn_result['errstr'] . ((isset($trxn_result['errno']) && $trxn_result['errno']) ? ' (Code: ' . $trxn_result['errno'] . ')' : '');
                                    }
                                    else {
                                        $formErrors[] = 'There was a problem updating your payment information.  Please try again.';
                                    }
                                }
                                elseif (isset($trxn_result['errstr']) && $trxn_result['errstr']) {
                                    $formErrors[] = $trxn_result['errstr'] . ((isset($trxn_result['errno']) && $trxn_result['errno']) ? ' (Code: ' . $trxn_result['errno'] . ')' : '');
                                }
                                else {
                                    $formErrors[] = 'There was a problem with the credit card information you have entered.  Please try again.';
                                }
                            }
                            /*
                            elseif (!$this->_getParam('cc_type') && !$this->_getParam('cc_num') && $this->_getParam('cc_exp_month') && $this->_getParam('cc_exp_year') && !$this->_getParam('cc_security_code')) {
                                // Update CC Expiration Only
                                $formErrors[] = 'Update CC Expiration Only';
                            }
                            */
                            else {
                                $formErrors[] = 'Incorrect number of data parameters entered.  Either enter all of the form fields to update the payment information, or, to keep the payment information and only update the expiration date, leave everything blank except for the month and year fields.';
                            }
                        }
                        
                        if (isset($formErrors) && count($formErrors)) {
                            echo '<ul class="errors" style="margin-bottom: 1em;">';
                            foreach ($formErrors as $message) {
                                echo "<li>$message</li>\n";
                            }
                            echo '</ul>';
                        }
                        echo '<h1 style="font-size: 220%;">' . (isset($subscription->display) && $subscription->display ? $subscription->display : $subscription->title) . '</h1>';
                        
                        if ($reactivation_flag) {
                            $currency = new ViaMe_Currency('USD');
                            echo '<h2>You will be immediately charged ' . $currency->toCurrency($subscription->member_amount) . ' to reactivate your subscription.</h2>';
                        }
                        
                        echo $form;
                    }
                    else {
                        echo $this->view->CM(array(
                            'class' => 'cm decorated plain errormessage',
                            'hd' => 'Update Payment Information Error',
                            'hd2' => 'ARB Subscription Not Found',
                            'bd' => '<p class="error">An error has occurred. Your subscription could not be found.</p><p>A valid subscription for you with the submitted parameters could not be found.</p><p>Please use the correct link with subscription parameters to successfully update your payment information. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems updating your information, please contact our customer service department.</p>'
                        ));
                    }
                }
                else {
                    echo $this->view->CM(array(
                        'class' => 'cm decorated plain errormessage',
                        'hd' => 'Update Payment Information Error',
                        'hd2' => 'DB Subscription Not Found',
                        'bd' => '<p class="error">An error has occurred. Your subscription could not be found.</p><p>A valid subscription for you with the submitted parameters could not be found.</p><p>Please use the correct link with subscription parameters to successfully update your payment information. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems updating your information, please contact our customer service department.</p>'
                    ));
                }
            }
            else {
                echo $this->view->CM(array(
                    'class' => 'cm decorated plain errormessage',
                    'hd' => 'Input Error',
                    'hd2' => 'Subscription Required',
                    'bd' => '<p class="error">An error has occurred. Subscription information required.</p><p>Parameters that are required for us to find the product you are subscribed to were not supplied and this transaction cannot proceed any further.</p><p>Please use the correct link with subscription parameters to successfully update your payment information. Hit the <a href="javascript:history.back();">back button</a> on your browser and try your request again.</p><p>If you continue to have problems updating your information, please contact our customer service department.</p>'
                ));
            }
        }
        else {
            return $this->_redirect('/member/login/?redirect=' . urlencode($this->getFrontController()->getRequest()->getServer('REQUEST_URI')));
        }
        
        return $this->_helper->viewRenderer->setNoRender();
    }
}