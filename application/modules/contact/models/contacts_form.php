<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */
    
$form_config = array(
    'attribs' => array(
        'name' => 'contact_form',
        'method' => 'post',
        'id' => 'contact_form',
        'class' => 'form'
    ),
    'elements' => array(
        /*
        'contact_profile_id' => array('Hidden', array(
            'required' => true,
            'class' => 'vmfh_acvc_hidden',
            'validators' => array(
                'Int'
            )
        )),
        */
        'contact' => array('Text', array(
            'label' => 'Contact',
            'description' => 'Contact name (type partial name and select from drop-down)',
            'order' => 5,
            'class' => 'vmfh_acvc nocontacts'
        )),
        'display' => array('Text', array(
            'label' => 'Displayed Name',
            'description' => 'Displayed Name',
            'order' => 10,
            'validators' => array(
                array('StringLength', false, array(1, 98)),
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/'))
            )
        )),
        'description' => array('Textarea', array(
            'label' => 'Description',
            'description' => 'Description',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 15
        )),
        'message' => array('Textarea', array(
            'label' => 'Request Message',
            'description' => 'Message to display to contact (256 Chars Max - Optional)',
            'filters' => array(
                array('HTMLPurify')
            ),
            'validators' => array(
                array('StringLength', false, array(1, 256))
            ),
            'order' => 20
        )),
        'auto_reciprocate' => array('Checkbox', array(
            'label' => 'Auto-Reciprocate',
            'description' => 'Auto-Reciprocate',
            'value' => true,
            'order' => 25
        ))
    )
);