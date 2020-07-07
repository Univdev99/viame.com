<?php
/**
 * ViaMe Application
 *
 * Levelogic, Inc. (http://www.levelogic.com)
 */

class Contact_EditController extends ViaMe_Controller_Action
{   
    // PreDispatch Overrides
    //protected $_routeThruDefault = true;
    protected $_memberDefined = true;
    //protected $_moduleInMatrix = true;
    //protected $_modObjectCheck = true;
    protected $_minPrivilege = self::ACL_OWNER;
    //protected $_defaultAllow = true;
    protected $_mustBeOwner = true;
    
    //protected $_tableName = 'table_tables';
    
    public function init()
    {
        parent::init();
        
        if (isset($this->target->type) && $this->target->type == 'VIA' && isset($this->via)) {
            $this->target->id = $this->via->id;
        }
        else {
            $this->target->id = $this->member->profile->id;
            $this->_minPrivilege = null;
            $this->target->acl->owner = true;
        }
    }
    
    
    public function indexAction() {
        $this->view->headTitle('Manage Your Contacts', 'PREPEND');
        $this->view->headTitle('Edit A Contact', 'PREPEND');
        
        // Change Sub Layout
        $this->_helper->ViaMe->setSubLayout('default');
        #$this->internal->sublayout_with_header = true;
        
        /* Moved this to the beginning to bypass loading of the form and other ancillary
           functions.  Also, didn't use cancel->isChecked(), as that requires form load.
         */
        if ($this->getRequest()->isPost() && ($this->_getParam('cancel') == 'Cancel')) {
            $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
        }
        
        
        require dirname(dirname(__FILE__)) . '/models/contacts_form.php';        
        $form = new Zend_Form();
        $form->addPrefixPath('ViaMe_Form', 'ViaMe/Form/');  // For Hidden Decorator
        $form->addElementPrefixPath('ViaMe', 'ViaMe/');     // For Filters
        $form->setOptions($form_config);
        
        
        $form->getElement('contact')
            ->setAttrib('class', null)
            ->setAttrib(ViaMe_Form_Decorator_ViewHelper::ATTRIB_NAME, ViaMe_Form_Decorator_ViewHelper::VALUE_HIDDEN)
            ->setDecorators( array('ViewHelper', array('HtmlTag', array('tag' => 'dd', 'style' => 'font-weight: bold;')), array('Label', array('tag' => 'dt'))) );
        
        
        $form->addDisplayGroup(array('display', 'description'), 'options_meta', array('legend' => 'Custom Display'));
        $form->addDisplayGroup(array('auto_reciprocate'), 'options_reciprocate', array('legend' => 'Auto-Reciprocate'));
        
        $form->addDisplayGroup(array('contact', 'message'), 'main');
        
        $form->addElement('Submit', 'submit', array('label' => 'Edit Contact', 'ignore' => true, 'order' => 999));
        $form->addElement('Submit', 'cancel', array('label' => 'Cancel', 'order' => 1000));
        $form->addDisplayGroup(array('submit', 'cancel'), 'buttons');
        
        $form->addElement('Hidden', 'id', array('required' => true));
        $form->addElement('Hidden', 'vmpd_nar', array('value' => 1));
        $form->addElement('Hidden', 'redirect', array('value' => $this->_getParam('redirect')));
        $form->addDisplayGroup(array('id', 'vmpd_nar', 'redirect'), 'hidden');
        
        // Remove HTMLPurify Filter
        if (isset($this->member) && ($this->member->site_admin || $this->member->profile->site_admin)) {
            $form->getElement('description')->removeFilter('HTMLPurify');
            $form->getElement('message')->removeFilter('HTMLPurify');
        }
        
        // If posted, validate, write to db and send email
        if ($this->getRequest()->isPost() && $form->isValid($this->_getAllParams())) {
            $contact_contacts = new contact_models_contacts();
            $fields = array('display', 'description', 'message', 'auto_reciprocate');
            $params = (object) $form->getValues();
            foreach ($fields as $key) {
                if (isset($params->$key) && $params->$key !== '') {
                    $data[$key] = $params->$key;
                }
                else {
                    $data[$key] = null;
                }
            }
            
            $where = array();
            $where[] = $contact_contacts->getAdapter()->quoteInto('profile_id=?', $this->target->id);
            $where[] = $contact_contacts->getAdapter()->quoteInto('contact_profile_id=?', $this->_getParam('id'));
            
            
            try {
                $contact_contacts->update($data, $where);
                
                $this->_autoredirect($this->target->pre . '/' . $this->getRequest()->getModuleName() . '/');
                
            } catch (Exception $e) {
                $this->view->formErrors = array('That contact was not successfully updated.');
            }
            
        } elseif (!$this->getRequest()->isPost()) {
            // Can reuse code from the view controller
            $select = $this->db->select()->from(array('c' => 'contact_contacts'))
                ->where('c.active NOTNULL')
                ->where('c.contact_profile_id=?', $this->_getParam('id'))
                ->where('c.profile_id=?', $this->target->id)
                // Placeholder
                ->join(array('p' => 'profile_profiles'), 'c.contact_profile_id = p.id', array('name' => 'name'))
                ->where('p.active NOTNULL')
                ->join(array('b' => 'member_members'), 'p.member_id = b.id', array())
                ->where('b.active NOTNULL')
                #->order(array('p.id DESC'))
                ;
            
            if ($result = $this->db->fetchRow($select)) {
                if (!is_null($result->status)) {
                    $form->removeDisplayGroup('options_reciprocate');
                    $form->removeElement('auto_reciprocate');
                    $form->removeElement('message');
                }
                $result->contact = $result->name;
                $form->populate((array) $result);
            } else {
                $this->view->formErrors = array('There was an error trying to retrieve that contact.');
            }
        }
        
        $this->view->form = $form;
        $this->view->page_title = 'Edit A Contact';
        
        $this->renderScript('create/index.phtml');
    }
}