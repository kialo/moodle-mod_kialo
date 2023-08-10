<?php

class mod_kialo_generator extends testing_module_generator {
    public function create_instance($record = null, array $options = null): stdClass {
        $record = (object) (array) $record;

        // Set some useful defaults for tests.
        if (!isset($record->deployment_id)) {
            $record->deployment_d = "random string 1234";
        }
        if (!isset($record->discussion_title)) {
            $record->discussion_title = "Test discussion";
        }
        if (!isset($record->discussion_url)) {
            $record->discussion_url = "https://www.kialo-edu.com/42";
        }

        return parent::create_instance($record, $options);
    }
}
