<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Acl_Webhook_PaypalController extends ViaMe_Controller_Action
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
        // Exit quickly for nothing
        #if (!count($_POST)) {
        #    #return $this->_forward('error', 'error', 'default', array('errorcode' => 400, 'title' => $title, 'message' => $message));
        #    return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
        #}
        // Exit quickly for nothing
        
        
        $paypal = new ViaMe_Vm_Payment_PayPal(($this->config->debug == 0 ? 'LIVE' : 'TEST')); // LIVE Is Production Environment Only
        $paypal->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
        $result = $paypal->webhook();
        
        // Log it
        #$this->db->insert('log_webhooks', array(
        #    'identifier' => 'paypal (' . ($this->config->debug == 0 ? 'LIVE' : 'TEST') . ')',
        #    'ip_address' => $_SERVER['REMOTE_ADDR'],
        #    'serialized_get' => serialize($_GET),
        #    'serialized_post' => serialize($_POST),
        #    'serialized_result' => serialize($result),
        #    'active' => ($result['status'] ? 't' : 'f')
        #));
        
        $db_commits = true;
        $trans_title = 'Unknown';
        
        // Anything to do??
        #if ($result['status']) {
        if (false) {
        
            // Anything done should be transaction
            $this->db->beginTransaction();
            try {
            
            
                if ($this->_getParam('txn_type') == 'cart') {
                    // One time payment.  Nothing to do.  All done in access controller.
                    $trans_title = 'Direct Payment';
                }
                elseif ($this->_getParam('txn_type') == 'recurring_payment' && $this->_getParam('payment_status') == 'Completed' && $this->_getParam('rp_invoice_id')) {
                    $trans_title = 'Recurring Payment';
                    
                    $tokens = explode('-', $this->_getParam('rp_invoice_id')); // profile_id, space_type, space_id, module_id, matrix_counter, item_counter, access_counter
                    
                    // Log Trans First
                    $this->db->insert('log_trans', array(
                        strtolower($tokens[1]) . '_id' => $tokens[2],
                        'module_id' => $tokens[3],
                        'matrix_counter' => $tokens[4],
                        'item_counter' => $tokens[5],
                        'acl_counter' => $tokens[6],
                        'profile_id' => $tokens[0],
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'amount' => $this->_getParam('mc_gross'),
                        'message' => 'Recurring Billing (' . trim($this->_getParam('period_type')) . ')',
                        'serialized_result' => serialize($result)
                    ));
                    
                    if (trim($this->_getParam('period_type')) == 'Trial') {
                        $trans_title .= ' - Trial Period';
                        
                        // Nothing to do.  All done in access controller. - Expiration Already Pre Set - Dont have trial intervals in acl_members (its in acl_acl)
                        
                        // Check integrity of the expiration date against ["next_payment_date"] ??
                        // Send Email? - Use first_name, last_name, payer_email from post
                    }
                    elseif (trim($this->_getParam('period_type')) == 'Regular') {
                        $trans_title .= ' - Regular Period';
                        
                        // Update Access Expiration For Active Recurring Subscriptions
                        $this->db->update('acl_members',
                            array(
                                'expiration' => new Zend_Db_Expr("expiration + (member_quantity || ' ' || member_interval)::interval")
                            ),
                            array(
                                $this->db->quoteInto(strtolower($tokens[1]) . '_id=?', $tokens[2]),
                                $this->db->quoteInto('module_id=?', $tokens[3]),
                                $this->db->quoteInto('matrix_counter=?', $tokens[4]),
                                $this->db->quoteInto('item_counter=?', $tokens[5]),
                                $this->db->quoteInto('acl_counter=?', $tokens[6]),
                                $this->db->quoteInto('profile_id=?', $tokens[0]),
                                #$this->db->quoteInto('member_auto_renew=?', 't'),
                                #$this->db->quoteInto('active=?', 't')
                            )
                        );
                        
                        // Check integrity of the expiration date against ["next_payment_date"] ??
                        // Send Email? - Use first_name, last_name, payer_email from post
                    }
                    
                }
                elseif ($this->_getParam('txn_type') == 'recurring_payment_profile_created') {
                    // New recurring transaction profile created.  Nothing to do.  All done in access controller.
                    $trans_title = 'Recurring Payment Profile Created';
                }
                elseif ($this->_getParam('txn_type') == 'recurring_payment_expired' && $this->_getParam('rp_invoice_id')) {
                    // Non-auto-renew profiles: first and last payment...  Can occur before the actual billing
                    $trans_title = 'Recurring Payment Expired';
                    
                    // Subscription Is Over
                    
                    $tokens = explode('-', $this->_getParam('rp_invoice_id')); // profile_id, space_type, space_id, module_id, matrix_counter, item_counter, access_counter
                    
                    // Log Trans First
                    $this->db->insert('log_trans', array(
                        strtolower($tokens[1]) . '_id' => $tokens[2],
                        'module_id' => $tokens[3],
                        'matrix_counter' => $tokens[4],
                        'item_counter' => $tokens[5],
                        'acl_counter' => $tokens[6],
                        'profile_id' => $tokens[0],
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'amount' => 0,
                        'message' => 'Recurring Payment Expired',
                        'serialized_result' => serialize($result)
                    ));
                    
                    $this->db->update('acl_members',
                        array(
                            'active' => 'f'
                        ),
                        array(
                            $this->db->quoteInto(strtolower($tokens[1]) . '_id=?', $tokens[2]),
                            $this->db->quoteInto('module_id=?', $tokens[3]),
                            $this->db->quoteInto('matrix_counter=?', $tokens[4]),
                            $this->db->quoteInto('item_counter=?', $tokens[5]),
                            $this->db->quoteInto('acl_counter=?', $tokens[6]),
                            $this->db->quoteInto('profile_id=?', $tokens[0]),
                            #$this->db->quoteInto('member_auto_renew=?', 't'),
                            $this->db->quoteInto('active=?', 't')
                        )
                    );
                }
                elseif ($this->_getParam('txn_type') == 'recurring_payment_profile_cancel' && $this->_getParam('rp_invoice_id')) {
                    $trans_title = 'Recurring Payment Profile Cancelled';
                    
                    // Cancelled Subscription
                    
                    $tokens = explode('-', $this->_getParam('rp_invoice_id')); // profile_id, space_type, space_id, module_id, matrix_counter, item_counter, access_counter
                    
                    // Log Trans First - Already logged in the unsubscribe controller
                    $this->db->insert('log_trans', array(
                        strtolower($tokens[1]) . '_id' => $tokens[2],
                        'module_id' => $tokens[3],
                        'matrix_counter' => $tokens[4],
                        'item_counter' => $tokens[5],
                        'acl_counter' => $tokens[6],
                        'profile_id' => $tokens[0],
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'amount' => 0,
                        'message' => 'Subscription Cancellation Confirmed',
                        'serialized_result' => serialize($result)
                    ));
                    
                    $this->db->update('acl_members',
                        array(
                            'active' => 'f'
                        ),
                        array(
                            $this->db->quoteInto(strtolower($tokens[1]) . '_id=?', $tokens[2]),
                            $this->db->quoteInto('module_id=?', $tokens[3]),
                            $this->db->quoteInto('matrix_counter=?', $tokens[4]),
                            $this->db->quoteInto('item_counter=?', $tokens[5]),
                            $this->db->quoteInto('acl_counter=?', $tokens[6]),
                            $this->db->quoteInto('profile_id=?', $tokens[0]),
                            #$this->db->quoteInto('member_auto_renew=?', 't'),
                            $this->db->quoteInto('active=?', 't')
                        )
                    );
                }
                elseif ($this->_getParam('payment_status') == 'Refunded' && ($this->_getParam('rp_invoice_id', $this->_getParam('invoice')))) {
                    $trans_title = 'Refund';
                    
                    // Refunds
                    $tokens = explode('-', $this->_getParam('rp_invoice_id', $this->_getParam('invoice'))); // profile_id, space_type, space_id, module_id, matrix_counter, item_counter, access_counter
                    
                    // Log Trans First - Already logged in the unsubscribe controller
                    $this->db->insert('log_trans', array(
                        strtolower($tokens[1]) . '_id' => $tokens[2],
                        'module_id' => $tokens[3],
                        'matrix_counter' => $tokens[4],
                        'item_counter' => $tokens[5],
                        'acl_counter' => $tokens[6],
                        'profile_id' => $tokens[0],
                        'ip_address' => $_SERVER['REMOTE_ADDR'],
                        'amount' => $this->_getParam('mc_gross'),
                        'message' => 'Payment Refunded',
                        'serialized_result' => serialize($result)
                    ));
                    
                    if ($this->_getParam('invoice')) {
                        $trans_title .= ' - Direct Payment';
                        
                        // Refunds on carts cancel access immediately
                        $this->db->update('acl_members',
                            array(
                                'active' => new Zend_Db_Expr('null')
                            ),
                            array(
                                $this->db->quoteInto(strtolower($tokens[1]) . '_id=?', $tokens[2]),
                                $this->db->quoteInto('module_id=?', $tokens[3]),
                                $this->db->quoteInto('matrix_counter=?', $tokens[4]),
                                $this->db->quoteInto('item_counter=?', $tokens[5]),
                                $this->db->quoteInto('acl_counter=?', $tokens[6]),
                                $this->db->quoteInto('profile_id=?', $tokens[0])
                            )
                        );
                    }
                }
                
                
                // Done with transaction
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                
                $this->log->EMERG('Webhook updates failed!');
                
                $db_commits = false;
            }
        }
        
        
        
        // Send Email to Arthur
        $temp = $this->view->partial('webhook/email/dump.phtml', array('post' => $_POST, 'get' => $_GET, 'params' => $this->_getAllParams(), 'result' => $result, 'title' => $trans_title));
        $this->_helper->ViaMe->sendEmail('akang@levelogic.com', 'AK', 'PayPal Webhook Dump' . ($this->config->debug == 0 ? ' (LIVE)' : ' (TEST)') . ' - ' . ($result['status'] && $db_commits ? 'Verified and Completed' : 'FAILURE'), $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        
        
        
        #if ($result['status'] && $db_commits) {
        #    // Message confirmed - send simple 200 status
            echo 'Thank you.';
            return $this->_helper->viewRenderer->setNoRender();
        #}
        #else {
        #    // Message not confirmed or other error in db transaction so send non-200 status
        #    return $this->_forward('error', 'error', 'default', array('errorcode' => 500));
        #}
    }
}


/*

receiver_email
receiver_id

txn_id
txt_type
recurring_payment_id
payer_email
first_name
last_name

adjustment
cart
express_checkout
masspay
mp_signup
merch_pmt
new_case
recurring_payment
recurring_payment_expired
recurring_payment_profile_created
recurring_payment_skipped
recurring_payment_profile_cancel
send_money
subscr_cancel
subscr_eot
subscr_failed
subscr_modify
subscr_payment
subscr_signup
virtual_terminal
web_accept




["mc_gross"] => string(4) "1.22"
["amount"] => string(4) "1.22"
["period_type"] => string(6) " Trial"
["payment_status"] => string(9) "Completed"

["recurring_payment_id"] => string(14) "I-NMFC2A99NSH1"
["next_payment_date"] => string(25) "02:00:00 Dec 13, 2012 PST"

["first_name"] => string(6) "Arthur"
["payer_email"] => string(23) "akang+tp3@levelogic.com"
["payment_type"] => string(7) "instant"
["last_name"] => string(4) "Kang"
["rp_invoice_id"] => string(18) "4335-NET-2-0-0-0-7"
["txn_id"] => string(17) "1Y403509672985223"
["txn_type"] => string(17) "recurring_payment"
["profile_status"]
*/