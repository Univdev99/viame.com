<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'network_form',
        'id' => 'network_form',
        'method' => 'post',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.network_form] }) && YAHOO.viame.dubsub.check(this));'
    ),
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'name' => array('Text', array(
            'label' => 'Network Name',
            'description' => 'Network name',
            'required' => true,
            'maxlength' => 98,
            'validators' => array(
                array('StringLength', false, array(4, 98)),
                array('Regex', false, array(
                    'pattern' => '/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/',
                    'messages' => array(
                        Zend_Validate_Regex::NOT_MATCH => 'network_form_regexNotMatch'
                    )
                ))
            ),
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="network name", message="The {label} field cannot be empty.", groups=[network_form])',
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
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/'))
            )
        )),
        'category' => array('Text', array(
            'label' => 'Network Category',
            'description' => 'Network Category',
            'order' => 25
        )),
        'parent_id' => array('Select', array(
            'label' => 'Parent Group',
            'description' => 'Parent Group',
            'order' => 30
        )),
        'public' => array('Checkbox', array(
            'label' => 'Public',
            'description' => 'Network is not hidden and will be displayed',
            'order' => 35
        )),
        'open' => array('Checkbox', array(
            'label' => 'Open',
            'description' => 'Anyone can join network',
            'value' => true,
            'order' => 40
        )),
        'password' => array('Text', array(
            'label' => 'Password',
            'description' => 'Required password to join network',
            'maxlength' => 256,
            'order' => 45,
            'validators' => array(
                array('StringLength', false, array(1, 256))
            )
        )),
        'allow_requests' => array('Checkbox', array(
            'label' => 'Allow Join Requests',
            'description' => 'Allow Join Requests',
            'value' => true,
            'order' => 50
        )),
        'show_on_fail' => array('Checkbox', array(
            'label' => 'Show When Access Denied',
            'description' => 'Show when access denied',
            'value' => 0,
            'order' => 55
        )),
        'page_width' => array('Select', array(
            'label' => 'Page Width',
            'description' => 'Page Width',
            'order' => 60,
            'multiOptions' => array(
                '' => 'Select Page Width',
                'doc' => '750px',
                'doc2' => '950px',
                'doc4' => '974px',
                'doc3' => '100%'
            )
        )),
        'page_layout' => array('Select', array(
            'label' => 'Page Layout',
            'description' => 'Page Layout',
            'order' => 65,
            'multiOptions' => array(
                '' => 'Select Layout',
                't1' => 'Fixed Left 160px',
                't2' => 'Fixed Left 180px',
                't3' => 'Fixed Left 300px',
                't4' => 'Fixed Right 180px',
                't5' => 'Fixed Right 240px',
                't6' => 'Fixed Right 300px'
            )
        )),
        'page_sublayout' => array('Select', array(
            'label' => 'Page Sub Layout',
            'description' => 'Page Sub Layout',
            'order' => 70,
            'multiOptions' => array(
                '' => 'Select Sub-Layout',
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
            'description' => 'Page Theme',
            'order' => 75,
            'multiOptions' => array(
                'default' => 'Default'
            )
        )),
        'page_style' => array('Textarea', array(
            'label' => 'Page Style',
            'description' => 'CSS to be included in your space',
            'cols' => 60,
            'rows' => 5,
            'order' => 80
        ))        
    )
);
