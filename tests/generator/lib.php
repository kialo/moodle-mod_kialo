<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Test lib for the Kialo activity module tests.
 *
 * @package    mod_kialo
 * @copyright  2023 Kialo GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kialo_generator extends testing_module_generator {

    /**
     * Create a new instance of the Kialo activity module, setting some useful default placeholder values for tests.
     * @param stdClass $record
     * @param array|null $options
     * @return stdClass
     * @throws coding_exception
     */
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
