<?php

namespace local_hello\output;

defined('MOODLE_INTERNAL') || die();

class mobile {

    public static function view_kialo() {
        return [
                'templates' => [
                        [
                                'id' => 'main',
                                'html' => '<h1 class="text-center">{{ "plugin.local_hello.hello" | translate }}</h1>',
                        ],
                ],
        ];
    }

}
