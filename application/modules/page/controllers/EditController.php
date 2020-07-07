<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

class Page_EditController extends ViaMe_Controller_Default_Edit
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
    
    
    public function indexAction()
    {
        $this->view->headTitle('Edit Page', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        $this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
        }
        
        // Load the Form
        require dirname(dirname(__FILE__)) . '/models/pages_form.php';
        $form = new Zend_Form();
        $form->setOptions($form_config);

        $form->addDisplayGroup(array('meta_title', 'meta_description', 'meta_keywords'), 'options_meta', array('legend' => 'Meta Tags'));
        $form->addDisplayGroup(array('parent_id', 'disable_cm', 'disable_sublayouts', 'disable_layouts', 'page_code'), 'options_display', array('legend' => 'Display'));
        $form->addDisplayGroup(array('allow_comments', 'allow_ratings'), 'options_interact', array('legend' => 'Interactive'));
        $form->addDisplayGroup(array('activation', 'expiration'), 'options_actexp', array('legend' => 'Active / Expire'));
        $form->addDisplayGroup(array('status'), 'options_status', array('legend' => 'Status'));
        $form->addDisplayGroup(array('show_on_fail'), 'options_acl', array('legend' => 'Access Controls'));
        
        $form->addDisplayGroup(array('title', 'content'), 'main');
        #$form->getDisplayGroup('main')->removeDecorator('Fieldset');
        
        $form->addElement('Submit', 'submit', array('label' => 'Publish Page', 'order' => 996));
        $form->addElement('Submit', 'save', array('label' => 'Save Page', 'ignore' => true, 'order' => 997));
        $form->addElement('Submit', 'saveedit', array('label' => 'Save and Continue Editing', 'order' => 998));
        $form->addElement('Reset', 'reset', array('label' => 'Reset', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000, 'onClick' => "document.getElementById('page_form').vivregval_canceled = true;"));
        $form->addDisplayGroup(array('submit', 'save', 'saveedit', 'reset', 'cancel'), 'buttons');

        $form->addElement('Hidden', 'mid', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('mid', 'vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
            if ((!$this->getRequest()->isPost() && isset($this->_modObject->page_code) && $this->_modObject->page_code) ||
                ($this->getRequest()->isPost() && $this->_getParam('page_code'))) {
                $form->getElement('content')->setAttrib('class', 'vmfh_codeeditor');
                $form->getElement('content')->setAttrib('langmode', (($this->getRequest()->isPost() && $this->_getParam('page_code')) ? $this->_getParam('page_code') : $this->_modObject->page_code));
            }
        }
        
        
        // Load up the existing pages for possible parent
        $groups[''] = '-- None --';
        /*
        $which_space = strtolower($this->target->type) . '_id';
        
        foreach ($this->db->fetchAll("SELECT counter, title, array_to_string(array_reverse((SELECT array_accum(a) FROM recursive_find('page_pages', 'parent_id', 'counter', counter, 't') AS a)), '-') AS sort_order FROM page_pages WHERE active='t' AND $which_space=? AND matrix_counter=? AND counter<>? ORDER BY sort_order, counter, title", array($this->target->id, $this->_getParam('mid'), $this->_getParam('id'))) as $group) {
            $temp_groups[$group->sort_order] = $group;
        }
        if (isset($temp_groups)) {
            $temp_sorted_keys = array_keys( $temp_groups );
            natsort( $temp_sorted_keys );
            
            foreach ($temp_sorted_keys as $key) {
                $groups[$temp_groups[$key]->counter] = (str_repeat(' -- ', substr_count($temp_groups[$key]->sort_order, '-'))) . $temp_groups[$key]->title;
            }
        }
        */
        
        $clause = 'pp.' . strtolower($this->target->type) . '_id=' . $this->target->id . ' AND pp.matrix_counter=' . $this->_getParam('mid');
        
        foreach ($this->db->fetchAll(
                "WITH RECURSIVE find_pages(com_id, net_id, via_id, matrix_counter, counter, title, active, parent_id, depth, path, cycle) AS (
                    SELECT pp.com_id, pp.net_id, pp.via_id, pp.matrix_counter, pp.counter, pp.title, pp.active, pp.parent_id, 1,
                      ARRAY[pp.counter],
                      false
                    FROM page_pages pp WHERE pp.parent_id IS NULL AND pp.active='t' AND $clause
                  UNION ALL
                    SELECT pp.com_id, pp.net_id, pp.via_id, pp.matrix_counter, pp.counter, pp.title, pp.active, pp.parent_id, fp.depth + 1,
                      path || pp.counter,
                      pp.counter = ANY(path)
                    FROM page_pages pp, find_pages fp
                    WHERE fp.counter = pp.parent_id AND NOT cycle AND pp.active='t' AND $clause
                )
                SELECT DISTINCT * FROM find_pages WHERE counter <> " . $this->_getParam('id') . " ORDER BY path"
            ) as $group) {
            $groups[$group->counter] = (str_repeat('-- ', substr_count($group->path, ','))) . $group->title;
        }
        
        $form->getElement('parent_id')->setMultiOptions($groups);
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('content')->removeFilter('HTMLPurify');
        }
        else {
            $form->removeElement('disable_layouts');
            $form->removeElement('page_code');
        }
        
        // If posted, validate, write to db
        if ($this->getRequest()->isPost() && (($this->_getParam('override')) || $form->isValid($this->_getAllParams()))) {
            $fields = array('title' => 'title', 'meta_title' => 'meta_title', 'meta_description' => 'meta_description', 'meta_keywords' => 'meta_keywords', 'allow_comments' => 'allow_comments', 'allow_ratings' => 'allow_ratings', 'content' => 'content', 'disable_cm' => 'disable_cm', 'disable_sublayouts' => 'disable_sublayouts', 'disable_layouts' => 'disable_layouts', 'page_code' => 'page_code', 'status' => 'active', 'show_on_fail' => 'show_on_fail', 'parent_id' => 'parent_id');
            $params = (object) $form->getValues();
            foreach ($fields as $key => $val) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$val] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            
            // Activation Field
            if ($this->_getParam('activation')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('activation'));
                $data['activation'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['activation'] = null;
            }
            // Expiration Field
            if ($this->_getParam('expiration')) {
                $date = new Zend_Date();
                if (isset($this->member)) {
                    $date->setTimezone($this->internal->member->timezone);
                }
                $date->set($this->_getParam('expiration'));
                $data['expiration'] = $date->get(Zend_Date::ISO_8601);
            }
            else {
                $data['expiration'] = null;
            }
            
            // Modified
            $data['modified'] = new Zend_Db_Expr("(CASE WHEN published ISNULL OR (modified ISNULL AND activation > published) THEN null ELSE 'now' END)::timestamp");
            
            // Status Field
            if ($this->_getParam('submit') || $this->_getParam('status')) {
                $data['active'] = 't';
                $data['published'] = new Zend_Db_Expr("COALESCE(published, 'now')");
            }
            
            $page_pages = new page_models_pages();
            
            $where = array();
            $where[] = $this->db->quoteInto('matrix_counter=?', $this->_getParam('mid'));
            $where[] = $this->db->quoteInto('counter=?', $this->_getParam('id'));
            $where[] = $this->db->quoteInto(strtolower($this->target->type) . '_id=?', $this->target->id);
            
            if ($data) {
                try {
                    $page_pages->update(
                        $data,
                        $where
                    );
                    
                    $this->log->ERR('_pageEdited', array('item_counter' => $this->_getParam('id')));
                    
                    if ($this->_getParam('saveedit')) {
                        $this->view->formErrors = array('Page Saved.');
                    }
                    else {
                        $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
                    }

                    
                } catch (Exception $e) {
                    $this->view->formErrors = array('That page was not successfully editted.');
                }
            }
            else {
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/p/mid/' . $this->_getParam('mid') . '/');
            }
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $form->populate((array) $this->_modObject);
            $form->getElement('status')->setValue($this->_modObject->active);
            $date = new Zend_Date();
            if (isset($this->member)) {
                $date->setTimezone($this->internal->member->timezone);
            }
            if (isset($this->_modObject->activation)) {
                $date->set($this->_modObject->activation, Zend_Date::ISO_8601);
                $form->getElement('activation')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
            if (isset($this->_modObject->expiration)) {
                $date->set($this->_modObject->expiration, Zend_Date::ISO_8601);
                $form->getElement('expiration')->setValue($date->toString(preg_replace(array('/m+/i', '/d+/i', '/y+/i'), array('MM', 'dd', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', 'short')) . ' HH:mm:ss'));
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit Page' . ($this->_modObject->title ? ' (' . $this->_modObject->title . ')' : '');
        
        $this->renderScript('create/index.phtml');
    }
}
