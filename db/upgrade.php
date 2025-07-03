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

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../constants.php');

/**
 * In this version grading was first introduced.
 */
const VERSION_GRADING_1 = 2024091805;

/**
 * In this version display options (embed vs new window) were first introduced.
 */
const VERSION_DISPLAY_OPTIONS_1 = 2025012402;

/**
 * In this version the resource_link_id_history field was first introduced.
 */
const VERSION_RESOURCE_LINK_ID_HISTORY_1 = 2025070301;

/**
 * Custom upgrade steps.
 * @param int $oldversion
 */
function xmldb_kialo_upgrade($oldversion = 0): bool {
    global $CFG, $DB;

    $dbman = $DB->get_manager();
    $table = new xmldb_table('kialo');

    if ($oldversion < VERSION_GRADING_1) {
        // Define field 'grade' to be added to kialo.
        $gradefield = new xmldb_field('grade', XMLDB_TYPE_INTEGER, '10', false, XMLDB_NOTNULL, false, 100, null);

        // Conditionally launch add field 'grade'.
        if (!$dbman->field_exists($table, $gradefield)) {
            $dbman->add_field($table, $gradefield);
        }

        // Kialo savepoint reached.
        upgrade_mod_savepoint(true, VERSION_GRADING_1, 'kialo');
    }

    if ($oldversion < VERSION_DISPLAY_OPTIONS_1) {
        // Define field 'display' to be added to kialo.
        $displayfield = new xmldb_field(
            'display',
            XMLDB_TYPE_CHAR,
            '16',
            false,
            XMLDB_NOTNULL,
            false,
            MOD_KIALO_DISPLAY_IN_EMBED,
            null
        );

        // Conditionally launch add field 'display'.
        if (!$dbman->field_exists($table, $displayfield)) {
            $dbman->add_field($table, $displayfield);
        }

        // Kialo savepoint reached.
        upgrade_mod_savepoint(true, VERSION_DISPLAY_OPTIONS_1, 'kialo');
    }

    if ($oldversion < VERSION_RESOURCE_LINK_ID_HISTORY_1) {
        $displayfield = new xmldb_field('resource_link_id_history', XMLDB_TYPE_TEXT);

        if (!$dbman->field_exists($table, $displayfield)) {
            $dbman->add_field($table, $displayfield);
        }

        upgrade_mod_savepoint(true, VERSION_RESOURCE_LINK_ID_HISTORY_1, 'kialo');
    }

    return true;
}
