<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'name' => 'comment_form',
        'method' => 'post',
        'id' => 'comment_form',
        'class' => 'form',
        'onsubmit' => 'return (YAHOO.viame.vivin_regula.validate(this, { groups: [regula.Group.comment_form] }) && YAHOO.viame.dubsub.check(this));'
    ),
    'elementPrefixPath' => array(
        'prefix' => 'ViaMe',
        'path' => 'ViaMe/'
    ),
    'elements' => array(
        'title' => array('Text', array(
            'label' => 'Title',
            #'description' => '(256 Chars Max)',
            'maxlength' => 256,
            'required' => true,
            'class' => 'regula-validation',
            'data-constraints' => '@Required(label="title", message="The {label} field cannot be empty.", groups=[comment_form])',
            'validators' => array(
                array('StringLength', false, array(0, 256))
            ),
            'order' => 5
        )),
        'content' => array('Textarea', array(
            'label' => 'Content',
            #'description' => 'Content of the comment',
            'required' => true,
            'class' => 'vmfh_simple_textarea',
            'cols' => 45,
            'rows' => 5,
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 10
        ))
    )
);
