<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'article_form',
        'method' => 'post',
        'id' => 'article_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.article_form] }) && YAHOO.viame.dubsub.check(this));' // Set in the form view script
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
            'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[article_form])',
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
        'widget_image_url' => array('Text', array(
            'label' => 'Widget Image URL',
            'description' => 'Url of image to display in widget',
            'maxlength' => 512,
            'order' => 23,
            'validators' => array(
                array('StringLength', false, array(1, 512))
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
        'content' => array('Textarea', array(
            'label' => 'Content',
            #'description' => 'Content of the article',
            'required' => true,
            'class' => 'vmfh_textarea',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 50
        )),
        'activation' => array('Text', array(
            'label' => 'Activation',
            'description' => 'Content accessible after this date/time (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
            'class' => 'vmfh_date vmfh_datetime',
            'autocomplete' => 'off',
            'order' => 55,
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
            )
        )),
        'expiration' => array('Text', array(
            'label' => 'Expiration',
            'description' => 'Content not accessible after this date/time (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
            'class' => 'vmfh_date vmfh_datetime',
            'autocomplete' => 'off',
            'order' => 60,
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
            )
        )),
        'status' => array('Radio', array(
            'label' => 'Status',
            #'description' => 'Publish Status',
            'MultiOptions' => array(
                '1' => 'Published',
                '0' => 'Draft'
            ),
            'order' => 65
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show Access Link',
            'description' => 'Show when access denied',
            'value' => 0,
            'order' => 70
        ))
    )
);