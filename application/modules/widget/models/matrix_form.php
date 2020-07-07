<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'matrix_form',
        'method' => 'post',
        'id' => 'matrix_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.widget_matrix_form] }) && YAHOO.viame.dubsub.check(this));'
    ),
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'orderby' => array('Text', array(
            'label' => 'Priority',
            'description' => 'Priority (Float)',
            'order' => 5,
            'validators' => array(
                array('Float')
            )
        )),
        'widget_id' => array('Select', array(
            'label' => 'Widget',
            #'description' => 'Widget',
            'order' => 10,
            'validators' => array(
                'Int'
            )
        )),
        'display' => array('Text', array(
            'label' => 'Widget Display Title',
            'description' => '256 Character(s) Remaining',
            'maxlength' => 256,
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[module_matrix_form])',
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'order' => 15,
            'validators' => array(
                array('StringLength', false, array(0, 256)),
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/'))
            )
        )),
        'display_url' => array('Text', array(
            'label' => 'Title URL',
            'description' => 'Url to go to when title is clicked',
            'maxlength' => 512,
            'order' => 20,
            'validators' => array(
                array('StringLength', false, array(1, 512))
            )
        )),
        'secondary' => array('Text', array(
            'label' => 'Secondary Widget Display',
            'description' => '256 Character(s) Remaining',
            'maxlength' => 256,
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'order' => 22,
            'validators' => array(
                array('StringLength', false, array(0, 256))
            )
        )),
        'secondary_url' => array('Text', array(
            'label' => 'Secondary Widget Display URL',
            #'description' => '512 Character(s) Remaining',
            'description' => 'Url to go to when clicked',
            'maxlength' => 512,
            #'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            #'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 512);",
            'order' => 23,
            'validators' => array(
                array('StringLength', false, array(1, 512))
            )
        )),
        'widget' => array('Checkbox', array(
            'label' => 'Display Widget',
            #'description' => 'Display Widget',
            'value' => 1,
            'order' => 25
        )),
        'display_cm' => array('Checkbox', array(
            'label' => 'Display In Content Module',
            'description' => 'Display the widget in a content module box.',
            'value' => 1,
            'order' => 30
        )),
        'status' => array('Radio', array(
            'label' => 'Status',
            #'description' => 'Publish Status',
            'MultiOptions' => array(
                '1' => 'Active',
                '0' => 'Inactive'
            ),
            'value' => 1,
            'order' => 35
        )),
    )
);
