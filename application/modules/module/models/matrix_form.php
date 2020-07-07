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
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.module_matrix_form] }) && YAHOO.viame.dubsub.check(this));'
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
        'module_id' => array('Select', array(
            'label' => 'Module',
            #'description' => 'Module',
            'order' => 10,
            'validators' => array(
                'Int'
            )
        )),
        'display' => array('Text', array(
            'label' => 'Module Display Title',
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
        'content' => array('Textarea', array(
            'label' => 'On Page Content',
            #'description' => 'Content of the article',
            'class' => 'vmfh_textarea',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 16
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
            'order' => 20,
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
            'order' => 25,
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
            'order' => 30,
            'validators' => array(
                array('StringLength', false, array(0, 512)),
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/'))
            )
        )),
        'display_orderby' => array('Text', array(
            'label' => 'Display Priority',
            'description' => '(Float)',
            'order' => 35,
            'validators' => array(
                array('Float')
            )
        )),
        'display_stack' => array('Select', array(
            'label' => 'Display Stack',
            'description' => 'Select Display Options or Parent',
            'order' => 40
        )),
        'secondary' => array('Text', array(
            'label' => 'Secondary Widget Display',
            'description' => '256 Character(s) Remaining',
            'maxlength' => 256,
            'onfocus' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'onkeyup' => "YAHOO.viame.textarea_limiter.check(this, 256);",
            'order' => 42,
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
            'order' => 43,
            'validators' => array(
                array('StringLength', false, array(1, 512))
            )
        )),
        'widget' => array('Checkbox', array(
            'label' => 'Display Widget',
            'description' => 'Display the module widget',
            'value' => 1,
            'order' => 45
        )),
        'widget_hide_summary' => array('Checkbox', array(
            'label' => 'Hide Widget Summary',
            'description' => 'Hide summary in the widget',
            'order' => 50
        )),
        'widget_max' => array('Text', array(
            'label' => 'Widget Max',
            'description' => 'Max # Items To Display In Widget',
            'order' => 55,
            'validators' => array(
                'Int'
            )
        )),
        'publicly_searchable' => array('Checkbox', array(
            'label' => 'Publicly Searchable',
            'description' => 'Module contents are publicly searchable',
            'value' => 1,
            'order' => 60
        )),
        'interactive' => array('Checkbox', array(
            'label' => 'Interactive',
            'description' => 'Allow comments and ratings',
            'value' => 1,
            'order' => 65
        )),
        'moderated' => array('Checkbox', array(
            'label' => 'Moderated',
            'description' => 'Moderate comments',
            'value' => 0,
            'order' => 70
        )),
        'status' => array('Radio', array(
            'label' => 'Status',
            #'description' => 'Publish Status',
            'MultiOptions' => array(
                '1' => 'Active',
                '0' => 'Inactive'
            ),
            'value' => 1,
            'order' => 75
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show Access Link',
            'description' => 'Show When Access Denied',
            'value' => 0,
            'order' => 80
        ))
    )
);
