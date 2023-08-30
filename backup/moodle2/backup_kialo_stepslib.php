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
 * Kialo backup step.
 *
 * @package    mod_kialo
 * @subpackage backup-moodle2
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @see https://docs.moodle.org/dev/Backup_2.0_for_developers
 */

/**
 * Define all the backup steps that will be used by the backup_choice_activity_task
 */
class backup_kialo_activity_structure_step extends backup_activity_structure_step {

    /**
     * Define the backup structure for the kialo activity.
     * @return backup_nested_element
     * @throws base_element_struct_exception
     */
    protected function define_structure() {
        // We just back up everything. See install.xml for fields that need to be backed up.
        $kialo = new backup_nested_element('kialo', ['id'], [
                'course', 'name', 'timecreated', 'timemodified', 'intro', 'introformat', 'discussion_title',
                'deployment_id']);
        $kialo->set_source_table('kialo', array('id' => backup::VAR_ACTIVITYID));

        // Currently we don't need any annotations.
        // If at some point our data refers to users, groups, groupings, roles, scales, outcomes, or files,
        // refer to https://docs.moodle.org/dev/Backup_2.0_for_developers#annotate_is_important.

        return $this->prepare_activity_structure($kialo);
    }
}
