<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Pick_CloseController extends ViaMe_Controller_Default_Delete
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    //protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    //protected $_minPrivilege = self::ACL_INTERACT;
    //protected $_defaultAllow = true;
    //protected $_mustBeOwner = true;
    
    protected $_tableName = 'pick_picks';
    
    
    public function indexAction() {
        $this->view->headTitle('Close Pick', 'PREPEND');
        
        // Change Sub Layout
        #$this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        // Ineligible Picks to be closed
        if (
            ($this->_modObject->close_datestamp) ||
            ($this->_modObject->close_temp_price) ||
            ($this->_modObject->close_price)
            ) {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Close Pick',
                'hd2' => 'Invalid pick to close',
                'bd' => '<p class="error">That pick has already been closed.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->internal->target->pre . '/pick/p/mid/' . $this->_getParam('mid') . '/') . '">Continue &raquo;</a></p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
        elseif (!$this->_modObject->open_price) {
            echo $this->view->CM(array(
                'class' => 'cm decorated plain errormessage',
                'hd' => 'Close Pick',
                'hd2' => 'Cannot close that pick at this time',
                'bd' => '<p class="error">That pick is still in a pending state and cannot be closed.</p><p>Please wait for the pick to be updated to try your request again.</p><p><a href="' . ($this->_getParam('redirect') ? $this->_getParam('redirect') : $this->internal->target->pre . '/pick/p/mid/' . $this->_getParam('mid') . '/') . '">Continue &raquo;</a></p>'
            ));
            return $this->_helper->viewRenderer->setNoRender();
        }
            
        if (($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel'))) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');
        $form->setOptions(array(
            'attribs' => array(
                'name' => 'pick_close_form',
                'method' => 'post',
                'id' => 'pick_close_form',
                'class' => 'form'
            ),
            'elementPrefixPath' => array(
                'prefix' => 'ViaMe',
                'path' => 'ViaMe/'
            ),
            'elements' => array(
                'allocation' => array('Select', array(
                'label' => 'Allocation',
                'description' => 'Total allocation %',
                'required' => true,
                'validators' => array(
                    array('Between', false, array(1, 100))
                ),
                'order' => 5
            )),
            )
        ));
        
        $form->addDisplayGroup(array('allocation'), 'main');
        
        $form->addElement('Submit', 'submit', array('label' => 'Close Pick', 'ignore' => true, 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'reset', 'cancel'), 'buttons');
        
        // Redirects
        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_nar', 'redirect'), 'hidden');
        
        $max_alloc = $this->_modObject->allocation;
        for ($i = 1; $i <= $max_alloc; $i++) {
            $alloc_fill[$i] = $i;
        }
        $form->getElement('allocation')->setMultiOptions($alloc_fill)->setValidators(array(array('Between', false, array(1, $max_alloc))))->setValue($max_alloc);
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $symbol = $this->db->fetchOne("SELECT delayed_symbol FROM quote_view_symbol_matrix WHERE id=?", $this->_modObject->symbol_id);
            
            $quotes = new ViaMe_Vm_Quotes();
            $quote = $quotes->fetch($symbol, 'l1');
            $quoteRT = $quotes->fetchRT($symbol);
            #Zend_Debug::Dump($quoteRT);
            $dt_now = new DateTime('now');
            $dt_open = new DateTime('now');
              $dt_open->setTime(06, 30, 00);
            $dt_close = new DateTime('now');
              $dt_close->setTime(13, 00, 00);
              
            $data = array();
            
            if ($quote[0][0] && $quote[0][0] != 'N/A') {
                $close_price_to_use = $quote[0][0];
                $close_field_to_use = 'close_temp_price';
                
                if (
                    $quoteRT && isset($quoteRT[0]['l']) && $quoteRT[0]['l'] && $quoteRT[0]['l'] >= .01 && isset($quoteRT[0]['volume']) && $quoteRT[0]['volume'] > 0 &&
                    (date('N') >= 1 && date('N') < 6) &&
                    ($dt_now >= $dt_open && $dt_now <= $dt_close)
                ) {
                    $close_price_to_use = $quoteRT[0]['l'];
                    $close_field_to_use = 'close_price';
                }
                        
                        
                $this->db->beginTransaction();
                try {
                    if ($this->_getParam('allocation') != $this->_modObject->allocation) {
                        // Create Partial Pick
                        $new_pick = array();
                        foreach (array('creation', 'modified', 'module_id', 'com_id', 'net_id', 'via_id', 'matrix_counter', 'title', 'heading', 'summary', 'more_link', 'meta_title', 'meta_description', 'meta_keywords', 'content', 'symbol_id', 'position', 'risk', 'target_date', 'target_price', 'timeframe', 'suggested_stop_loss', 'notes', 'holding', 'disclosure', 'open_datestamp', 'open_temp_price', 'open_price', 'activation', 'expiration', 'show_on_fail', 'updated', 'active', 'quip', 'allow_comments', 'allow_ratings') as $temp) {
                            if (isset($this->_modObject->{$temp}) && ($this->_modObject->{$temp} !== null)) {
                                if ($this->_modObject->{$temp} === true) {
                                    $new_pick[$temp] = 1;
                                }
                                elseif ($this->_modObject->{$temp} === false) {
                                    $new_pick[$temp] = 0;
                                }
                                else {
                                    $new_pick[$temp] = $this->_modObject->{$temp};
                                }
                            }
                        }
                        
                        $new_pick['profile_id'] = $this->member->profile->id;
                        $new_pick['close_datestamp'] = new Zend_Db_Expr("'now'");
                        $new_pick[$close_field_to_use] = $close_price_to_use;
                        $new_pick['allocation'] = $this->_getParam('allocation');
                        $new_pick['partial_close_parent_id'] = $this->_modObject->counter;
                        $this->db->insert($this->_tableName, $new_pick);
                        
                        $data['allocation'] = $this->_modObject->allocation - $this->_getParam('allocation');
                    }
                    else {
                        $data['close_datestamp'] = new Zend_Db_Expr("'now'");
                        $data[$close_field_to_use] = $close_price_to_use;
                    }
                    
                    $where = array();
                    $where[] = $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid', 0));
                    $where[] = $this->db->quoteInto('counter=?', $this->_getParam('id', 0));
                    $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);            
                    
                    
                    $this->db->update(
                        $this->_tableName,
                        $data,
                        $where
                    );
                    
                    
                    // Close Completed
                    $this->db->commit();
                    $this->log->ERR('_pickClosed');
                    $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                } catch (Exception $e) {
                    #$profiler = $this->db->getProfiler();
                    #$query = $profiler->getLastQueryProfile();
                    #echo  $query->getQuery();
                    #Zend_Debug::Dump($new_pick);
                    #Zend_Debug::Dump($e);
                    
                    $this->db->rollBack();
                    $this->view->formErrors = array('An error occurred trying to close that pick.  Please try again.');
                    
                    
                }
            }
            else {
                $this->view->formErrors = array('Could not get a valid quote for that symbol.');
            }
        }

        $this->view->object = $this->_modObject;
        $this->view->form = $form;
    }
}