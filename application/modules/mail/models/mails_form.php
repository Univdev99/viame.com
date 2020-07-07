<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'mail_form',
        'method' => 'post',
        'id' => 'mail_form',
        'class' => 'form'
    ),
    'elements' => array(
        'to' => array('Text', array(
            'label' => 'To',
            #'description' => '<a href="">Show CC</a> - <a href="">Show BCC</a>',
            #'required' => 'true',
            'order' => 5,
            'class' => 'vmfh_acvc multiple addgroups unique'
        )),
        'cc' => array('Text', array(
            'label' => 'Cc',
            #'description' => 'Cc who',
            'order' => 10,
            'class' => 'vmfh_acvc multiple addgroups unique'
        )),
        'bcc' => array('Text', array(
            'label' => 'Bcc',
            #'description' => 'Bcc who',
            'order' => 15,
            'class' => 'vmfh_acvc multiple addgroups unique'
        )),
        'priority' => array('Select', array(
            'label' => 'Priority',
            #'description' => 'Mail priority',
            'value' => '0',
            'order' => 20,
            'multiOptions' => array(
                '2' => 'Highest',
                '1' => 'High',
                '0' => 'Normal',
                '-1' => 'Low',
		        '-2' => 'Lowest'
            )
        )),
        'subject' => array('Text', array(
            'label' => 'Subject',
            #'description' => 'Subject of the mail',
            'required' => true,
            'order' => 25,
            'filters' => array(
                array('HTMLPurify')
            ),
            'validators' => array(
                array('StringLength', false, array(0, 256))
            )
        )),
        'self_destruct' => array('Text', array(
            'label' => 'Self-Destruct',
            'description' => '(' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ' hh:mm:ss)',
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
            'order' => 30
        )),
        'content' => array('Textarea', array(
            'label' => 'Content',
            'description' => 'Content of the message',
            'required' => true,
            'class' => 'vmfh_textarea',
            'filters' => array(
                array('HTMLPurify')
            ),
            'cols' => 45,
            'rows' => 5,
            'order' => 35
        ))
    )
);
