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
 * Upgrade steps for the Kialo activity module.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * In this version grading was first introduced.
 */
const VERSION_GRADING_1 = 2024091805;

/**
 * Custom upgrade steps.
 * @param int $oldversion
 */
function xmldb_kialo_upgrade($oldversion = 0): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < VERSION_GRADING_1) {
        // Define field id to be added to kialo.
        $table = new xmldb_table('kialo');
        $field = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, false, 100, null);

        // Conditionally launch add field id.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Kialo savepoint reached.
        upgrade_mod_savepoint(true, VERSION_GRADING_1, 'kialo');
    }

    return true;
}
