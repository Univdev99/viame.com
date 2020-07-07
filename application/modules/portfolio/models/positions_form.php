<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'portfolio_position_form',
        'id' => 'portfolio_position_form',
        'class' => 'form',
    ),
    
    /* DO VIVIAN REGULA VALIDATION AND EXPANDING TEXTAREASW WITH CHARACTER COUNTDOWNS*/
    
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'symbol' => array('Text', array(
            'label' => 'Symbol',
            'description' => 'Equity Symbol',
            'required' => true,
            'class' => 'vmfh_acss',
            'order' => 5
        )),
        'position' => array('Select', array(
            'label' => 'Position',
            'description' => 'Long or Short Position',
            'required' => true,
            'MultiOptions' => array(
                '1' => 'Long',
                '-1' => 'Short'
            ),
            'validators' => array(
                array('Between', false, array(-1, 1))
            ),
            'order' => 10
        )),
        'shares' => array('Text', array(
            'label' => '# Shares',
            'description' => 'Number of Shares',
            'required' => true,
            'validators' => array(
                array('Float')
            ),
            'order' => 15
        )),
        'price' => array('Text', array(
            'label' => 'Price',
            'description' => 'Price',
            #'required' => true,
            'validators' => array(
                array('Float')
            ),
            'order' => 20
        )),
        'purchase_date' => array('Text', array(
            'label' => 'Purchase Date',
            'description' => 'Purchase Date (' . preg_replace(array('/d+/i', '/m+/i', '/y+/i'), array('dd', 'mm', 'yyyy'), Zend_Locale_Data::getContent($this->locale, 'date', array('gregorian', 'short'))) . ')',
            #'required' => true,
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
            'order' => 25
        )),
        'upper_limit' => array('Text', array(
            'label' => 'Upper Limit',
            'description' => 'Upper Limit',
            'validators' => array(
                array('Float')
            ),
            'order' => 30
        )),
        'lower_limit' => array('Text', array(
            'label' => 'Lower Limit',
            'description' => 'Lower Limit',
            'validators' => array(
                array('Float')
            ),
            'order' => 35
        )),
        'notes' => array('Textarea', array(
            'label' => 'Notes',
            'description' => 'Notes',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 40
        )),

    )
);