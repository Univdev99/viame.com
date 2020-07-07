<?php
/**
 * ViaMe Application
 *
 * @copyright  Copyright (c) 2008 Levelogic, Inc. (http://www.levelogic.com)
 */

$form_config = array(
    'attribs' => array(
        'action' => '?',
        'name' => 'info_form',
        'id' => 'info_form',
        'class' => 'form'
    )
);

$form_subforms_config = array(
    'basic' => array(
        'attribs' => array(
            'name' => 'info_form_basic',
            'id' => 'info_form_basic',
            'class' => 'subform',
            'legend' => 'Basic'
        ),
        'elements' => array(
            'gender' => array('Select', array(
                'label' => 'Gender',
                'description' => 'Your gender',
                'MultiOptions' => array(
                    '' => 'Select Gender:',
                    'M' => 'Male',
                    'F' => 'Female'
                ),
                'order' => 5
            )),
            'birthday' => array('Text', array(
                'label' => 'Birthday',
                'description' => 'Birthday',
                'class' => 'vmfh_date',
                'order' => 10,
                'validators' => array(
                    array('Date', false)
                )
            )),
            'former_name' => array('Text', array(
                'label' => 'Former/Maiden Name (aka)',
                'description' => 'Former/Maiden Name (aka)',
                'order' => 12,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'hometown' => array('Text', array(
                'label' => 'Hometown',
                'description' => 'Hometown',
                'order' => 15,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'political_views' => array('Text', array(
                'label' => 'Political Views',
                'description' => 'Political Views',
                'order' => 20,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'Religious Views' => array('Text', array(
                'label' => 'Religious Views',
                'description' => 'Religious Views',
                'order' => 25,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            ))
        ),
        'order' => 10
    ),
    'contact' => array(
        'attribs' => array(
            'name' => 'info_form_contact',
            'id' => 'info_form_contact',
            'class' => 'subform',
            'legend' => 'Contact'
        ),
        'elements' => array(
            'email' => array('Text', array(
                'label' => 'Email',
                'description' => 'Email Address',
                'order' => 5,
                'validators' => array(
                    array('EmailAddress', false)
                )
            )),
            'im_id' => array('Text', array(
                'label' => 'Instant Messaging ID',
                'description' => 'Instant Messaging ID',
                'order' => 10,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'im_client' => array('Select', array(
                'label' => 'Instant Messaging Client',
                'description' => 'Instant Messaging Client',
                'MultiOptions' => array(
                    'AIM' => 'AIM',
                    'Google' => 'Google',
                    'Skype' => 'Skype',
                    'MSN' => 'MSN',
                    'Yahoo' => 'Yahoo',
                    'Gadu Gadu' => 'Gadu Gadu',
                    'ICQ' => 'ICQ',
                    'Jabber' => 'Jabber'
                ),
                'order' => 15
            )),
            'phone_type' => array('Select', array(
                'label' => 'Phone Type',
                'description' => 'Phone Type',
                'MultiOptions' => array(
                    'Home' => 'Home',
                    'Work' => 'Work',
                    'Mobile' => 'Mobile',
                    'FAX' => 'FAX',
                    'Other' => 'Other'
                ),
                'order' => 20
            )),
            'phone_number' => array('Text', array(
                'label' => 'Phone Number',
                'description' => 'Phone Number',
                'order' => 25,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'address' => array('Text', array(
                'label' => 'Address',
                'description' => 'Address',
                'order' => 30,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'city' => array('Text', array(
                'label' => 'City/Town',
                'description' => 'City/Town',
                'order' => 30,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'state' => array('Text', array(
                'label' => 'State',
                'description' => 'State',
                'order' => 35,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'postal_code' => array('Text', array(
                'label' => 'Postal Code',
                'description' => 'Postal Code',
                'order' => 40,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'website' => array('Text', array(
                'label' => 'Website',
                'description' => 'Website',
                'order' => 45,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            ))
        ),
        'order' => 20
    ),
    'relationships' => array(
        'attribs' => array(
            'name' => 'info_form_relationships',
            'id' => 'info_form_relationships',
            'class' => 'subform',
            'legend' => 'Relationships'
        ),
        'elements' => array(
            'relationship_status' => array('Select', array(
                'label' => 'Relationship Status',
                'description' => 'Relationship Status',
                'MultiOptions' => array(
                    'Single' => 'Single',
                    'In A Relationship' => 'In A Relationship',
                    'In An Open Relationship' => 'In An Open Relationship',
                    'Engaged' => 'Engaged',
                    'Married' => 'Married',
                    'Widowed' => 'Widowed',
                    "It's Complicated" => "It's Complicated"
                ),
                'order' => 5
            )),
            'interested_in' => array('Select', array(
                'label' => 'Interested In',
                'description' => 'Interested In',
                'MultiOptions' => array(
                    'M' => 'Male',
                    'F' => 'Female'
                ),
                'order' => 15
            )),
            'looking_for' => array('Select', array(
                'label' => 'Looking For',
                'description' => 'Looking For',
                'MultiOptions' => array(
                    'Friendship' => 'Friendship',
                    'Dating' => 'Dating',
                    'A Relationship' => 'A Relationship',
                    'Social Networking' => 'Social Networking',
                    'Professional Networking' => 'Professional Networking'
                ),
                'order' => 20
            ))
        ),
        'order' => 30
    ),
    'family' => array(
        'attribs' => array(
            'name' => 'info_form_family',
            'id' => 'info_form_family',
            'class' => 'subform',
            'legend' => 'Family'
        ),
        'elements' => array(
        ),
        'order' => 33
    ),
    'personal' => array(
        'attribs' => array(
            'name' => 'info_form_personal',
            'id' => 'info_form_personal',
            'class' => 'subform',
            'legend' => 'Personal'
        ),
        'elements' => array(
            'about_me' => array('Textarea', array(
                'label' => 'About Me',
                'description' => 'About Me',
                'order' => 5
            )),
            'activities' => array('Textarea', array(
                'label' => 'Activities',
                'description' => 'Activities',
                'order' => 10
            )),
            'interests' => array('Textarea', array(
                'label' => 'Interests',
                'description' => 'Interests',
                'order' => 15
            )),
            'groups' => array('Textarea', array(
                'label' => 'Groups, Clubs, or Organizations',
                'description' => 'Groups, Clubs, or Organizations',
                'order' => 20
            )),
            'music' => array('Textarea', array(
                'label' => 'Favorite Music',
                'description' => 'Favorite Music',
                'order' => 25
            )),
            'shows' => array('Textarea', array(
                'label' => 'Favorite TV Shows',
                'description' => 'Favorite TV Shows',
                'order' => 30
            )),
            'movies' => array('Textarea', array(
                'label' => 'Favorite Movies',
                'description' => 'Favorite Movies',
                'order' => 35
            )),
            'books' => array('Textarea', array(
                'label' => 'Favorite Books',
                'description' => 'Favorite Books',
                'order' => 40
            )),
            'quotes' => array('Textarea', array(
                'label' => 'Favorite Quotes',
                'description' => 'Favorite Quotes',
                'order' => 45
            )),
            'charities' => array('Textarea', array(
                'label' => 'Favorite Charities',
                'description' => 'Favorite Charities',
                'order' => 50
            )),
            'expertise' => array('Textarea', array(
                'label' => 'Areas of Expertise',
                'description' => 'Areas of Expertise',
                'order' => 55
            )),
            'other' => array('Textarea', array(
                'label' => 'Other Information',
                'description' => 'Other Information',
                'order' => 60
            ))
        ),
        'order' => 40
    ),
    'professional' => array(
        'attribs' => array(
            'name' => 'info_form_professional',
            'id' => 'info_form_professional',
            'class' => 'subform',
            'legend' => 'Professional'
        ),
        'elements' => array(
            'about_me' => array('Textarea', array(
                'label' => 'About Me',
                'description' => 'About Me',
                'order' => 5
            )),
            'activities' => array('Textarea', array(
                'label' => 'Activities',
                'description' => 'Activities',
                'order' => 10
            )),
            'interests' => array('Textarea', array(
                'label' => 'Interests',
                'description' => 'Interests',
                'order' => 15
            )),
            'groups' => array('Textarea', array(
                'label' => 'Groups, Clubs, or Organizations',
                'description' => 'Groups, Clubs, or Organizations',
                'order' => 20
            )),
            'music' => array('Textarea', array(
                'label' => 'Favorite Music',
                'description' => 'Favorite Music',
                'order' => 25
            )),
            'shows' => array('Textarea', array(
                'label' => 'Favorite TV Shows',
                'description' => 'Favorite TV Shows',
                'order' => 30
            )),
            'movies' => array('Textarea', array(
                'label' => 'Favorite Movies',
                'description' => 'Favorite Movies',
                'order' => 35
            )),
            'books' => array('Textarea', array(
                'label' => 'Favorite Books',
                'description' => 'Favorite Books',
                'order' => 40
            )),
            'quotes' => array('Textarea', array(
                'label' => 'Favorite Quotes',
                'description' => 'Favorite Quotes',
                'order' => 45
            )),
            'charities' => array('Textarea', array(
                'label' => 'Favorite Charities',
                'description' => 'Favorite Charities',
                'order' => 50
            )),
            'expertise' => array('Textarea', array(
                'label' => 'Areas of Expertise',
                'description' => 'Areas of Expertise',
                'order' => 55
            )),
            'other' => array('Textarea', array(
                'label' => 'Other Information',
                'description' => 'Other Information',
                'order' => 60
            ))
        ),
        'order' => 50
    ),
    'education' => array(
        'attribs' => array(
            'name' => 'info_form_education',
            'id' => 'info_form_education',
            'class' => 'subform',
            'legend' => 'Education'
        ),
        'elements' => array(
            'school' => array('Text', array(
                'label' => 'School Name',
                'description' => 'School Name',
                'order' => 5,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'former_name' => array('Text', array(
                'label' => 'Former/Maiden Name',
                'description' => 'Former/Maiden Name',
                'order' => 10,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'interested_in' => array('Select', array(
                'label' => 'Interested In',
                'description' => 'Interested In',
                'MultiOptions' => array(
                    'M' => 'Male',
                    'F' => 'Female'
                ),
                'order' => 15
            )),
            'looking_for' => array('Select', array(
                'label' => 'Looking For',
                'description' => 'Looking For',
                'MultiOptions' => array(
                    'Friendship' => 'Friendship',
                    'Dating' => 'Dating',
                    'A Relationship' => 'A Relationship',
                    'Social Networking' => 'Social Networking',
                    'Professional Networking' => 'Professional Networking'
                ),
                'order' => 20
            ))
        ),
        'order' =>60
    ),
    'employment' => array(
        'attribs' => array(
            'name' => 'info_form_employment',
            'id' => 'info_form_employment',
            'class' => 'subform',
            'legend' => 'Employment'
        ),
        'elements' => array(
            'employer' => array('Text', array(
                'label' => 'Employer',
                'description' => 'Employer',
                'order' => 5,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'location' => array('Text', array(
                'label' => 'Location',
                'description' => 'City/Town, State/Province, Country',
                'order' => 10,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'position' => array('Text', array(
                'label' => 'Position',
                'description' => 'Position',
                'order' => 15,
                'validators' => array(
                    array('StringLength', false, array(0, 256))
                )
            )),
            'description' => array('Textarea', array(
                'label' => 'Description',
                'description' => 'Description',
                'order' => 20
            )),
            'skill_sets' => array('Textarea', array(
                'label' => 'Skill Sets',
                'description' => 'Skill Sets',
                'order' => 25
            )),
            'start' => array('Text', array(
                'label' => 'Start Date',
                'description' => 'Start Date',
                'class' => 'vmfh_date',
                'order' => 30,
                'validators' => array(
                    array('Date', false)
                )
            )),
            'end' => array('Text', array(
                'label' => 'End Date',
                'description' => 'End Date',
                'class' => 'vmfh_date',
                'order' => 35,
                'validators' => array(
                    array('Date', false)
                )
            )),
            'Organizations' => array('Textarea', array(
                'label' => 'Organizations',
                'description' => 'Professional groups, clubs, and/or organizations.',
                'order' => 40
            )),
            'Expertise' => array('Textarea', array(
                'label' => 'Expertise',
                'description' => 'Expertise',
                'order' => 45
            ))
        ),
        'order' => 70
    ),
    'picture' => array(
        'attribs' => array(
            'name' => 'info_form_picture',
            'id' => 'info_form_picture',
            'class' => 'subform',
            'legend' => 'Picture'
        ),
        'elements' => array(
            'headshot' => array('File', array(
                'label' => 'Headshot',
                'description' => 'Headshot',
                'order' => 5
            ))
        ),
        'order' => 80
    )
);