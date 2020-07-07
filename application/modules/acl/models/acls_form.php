<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'acls_form',
        'id' => 'acls_form',
        'class' => 'form'
    ),
    'elements' => array(
        'title' => array('Text', array(
            'label' => 'Title',
            #'description' => '512 Character(s) Remaining',
            'maxlength' => 512,
            'required' => true,
            #'class' => 'regula-validation',
            #'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[acl_form])',
            #'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            #'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'order' => 3,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'display' => array('Text', array(
            'label' => 'Display',
            #'description' => '512 Character(s) Remaining',
            'maxlength' => 512,
            #'required' => false,
            #'class' => 'regula-validation',
            #'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[acl_form])',
            #'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            #'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'order' => 4,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'orderby' => array('Text', array(
            'label' => 'Priority',
            'description' => 'Priority',
            'order' => 5,
            'validators' => array(
                array('Float')
            )
        )),
        'w_registration_status' => array('Radio', array(
            'label' => 'Registration Status',
            'description' => 'Registration Status',
            'multioptions' => array(
                'E' => 'Everyone',
                'R' => 'Registered Profiles',
                'B' => 'Registered Base Profiles'
            ),
            'order' => 10
        )),
        'w_groups' => array('Multiselect', array(
            'label' => 'Contact Groups',
            'description' => 'Contact Groups',
            'order' => 15
        )),
        'w_contact_profiles' => array('Multiselect', array(
            'label' => 'Contacts',
            'description' => 'Contacts',
            'order' => 20
        )),
        'w_arb_profiles' => array('Text', array(
            'label' => 'Arbitrary Profiles',
            'description' => 'Artbitrary Profiles',
            'order' => 25
        )),
        'w_arb_profiles_ex' => array('Text', array(
            'label' => 'Arbitrary Excluded Profiles',
            'description' => 'Artbitrary Profiles Excluded From This Rule',
            'order' => 30
        )),
        'w_dos' => array('Text', array(
            'label' => 'Degrees of Separation',
            'description' => 'Degrees of Separation',
            'order' => 35,
            'validators' => array(
                array('Int')
            )
        )),
        'w_password' => array('Text', array(
            'label' => 'Password',
            'description' => 'Password',
            'order' => 40
        )),
        'w_member_start' => array('Text', array(
            'label' => 'Start Date',
            'description' => 'Optional Future Start Date (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')',
            'class' => 'vmfh_date',
            'autocomplete' => 'off',
            'order' => 41,
            'validators' => array(
                #array('Date', false, array('YYYY-MM-DD')),
                array('Regex', false, array(
                    'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . '$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                    )
                )),
                array('Date', false,
                    preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short')))
                )
            ),
        )),
        'w_member_initial_amount' => array('Text', array(
            'label' => 'Initial / Setup Amount',
            'description' => 'Initial / Setup Amount',
            'order' => 43,
            'validators' => array(
                array('Float')
            )
        )),
        'w_member_trial_amount' => array('Text', array(
            'label' => 'Member Trial Amount',
            'description' => 'Member Trial Amount',
            'order' => 45,
            'validators' => array(
                array('Float')
            )
        )),
        'w_member_trial_quantity' => array('Text', array(
            'label' => 'Member Trial Quantity',
            'description' => 'Member Trial Quantity',
            'order' => 50,
            'validators' => array(
                array('Int')
            )
        )),
        'w_member_trial_interval' => array('Select', array(
            'label' => 'Member Trial Interval',
            'description' => 'Member Trial Interval',
            'multioptions' => array(
                'Day' => 'Day',
                'Week' => 'Week',
                'Month' => 'Month',
                'Year' => 'Year'
            ),
            'order' => 55
        )),
        'w_member_amount' => array('Text', array(
            'label' => 'Member Amount',
            'description' => 'Member Amount',
            'order' => 60,
            'validators' => array(
                array('Float')
            )
        )),
        'w_member_quantity' => array('Text', array(
            'label' => 'Member Quantity',
            'description' => 'Member Quantity',
            'order' => 65,
            'validators' => array(
                array('Int')
            )
        )),
        'w_member_interval' => array('Select', array(
            'label' => 'Member Interval',
            'description' => 'Member Interval',
            'multioptions' => array(
                'Day' => 'Day',
                'Week' => 'Week',
                'Month' => 'Month',
                'Year' => 'Year'
            ),
            'order' => 70
        )),
        'w_member_auto_renew' => array('Checkbox', array(
            'label' => 'Recurring Billing',
            'description' => 'Recurring Billing',
            'value' => 1,
            'order' => 75
        )),
        'privilege' => array('Text', array(
            'label' => 'Privilege',
            'description' => 'Privilege',
            'required' => true,
            'order' => 80,
            'validators' => array(
                array('Int')
            )
        )),
        'filter' => array('Text', array(
            'label' => 'Filter',
            'description' => 'Filter',
            'order' => 85
        )),
        'description' => array('Textarea', array(
            'label' => 'Description',
            'description' => 'Description',
            'order' => 90
        )),
        'recursive' => array('Checkbox', array(
            'label' => 'Recursive',
            'description' => 'Recursive',
            'value' => 1,
            'order' => 95
        )),
        'invisible' => array('Checkbox', array(
            'label' => 'Invisible',
            'description' => 'Selection is not automatically shown.  Url to selection must be known.',
            'value' => 0,
            'order' => 97
        )),
        'expiration' => array('Text', array(
            'label' => 'Expiration',
            'description' => 'Expiration (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')',
            'class' => 'vmfh_date',
            'autocomplete' => 'off',
            'order' => 100,
            'validators' => array(
                #array('Date', false, array('YYYY-MM-DD')),
                array('Regex', false, array(
                    'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . '$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                    )
                )),
                array('Date', false,
                    preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short')))
                )
            )
        ))  
    )
);