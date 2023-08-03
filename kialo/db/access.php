<?php

$capabilities = [
        // Add a Kialo activity to a course.
        'mod/kialo:addinstance' => [
                'riskbitmask' => RISK_SPAM,
                'captype' => 'write',
                'contextlevel' => CONTEXT_COURSE,
                'archetypes' => [
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                ],
                'clonepermissionsfrom' => 'moodle/course:manageactivities',
        ],

        // Whether the user can see the link to the Kialo discussion and follow it.
        'mod/kialo:view' => [
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => [
                        'student' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                ],
        ],

        // When the user arrives at Kialo, if they have this capability
        // in Moodle, then they are given the Admin role in the linked Kialo discussion.
        // Otherwise they are given Writer permissions. See lti_flow::assign_lti_roles.
        'mod/kialo:kialo_admin' => [
            'captype' => 'write',
            'contextlevel' => CONTEXT_MODULE,
            'archetypes' => [
                'editingteacher' => CAP_ALLOW,
                'teacher' => CAP_ALLOW,
                'manager' => CAP_ALLOW,
            ],
        ],
];
