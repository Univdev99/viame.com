<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'page_form',
        'method' => 'post',
        'id' => 'page_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.page_form] }) && YAHOO.viame.dubsub.check(this));' // Set in the form view script
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
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[page_form])',
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
            'order' => 10,
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
            'order' => 15,
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
            'order' => 20,
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
            'order' => 25
        )),
        'allow_ratings' => array('Checkbox', array(
            'label' => 'Allow Ratings',
            #'description' => 'Allow ratings',
            'value' => 1,
            'order' => 30
        )),
        'parent_id' => array('Select', array(
            'label' => 'Parent Page',
            'description' => 'Page is a child page',
            'class' => 'ohp',
            'order' => 35
        )),
        'content' => array('Textarea', array(
            'label' => 'Content',
            #'description' => 'Content of the page',
            'required' => true,
            'class' => 'vmfh_textarea',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 40
        )),
        'disable_cm' => array('Checkbox', array(
            'label' => 'Disable Content Module Box',
            'description' => 'Disable bounding box and interactive content, such as comments and rating.',
            'value' => 0,
            'order' => 45
        )),
        'disable_sublayouts' => array('Checkbox', array(
            'label' => 'Disable Frame',
            'description' => 'Disable header, footer, and fixed column.',
            'value' => 0,
            'order' => 50
        )),
        'disable_layouts' => array('Checkbox', array(
            'label' => 'Disable Layouts',
            'description' => 'Display page as a standalone HTML page.  Plaintext editor should be used.',
            'value' => 0,
            'order' => 55
        )),
        'page_code' => array('Select', array(
            'label' => 'Page Code',
            'description' => 'Page coded language',
            'MultiOptions' => array(
                '' => 'HTML',
                'PHP' => 'PHP'
            ),
            'value' => 1,
            'order' => 57
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
            'order' => 60
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
            'order' => 65
        )),
        'status' => array('Radio', array(
            'label' => 'Status',
            #'description' => 'Publish Status',
            'MultiOptions' => array(
                '1' => 'Published',
                '0' => 'Draft'
            ),
            'value' => 1,
            'order' => 70
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show Access Link',
            'description' => 'Show when access denied',
            'value' => 0,
            'order' => 75
        ))
    )
);
