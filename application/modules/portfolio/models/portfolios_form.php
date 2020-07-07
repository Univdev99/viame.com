<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'portfolio_form',
        'method' => 'post',
        'id' => 'portfolio_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.portfolio_form] }) && YAHOO.viame.dubsub.check(this));' // Set in the form view script
    ),
    
    /* DO VIVIAN REGULA VALIDATION AND EXPANDING TEXTAREASW WITH CHARACTER COUNTDOWNS*/
    
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'title' => array('Text', array(
            'label' => 'Title',
            'description' => '512 Character(s) Remaining',
            'maxlength' => 512,
            'required' => true,
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[portfolio_form])',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'order' => 5,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'heading' => array('Text', array(
            'label' => 'Heading',
            'description' => '(512 Chars Max)',
            'maxlength' => 512,
            'order' => 10,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'summary' => array('Textarea', array(
            'label' => 'Summary',
            'description' => '(1024 Chars Max)',
            'maxlength' => 1024,
            'order' => 15,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 1024))
            )
        )),
        'more_link' => array('Text', array(
            'label' => 'More Link',
            'description' => 'eg. "Read More" (32 Chars Max)',
            'maxlength' => 32,
            'order' => 20,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 32))
            )
        )),
        'meta_title' => array('Text', array(
            'label' => 'Title',
            'description' => '(256 Chars Max)',
            'maxlength' => 256,
            'order' => 25,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 256))
            )
        )),
        'meta_description' => array('Textarea', array(
            'label' => 'Description',
            'description' => '(512 Chars Max)',
            'maxlength' => 512,
            'order' => 30,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'meta_keywords' => array('Text', array(
            'label' => 'Keywords',
            'description' => 'Comma Separated (512 Chars Max)',
            'maxlength' => 512,
            'order' => 35,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'currency_code' => array('Select', array(
            'label' => 'Currency',
            'description' => 'Default currency for this portfolio',
            'order' => 40,
            'value' => 'USD'
        )),
        'cash' => array('Text', array(
            'label' => 'Cash Balance',
            'description' => 'Cash balance of the portfolio',
            'order' => 45
        )),
        'activation' => array('Text', array(
            'label' => 'Activation',
            'description' => 'Content accessible after this date/time (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
            'class' => 'vmfh_date vmfh_datetime',
            'autocomplete' => 'off',
            'validators' => array(
                array('Regex', false, array(
                    'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' \d+:\d+:\d+$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                    )
                )),
                array('Date', false, array(
                    'format' => preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' HH:mm:ss',
                    'messages' => array(
                        Zend_Validate_Date::INVALID_DATE => "'%value%' does not appear to be a valid date/time"
                    )
                ))
            ),
            'order' => 50
        )),
        'expiration' => array('Text', array(
            'label' => 'Expiration',
            'description' => 'Content not accessible after this date/time (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
            'class' => 'vmfh_date vmfh_datetime',
            'autocomplete' => 'off',
            'validators' => array(
                array('Regex', false, array(
                    'pattern' => '/^' . preg_replace(array('/\./', '/\//', '/d+/i', '/m+/i', '/y+/i'), array('\\\.', '\\\/', '\d+', '\d+', '\d+'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' \d+:\d+:\d+$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'Invalidly formatted date'
                    )
                )),
                array('Date', false, array(
                    'format' => preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'MM', 'YYYY'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' HH:mm:ss',
                    'messages' => array(
                        Zend_Validate_Date::INVALID_DATE => "'%value%' does not appear to be a valid date/time"
                    )
                ))
            ),
            'order' => 55
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show Access Link',
            'description' => 'Show when access denied',
            'value' => 1,
            'order' => 60
        ))
    )
);
