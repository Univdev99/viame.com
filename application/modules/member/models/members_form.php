<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'member_form',
        'id' => 'member_form',
        'class' => 'form regula-validation',
        'data-constraints' => '@PasswordsMatch(field1="password", field2="password_confirm", message="Your passwords do not match.", groups=[member_form])',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.member_form] }) && YAHOO.viame.dubsub.check(this));'
    ),
    'elements' => array(
        'first_name' => array('Text', array(
            'label' => 'First Name',
            #'description' => 'Your first name',
            #'required' => true,
            'maxlength' => 32,
            #'class' => 'regula-validation',
            #'data-constraints' => '@Required(label="first name", message="The {label} field cannot be empty.", groups=[member_form])',
            'order' => 5
        )),
        'middle_name' => array('Text', array(
            'label' => 'Middle Name',
            #'description' => 'Your middle name',
            'maxlength' => 32,
            'order' => 10
        )),
        'last_name' => array('Text', array(
            'label' => 'Last Name',
            #'description' => 'Your last name',
            #'required' => true,
            'maxlength' => 32,
            #'class' => 'regula-validation',
            #'data-constraints' => '@Required(label="last name", message="The {label} field cannot be empty.", groups=[member_form])',
            'order' => 15
        )),
        'phone' => array('Text', array(
            'label' => 'Phone #',
            #'description' => 'Best number to contact you at.',
            'maxlength' => 32,
            'class' => 'regula-validation vmfh_phone',
            'data-constraints' => '@Pattern(regex=/^[\d\s\(\)\-\+\.]*$/, label="phone number", message="Use digits only in the {label}.", groups=[member_form], ignoreEmpty=true)',
            'order' => 20
        )),
        'gender' => array('Radio', array(
            'label' => 'Gender',
            #'description' => 'Your gender',
            'MultiOptions' => array(
                'M' => 'Male',
                'F' => 'Female'
            ),
            'order' => 25
        )),
        'dob' => array('Text', array(
            'label' => 'Date of Birth',
            'description' => '(' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')',
            'class' => 'vmfh_date',
            'autocomplete' => 'off',
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
            'order' => 30
        )),
        'dob_month' => array('Select', array(
            'label' => 'Date of Birth Month',
            'order' => 31
        )),
        'dob_day' => array('Select', array(
            'label' => 'Date of Birth Day',
            'order' => 32
        )),
        'dob_year' => array('Select', array(
            'label' => 'Date of Birth Year',
            'order' => 33
        )),
        'postal_code' => array('Text', array(
            'label' => 'Postal Code',
            #'description' => 'Your postal code',
            'maxlength' => 32,
            'order' => 35
        )),
        'username' => array('Text', array(
            'label' => 'Username',
            'description' => 'Use your real name or a screen name. (Min 4 chars)',
            'required' => true,
            'maxlength' => 98,
            'validators' => array(
                array('StringLength', false, array(4, 98)),
                array('Regex', false, array(
                    'pattern' => '/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/u',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'profiles_form_regexNotMatch'
                    )
                ))
            ),
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="username", message="The {label} field cannot be empty.", groups=[member_form])',
            'order' => 39
        )),
        'email' => array('Text', array(
            'label' => 'Email Address',
            #'description' => 'Your login email',
            'required' => true,
            'maxlength' => 256,
            'validators' => array(
                array('EmailAddress', false, array(
                    'messages' => array(
                        Zend_Validate_EmailAddress::INVALID             => 'Invalid type given. String expected.',
                        Zend_Validate_EmailAddress::INVALID_FORMAT      => 'Invalidly formatted email address.',
                        Zend_Validate_EmailAddress::INVALID_HOSTNAME    => 'Invalid hostname.',
                        Zend_Validate_EmailAddress::INVALID_MX_RECORD   => 'No valid MX record for %hostname%.',
                        Zend_Validate_EmailAddress::INVALID_SEGMENT     => 'Not in a routable segment.',
                        Zend_Validate_EmailAddress::DOT_ATOM            => 'Cannot be matched against dot-atom format.',
                        Zend_Validate_EmailAddress::QUOTED_STRING       => 'Cannot be matched against quoted string format.',
                        Zend_Validate_EmailAddress::INVALID_LOCAL_PART  => 'Invalid local part.',
                        Zend_Validate_EmailAddress::LENGTH_EXCEEDED     => 'Exceeds the allowed length.',
                        Zend_Validate_Hostname::UNDECIPHERABLE_TLD      => 'Cannot extract valid TLD.',
                        Zend_Validate_Hostname::LOCAL_NAME_NOT_ALLOWED  => 'Local network names are not allowed.'
                    )
                ))
            ),
            'class' => 'regula-validation',
            'data-constraints' => '@Email(label="email address", message="The {label} was not formatted properly.", groups=[member_form, login_form])',
            'order' => 40
        )),
        'password' => array('Password', array(
            'label' => 'Password',
            'description' => 'Use 4 or more characters (case sensitive)',
            'required' => true,
            'maxlength' => 64,
            'validators' => array(
                array('StringLength', false, array(4, 64))
            ),
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="password", message="The {label} field cannot be empty.", groups=[member_form, login_form]) @Length(min=4, max=64, label="password", message="The {label} must be between 4 and 64 characters in length.", groups=[member_form, login_form])',
            'order' => 45
        )),
        'password_confirm' => array('Password', array(
            'label' => 'Re-Type Password',
            'description' => 'Your login password confirmed',
            'required' => true,
            'maxlength' => 64,
            'validators' => array(
                array('StringLength', false, array(4, 64))
            ),
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="password confirm", message="The {label} field cannot be empty.", groups=[member_form])',
            'order' => 50
        )),
        'timezone' => array('Select', array(
            'label' => 'Time Zone',
            #'description' => 'Your time zone',
            'registerInArrayValidator' => false,
            'order' => 55
        )),
        'country' => array('Select', array(
            'label' => 'Country',
            #'description' => 'Your country',
            'order' => 60,
            'value' => 'US'
        )),
        'currency' => array('Select', array(
            'label' => 'Currency',
            #'description' => 'Your currency',
            'order' => 65,
            'value' => 'USD'
        )),
        'language' => array('Select', array(
            'label' => 'Language',
            #'description' => 'Your preferred language',
            'order' => 70,
            'value' => 'en'
        ))
    )
);
