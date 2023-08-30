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
 * Structure step to restore one kialo activity.
 *
 * @package    mod_kialo
 * @category   backup
 * @copyright  2023 Kialo GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_kialo_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the kialo activity restore.
     * @return mixed
     */
    protected function define_structure() {
        $paths = array();
        $paths[] = new restore_path_element('kialo', '/activity/kialo');
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the kialo element.
     * @param mixed $data
     * @return void
     * @throws base_step_exception
     * @throws dml_exception
     */
    protected function process_kialo($data) {
        global $DB;

        $data = (object) $data;
        $data->course = $this->get_courseid();

        $newitemid = $DB->insert_record('kialo', $data);
        $this->apply_activity_instance($newitemid);
    }
}
