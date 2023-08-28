<?php

class mod_kialo_generator extends testing_module_generator {
    public function create_instance($record = null, array $options = null): stdClass {
        $record = (object) (array) $record;

        // Set some useful defaults for tests.
        if (!isset($record->name)) {
            $record->name = "Some Kialo Discussion Activity";
        }
        if (!isset($record->deployment_id)) {
            $record->deployment_d = "random string 1234";
        }
        if (!isset($record->discussion_title)) {
            $record->discussion_title = "Test discussion";
        }

        return parent::create_instance($record, $options);
    }
}
