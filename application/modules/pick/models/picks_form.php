<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'pick_form',
        'method' => 'post',
        'id' => 'pick_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.pick_form] }) && YAHOO.viame.dubsub.check(this));' // Set in the form view script
    ),
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
            // Added the validation for edit only.  Create makes a default title
            //'class' => 'regula-validation',
            //'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[pick_form])',
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
        'heading' => array('Textarea', array(
            'label' => 'Heading',
            'description' => '512 Character(s) Remaining',
            'maxlength' => 512,
            'class' => 'vmfh_expandable_textarea',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'style' => 'height: 1em;',
            'maxheight' => '64',
            'minheight' => '16',
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
            'description' => '1024 Character(s) Remaining',
            'maxlength' => 1024,
            'class' => 'vmfh_expandable_textarea',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 1024);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 1024);",
            'style' => 'height: 1em;',
            'maxheight' => '128',
            'minheight' => '64',
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
            'description' => 'eg. "Read More" (32 Char(s) Remaining)',
            'maxlength' => 32,
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 32);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 32);",
            'order' => 20,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 32))
            )
        )),
        'meta_title' => array('Textarea', array(
            'label' => 'Title',
            'description' => '256 Character(s) Remaining',
            'maxlength' => 256,
            'class' => 'vmfh_expandable_textarea',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'style' => 'height: 1em;',
            'maxheight' => '64',
            'minheight' => '16',
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
            'description' => '512 Character(s) Remaining',
            'maxlength' => 512,
            'class' => 'vmfh_expandable_textarea',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'style' => 'height: 1em;',
            'maxheight' => '64',
            'minheight' => '32',
            'order' => 30,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'meta_keywords' => array('Textarea', array(
            'label' => 'Keywords',
            'description' => 'Comma Separated (512 Char(s) Remaining)',
            'maxlength' => 512,
            'class' => 'vmfh_expandable_textarea',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'style' => 'height: 1em;',
            'maxheight' => '64',
            'minheight' => '32',
            'order' => 35,
            'filters' => array(
                'StringTrim', 'StripNewlines'
            ),
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'allow_comments' => array('Checkbox', array(
            'label' => 'Allow Comments',
            #'description' => 'Allow comments',
            'value' => 1,
            'order' => 40
        )),
        'allow_ratings' => array('Checkbox', array(
            'label' => 'Allow Ratings',
            #'description' => 'Allow ratings',
            'value' => 1,
            'order' => 45
        )),
        
        'symbol' => array('Text', array(
            'label' => 'Symbol',
            #'description' => 'Symbol',
            'required' => true,
            'class' => 'vmfh_acss regula-validation',
            'data-constraints' => '@Required(label="symbol", message="The {label} field cannot be empty.", groups=[pick_form])',
            'order' => 50
        )),
        'position' => array('Select', array(
            'label' => 'Position',
            #'description' => 'Long or Short',
            'required' => true,
            'MultiOptions' => array(
                '1' => 'Buy Long',
                '-1' => 'Sell Short'
            ),
            'validators' => array(
                array('Between', false, array(-1, 1))
            ),
            'order' => 55
        )),
        'allocation' => array('Select', array(
            'label' => '% Allocation',
            #'description' => 'Allocation For This Pick (%)',
            'required' => true,
            'value' => 10,
            'validators' => array(
                array('Between', false, array(1, 100))
            ),
            'order' => 60
        )),
        'notes' => array('Textarea', array(
            'label' => 'Notes',
            'description' => 'Trading and/or other notes',
            #'filters' => array(
            #    array('HTMLPurify')
            #),
            'order' => 65
        )),
        'suggested_stop_loss' => array('Text', array(
            'label' => 'Stop Loss',
            #'description' => 'Suggested Stop Loss',
            'validators' => array(
                array('Float')
            ),
            'order' => 70
        )),
        'trailing_stop_loss' => array('Text', array(
            'label' => 'Trailing Stop Loss',
            #'description' => 'Suggested Stop Loss',
            'validators' => array(
                array('Float')
            ),
            'order' => 72
        )),
        'trailing_stop_loss_type' => array('Select', array(
            'label' => 'Trailing Stop Loss Type',
            #'description' => 'Suggested Stop Loss',
            'multiOptions' => array(
                '0' => '$',
                '1' => '%'
            ),
            'validators' => array(
                array('Int')
            ),
            'order' => 73
        )),
        'live_stop_loss' => array('Checkbox', array(
            'label' => 'Live Stops',
            #'description' => 'Stop losses are live and will be triggered within the system',
            'value' => 1,
            'order' => 74
        )),
        'risk' => array('Select', array(
            'label' => 'Risk',
            #'description' => 'Risk',
            'multiOptions' => array(
                '' => '-- Select Risk --',
                '1' => 'High',
                '0' => 'Medium',
                '-1' => 'Low'
            ),
            #'value' => 0,
            'validators' => array(
                array('Between', false, array(-1, 1))
            ),
            'order' => 75
        )),
        'timeframe' => array('Select', array(
            'label' => 'Timeframe',
            #'description' => 'Estimated holding period',
            'multiOptions' => array(
                '' => '-- Select Timeframe --',
                '1' => 'Long-Term',
                '0' => 'Mid-Term',
                '-1' => 'Short-Term'
            ),
            #'value' => 0,
            'validators' => array(
                array('Between', false, array(-1, 1))
            ),
            'order' => 80
        )),
        'target_date' => array('Text', array(
            'label' => 'Target Date',
            #'description' => 'Estimated date for pick maturation (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')',
            'class' => 'vmfh_date',
            'autocomplete' => 'off',
            'validators' => array(
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
            'order' => 85
        )),
        'target_price' => array('Text', array(
            'label' => 'Target Price',
            #'description' => 'Estimated price at pick maturation',
            'validators' => array(
                array('Float')
            ),
            'order' => 90
        )),        
        'holding' => array('Select', array(
            'label' => 'Holding',
            #'description' => 'Holding',
            'multiOptions' => array(
                '1' => 'Long Position',
                '0' => 'No Position',
                '-1' => 'Short Position'
            ),
            'value' => 0,
            'validators' => array(
                array('Between', false, array(-1, 1))
            ),
            'order' => 95
        )),
        'disclosure' => array('Textarea', array(
            'label' => 'Disclosure',
            'description' => 'Other disclosure statements',
            #'filters' => array(
            #    array('HTMLPurify')
            #),
            'order' => 100
        )),
        'content' => array('Textarea', array(
            'label' => 'Content',
            #'description' => 'Content of the analysis',
            'class' => 'vmfh_textarea',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 105
        )),
        /*
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
            'order' => 110
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
            'order' => 115
        )),
        */
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show Access Link',
            'description' => 'Show when access denied',
            'value' => 0,
            'order' => 120
        ))
    )
);
