<?php

// see https://moodledev.io/general/app/development/plugins-development-guide

$addons = [
        'mod_kialo' => [
                'handlers' => [
                        'kialo' => [
                                'delegate' => 'CoreMainMenuDelegate',
                                'method' => 'view_kialo',
                                'displaydata' => [
                                ],
                        ],
                ],
                'lang' => [
                        ['hello', 'kialo'],
                ],
        ],
];
