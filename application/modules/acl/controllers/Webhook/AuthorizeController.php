<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Acl_Webhook_AuthorizeController extends ViaMe_Controller_Action
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
        if (!count($_POST)) {
            return $this->_forward('error', 'error', 'default', array('errorcode' => 403));
        }
        else {
            ignore_user_abort(true);
            set_time_limit(120);
        }
        // Exit quickly for nothing
        
        
        $webhook = new ViaMe_Vm_Payment_Authorize(($this->config->debug == 0 ? 'LIVE' : 'TEST'), $this->community->name); // LIVE Is Production Environment Only
        $webhook->setSoftdescriptor($this->community->display ? $this->community->display : $this->community->name);
        $result = $webhook->webhook();
        
        // Log it
        $this->db->insert('log_webhooks', array(
            'identifier' => 'authorize (' . ($this->config->debug == 0 ? 'LIVE' : 'TEST') . ')',
            'ip_address' => $_SERVER['REMOTE_ADDR'],
            'serialized_get' => serialize($_GET),
            'serialized_post' => serialize($_POST),
            #'serialized_result' => serialize($result),
            #'active' => ($result['status'] ? 't' : 'f')
        ));
        
        $db_commits = true;
        $trans_title = 'Unknown';
        
        
        // Anything to do??
        if ($result['status'] || true) {
            
            // Anything done should be transaction
            $this->db->beginTransaction();
            try {
                
                if ($this->_getParam('x_subscription_id') && $this->_getParam('x_subscription_paynum')) {
                    // ARB Processes
                    $trans_title = 'ARB';
                    
                    if ($this->_getParam('x_type') == 'auth_capture') {
                        // Recurring Billing
                        $trans_title .= ' - Auth Capture';
                        
                        $select = $this->db->select()
                            ->from(array('obj' => 'acl_members'))
                            ->where('identifier=?', $this->_getParam('x_subscription_id'))
                            ->join(array('a' => 'acl_acls'), 'obj.com_id=a.com_id AND obj.net_id=a.net_id AND obj.via_id=a.via_id AND obj.module_id=a.module_id AND obj.matrix_counter=a.matrix_counter AND obj.item_counter=a.item_counter AND obj.acl_counter=a.counter',
                                array(
                                    'title'
                                )
                            )
                            ->limit(1);
                        
                        if ($member = $this->db->fetchRow($select)) {
                            $trans_title .= ' - Subscription Found';
                            
                            if ($this->_getParam('x_response_code') == '1') {
                                $trans_title .= ' - Success';
                                // Probably need to increment subscription if auto renew

                                // Log Trans First
                                $this->db->insert('log_trans', array(
                                    'com_id' => $member->com_id,
                                    'net_id' => $member->net_id,
                                    'via_id' => $member->via_id,
                                    'module_id' => $member->module_id,
                                    'matrix_counter' => $member->matrix_counter,
                                    'item_counter' => $member->item_counter,
                                    'acl_counter' => $member->acl_counter,
                                    'profile_id' => $member->profile_id,
                                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                                    'amount' => $this->_getParam('x_amount'),
                                    'referrer_profile_id' => $member->referrer_profile_id,
                                    'message' => 'Recurring Billing',
                                    'identifier' => $this->_getParam('x_trans_id'),
                                    'serialized_result' => serialize($result)
                                ));
                                
                                // Update Access Expiration For Active Recurring Subscriptions
                                //   Expirations that land on 28 or later, adjust to last day of the expiration month
                                $this->db->update('acl_members',
                                    array(
                                        #'expiration' => new Zend_Db_Expr("expiration + (member_quantity || ' ' || member_interval)::interval")
                                        'expiration' => new Zend_Db_Expr("expiration + (member_quantity || ' ' || member_interval)::interval + ((CASE WHEN member_interval='Month' AND date_part('DAY', expiration + (member_quantity || ' ' || member_interval)::interval) >= 28 THEN (31 - date_part('DAY', expiration + (member_quantity || ' ' || member_interval)::interval)) ELSE 0 END) || ' Days')::interval")
                                    ),
                                    array(
                                        $this->db->quoteInto('com_id=?', $member->com_id),
                                        $this->db->quoteInto('net_id=?', $member->net_id),
                                        $this->db->quoteInto('via_id=?', $member->via_id),
                                        $this->db->quoteInto('module_id=?', $member->module_id),
                                        $this->db->quoteInto('matrix_counter=?', $member->matrix_counter),
                                        $this->db->quoteInto('item_counter=?', $member->item_counter),
                                        $this->db->quoteInto('acl_counter=?', $member->acl_counter),
                                        $this->db->quoteInto('profile_id=?', $member->profile_id),
                                        #$this->db->quoteInto('member_auto_renew=?', 't'),
                                        #$this->db->quoteInto('active=?', 't')
                                    )
                                );
                                
                                // Check integrity of the expiration date against ["next_payment_date"] ??
                                // Send Email? - Use first_name, last_name, payer_email from post
                                
                            }
                            else {
                                // Probably need to cancel subscription - some type of failure
                                $trans_title .= ' - FAIL';
                                
                                // Log Trans First
                                $this->db->insert('log_trans', array(
                                    'com_id' => $member->com_id,
                                    'net_id' => $member->net_id,
                                    'via_id' => $member->via_id,
                                    'module_id' => $member->module_id,
                                    'matrix_counter' => $member->matrix_counter,
                                    'item_counter' => $member->item_counter,
                                    'acl_counter' => $member->acl_counter,
                                    'profile_id' => $member->profile_id,
                                    'ip_address' => $_SERVER['REMOTE_ADDR'],
                                    'amount' => 0,
                                    'referrer_profile_id' => $member->referrer_profile_id,
                                    'message' => 'Recurring Billing Failure',
                                    'identifier' => $this->_getParam('x_trans_id'),
                                    'serialized_result' => serialize($result)
                                ));
                                
                                $this->db->update('acl_members',
                                    array(
                                        'active' => 'f'
                                    ),
                                    array(
                                        $this->db->quoteInto('com_id=?', $member->com_id),
                                        $this->db->quoteInto('net_id=?', $member->net_id),
                                        $this->db->quoteInto('via_id=?', $member->via_id),
                                        $this->db->quoteInto('module_id=?', $member->module_id),
                                        $this->db->quoteInto('matrix_counter=?', $member->matrix_counter),
                                        $this->db->quoteInto('item_counter=?', $member->item_counter),
                                        $this->db->quoteInto('acl_counter=?', $member->acl_counter),
                                        $this->db->quoteInto('profile_id=?', $member->profile_id),
                                        #$this->db->quoteInto('member_auto_renew=?', 't'),
                                        #$this->db->quoteInto('active=?', 't')
                                    )
                                );
                                
                                // Should probably send an email or something notifying with options to get back
                                if ($this->_getParam('x_email')) {
                                    $to_name = $this->_getParam('x_first_name', '');
                                    if ($this->_getParam('x_last_name')) {
                                        if ($to_name) { $to_name .= ' '; }
                                        $to_name .= $this->_getParam('x_last_name');
                                    }
                                    $temp = $this->view->partial('webhook/email/recurring_failed.phtml', array('post' => $_POST, 'get' => $_GET, 'member' => $member, 'internal' => $this->internal));
                                    $this->_helper->ViaMe->sendEmail($this->_getParam('x_email'), $to_name, $member->title . ' - Recurring Billing Attempt Failed', $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
                                    
                                    $trans_title .= ' - Email Sent';
                                }
        
                            }
                        }
                        else {
                            $trans_title .= ' - Subscription Not Found';
                        }
                    }
                }
                else {
                    // Non-ARB Processes
                    $trans_title = 'REG';
                    
                    /* Need to break apart x_description */
                    #SCN Elite Opportunity (Quarterly) : 10986-VIA-21066-0-0-0-3-I
                    
                    if ($this->_getParam('x_type') == 'auth_only') {
                        // Nothing to do.  All done in access controller.
                        $trans_title .= ' - Auth Only';
                    }
                    elseif ($this->_getParam('x_type') == 'auth_capture') {
                        // Nothing to do.  All done in access controller.
                        $trans_title .= ' - Auth Capture';
                    }
                    elseif ($this->_getParam('x_type') == 'void') {
                        // Voided Transactions
                        $trans_title .= ' - Void';
                    }
                    elseif ($this->_getParam('x_type') == 'credit') {
                        // Refunds
                        $trans_title .= ' - Refund';
                        
                        if ($this->_getParam('x_response_code') == 1) {
	                        // Log Trans
	                        /*
                            $this->db->insert('log_trans', array(
                                'com_id' => $member->com_id,
                                'net_id' => $member->net_id,
                                'via_id' => $member->via_id,
                                'module_id' => $member->module_id,
                                'matrix_counter' => $member->matrix_counter,
                                'item_counter' => $member->item_counter,
                                'acl_counter' => $member->acl_counter,
                                'profile_id' => $member->profile_id,
                                'ip_address' => $_SERVER['REMOTE_ADDR'],
                                'amount' => $this->_getParam('x_amount') * -1,
                                'referrer_profile_id' => $member->referrer_profile_id,
                                'message' => 'Refund',
                                'identifier' => $this->_getParam('x_trans_id'),
                                'serialized_result' => serialize($result)
                            ));
                            */
	                    }
                    }
                    
                    
                    // Approved or Declined
                    if ($this->_getParam('x_response_code') == 1) {
                        $trans_title .= ' - Approved';
                    }
                    else {
                        $trans_title .= ' - Declined';
                    }
                }
                

                
                /*
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
                */
                
                
                // Done with transaction
                $this->db->commit();
            } catch (Exception $e) {
                $this->db->rollBack();
                
                $this->log->EMERG('Webhook updates failed!');
                
                $db_commits = false;
                
                $trans_title .= ' - Updates Failed';
            }
        }
        
        
        
        // Send Email to Arthur
        $temp = $this->view->partial('webhook/email/dump.phtml', array('post' => $_POST, 'get' => $_GET, 'result' => $result, 'title' => $trans_title));
        $this->_helper->ViaMe->sendEmail('akang@levelogic.com', 'AK', 'Authorize Webhook Dump' . ($this->config->debug == 0 ? ' (LIVE)' : ' (TEST)') . ' - ' . (isset($result['status']) && $result['status'] ? 'Verified' : 'Unverified') . ($db_commits ? ' - Completed' : ' - Not Completed') . " : $trans_title", $temp, null, ($this->community->noreply_email ? $this->community->noreply_email : null), $this->community->display);
        
        
        
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