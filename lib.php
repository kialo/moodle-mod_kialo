<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Library of interface functions and constants.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | string | null Truthy if the feature is supported, null otherwise.
 */
function kialo_supports($feature)
{
    switch ($feature) {
        case FEATURE_BACKUP_MOODLE2:
            return true;

        case FEATURE_MOD_INTRO:
            return false;

        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return true;

        case FEATURE_GRADE_HAS_GRADE:
            return true;

        default:
            return null;
    }
}

/**
 * Prevent the Kialo icon from having its colors modified on Moodle >= 4.4.
 */
function kialo_is_branded(): bool
{
    return true;
}

/**
 * Saves a new instance of the mod_kialo into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_kialo_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function kialo_add_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('kialo', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_kialo in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_kialo_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function kialo_update_instance($moduleinstance, $mform = null)
{
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('kialo', $moduleinstance);
}

/**
 * Removes an instance of the mod_kialo from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function kialo_delete_instance($id)
{
    global $DB;

    $exists = $DB->get_record('kialo', ['id' => $id]);
    if (!$exists) {
        return false;
    }

    $DB->delete_records('kialo', ['id' => $id]);

    return true;
}

/**
 * Given a coursemodule object, this function returns the extra
 * information needed to print this activity in various places.
 * For this module we just need to support external urls as
 * activity icons
 *
 * @param stdClass $coursemodule
 * @return cached_cm_info info
 */
function kialo_get_coursemodule_info($coursemodule)
{
    $info = new cached_cm_info();

    $url = new moodle_url('/mod/kialo/view.php', ['id' => $coursemodule->id]);
    $info->onclick = sprintf("window.open('%s'); return false;", $url->out(false));

    return $info;
}

/**
 * Callback method executed prior to enabling the activity module.
 *
 * @return bool Whether to proceed and enable the plugin or not.
 */
function kialo_pre_enable_plugin_actions(): bool
{
    // If the admin hasn't accepted the terms of service, don't enable the plugin.
    $acceptterms = get_config('mod_kialo', 'acceptterms');

    if (!$acceptterms) {
        return false;
    }

    // Otherwise, continue and enable the plugin.
    return true;
}

/**
 * Ensures the activity is only enabled when the terms have been accepted.
 *
 * @return void
 * @throws dml_exception
 */
function kialo_update_visibility_depending_on_accepted_terms(): void
{
    global $DB;

    $visible = get_config('mod_kialo', 'acceptterms') ? 1 : 0;

    if (class_exists('core\plugininfo\mod') && method_exists('core\plugininfo\mod', 'enable_plugin')) {
        // Moodle 4.0+.
        \core\plugininfo\mod::enable_plugin("kialo", $visible);
    } else {
        // Moodle 3.9 and older.
        $DB->set_field('modules', 'visible', $visible, ['name' => 'kialo']);

        // Ensure that the plugin status (Enabled/Disabled) is updated correctly in Plugins overview.
        \core_plugin_manager::instance()->reset_caches();
    }
}
