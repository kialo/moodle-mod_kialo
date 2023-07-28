<?php

$capabilities = [
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
        'mod/kialo:view' => [
                'captype' => 'read',
                'contextlevel' => CONTEXT_MODULE,
                'archetypes' => [
                        'guest' => CAP_ALLOW, # TODO PM-41849: Should guests (users without accounts) have access to the discussion?
                        'student' => CAP_ALLOW,
                        'teacher' => CAP_ALLOW,
                        'editingteacher' => CAP_ALLOW,
                        'manager' => CAP_ALLOW,
                ],
        ],
];
