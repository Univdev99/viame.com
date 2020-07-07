<?php
/*
    ViaMe Application
    Levelogic, Inc. (http://www.levelogic.com)
*/

$form_config = array(
    'attribs' => array(
        'name' => 'mail_folder_form',
        'method' => 'post',
        'id' => 'mail_folder_form',
        'class' => 'form'
    ),
    'elements' => array(
        'name' => array('Text', array(
            'label' => 'Folder Name',
            'description' => 'Folder Name',
            'required' => true,
            'order' => 5,
            'validators' => array(
                array('StringLength', false, array(1, 98)),
                array('Regex', false, array('/^[\p{L}\p{M}\p{N}\p{P}\p{Zs}]*$/'))
            )
        )),
        'description' => array('Textarea', array(
            'label' => 'Folder Description',
            'description' => 'Folder Description',
            'filters' => array(
                array('HTMLPurify')
            ),
            'order' => 10
        )),
        'parent_id' => array('Select', array(
            'label' => 'Parent Group',
            'description' => 'Parent Group',
            'order' => 15
        ))
    )
);