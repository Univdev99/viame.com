<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'profile_form',
        'method' => 'post',
        'id' => 'profile_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.profile_form] }) && YAHOO.viame.dubsub.check(this));'
    ),
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'name' => array('Text', array(
            'label' => 'Profile Name',
            'description' => 'Displayed name of the profile',
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
            'data-constraints' => '@Required(label="profile name", message="The {label} field cannot be empty.", groups=[profile_form])',
            'order' => 5
        )),
        'meta_title' => array('Text', array(
            'label' => 'Title',
            'description' => '(256 Chars Max)',
            'maxlength' => 256,
            'order' => 10,
            'validators' => array(
                array('StringLength', false, array(0, 256))
            )
        )),
        'meta_description' => array('Text', array(
             'label' => 'Description',
            'description' => '(512 Chars Max)',
            'maxlength' => 512,
            'order' => 15,
            'validators' => array(
                array('StringLength', false, array(0, 512))
            )
        )),
        'meta_keywords' => array('Text', array(
            'label' => 'Keywords',
            'description' => 'Comma Separated (512 Chars Max)',
            'maxlength' => 512,
            'order' => 20,
            'validators' => array(
                array('StringLength', false, array(0, 512)),
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/u'))
            )
        )),
        'same_member_priv' => array('Checkbox', array(
            'label' => 'Flow Privileges',
            'description' => 'Other profiles created by you will have similar owner privileges to this space.',
            'value' => 1,
            'order' => 25
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show When Access Denied',
            'description' => 'Show when access denied',
            'value' => 0,
            'order' => 30
        )),
        'editor_preference' => array('Select', array(
            'label' => 'Editor Preference',
            'description' => 'Default HTML Editor',
            'order' => 35,
            'multiOptions' => array(
                '-1' => 'None (No WYSIWYG Editor)',
                ''   => 'Redactor (Default)',
                '1'  => 'TinyMCE Editor',
                '2'  => 'FCK Editor',
		        '3'  => 'CK Editor',
		        '4'  => 'openWYSIWYG',
		        '5'  => 'Yahoo! Rich Text Editor',
		        #'6'  => 'elRTE',
		        '7'  => 'NicEdit'
            )
        )),
        'page_width' => array('Select', array(
            'label' => 'Page Width',
            'description' => 'Total page width of your space',
            'order' => 40,
            'multiOptions' => array(
                '' => 'Default',
                'doc' => '750px',
                'doc2' => '950px',
                'doc4' => '974px',
                'doc3' => '100%'
            )
        )),
        'page_layout' => array('Select', array(
            'label' => 'Fixed Column',
            'description' => 'Include a fixed column that always displays',
            'order' => 45,
            'multiOptions' => array(
                '' => 'None',
                't1' => 'Fixed Left 160px',
                't2' => 'Fixed Left 180px',
                't3' => 'Fixed Left 300px',
                't4' => 'Fixed Right 180px',
                't5' => 'Fixed Right 240px',
                't6' => 'Fixed Right 300px'
            )
        )),
        'page_sublayout' => array('Select', array(
            'label' => 'Sub-Layout',
            'description' => 'Divide your home into multiple columns',
            'order' => 50,
            'multiOptions' => array(
                '' => 'None',
                'g' => 'Nested 1/2, 1/2',
                'gb' => 'Nested 1/3, 1/3, 1/3',
                'gc' => 'Nested 2/3, 1/3',
                'gd' => 'Nested 1/3, 2/3',
                'ge' => 'Nested 3/4, 1/4',
                'gf' => 'Nested 1/4, 3/4',
                'gg' => 'Nested 1/4, 1/4, 1/4, 1/4'
            )
        )),
        'page_theme' => array('Select', array(
            'label' => 'Page Theme',
            'description' => 'Theme to be applied to your space',
            'order' => 55,
            'multiOptions' => array(
                'default' => 'Default'
            )
        )),
        'page_style' => array('Textarea', array(
            'label' => 'Page Style',
            'description' => 'CSS to be included in your space',
            'cols' => 60,
            'rows' => 5,
            'order' => 55
        )),
        'status' => array('Radio', array(
            'label' => 'Status',
            #'description' => 'Publish Status',
            'MultiOptions' => array(
                '1' => 'Active',
                '0' => 'Inactive'
            ),
            'value' => 1,
            'order' => 65
        ))
    )
);
