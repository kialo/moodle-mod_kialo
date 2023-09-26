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
 * Kialo restore task that provides all the settings and steps to perform one complete restore of the activity.
 *
 * @package    mod_kialo
 * @category   backup
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/kialo/backup/moodle2/restore_kialo_stepslib.php');

/**
 * Implementation of the Moodle Restore API for the Kialo plugin.
 */
class restore_kialo_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Kialo only has one structure step.
        $this->add_step(new restore_kialo_activity_structure_step('kialo_structure', 'kialo.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    public static function define_decode_contents(): array {
        $contents = [];

        // We don't actually use the intro field right now, but since it's a default field we handle it here just in case
        // we are going to use it at some point.
        $contents[] = new restore_decode_content('kialo', ['intro'], 'kialo');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    public static function define_decode_rules() {
        $rules = [];

        $rules[] = new restore_decode_rule('KIALOVIEWBYID', '/mod/kialo/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('KIALOINDEX', '/mod/kialo/index.php?id=$1', 'course');

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the restore_logs_processor when restoring
     * kialo logs. It must return one array
     * of restore_log_rule objects
     */
    public static function define_restore_log_rules(): array {
        $rules = [];

        $rules[] = new restore_log_rule('kialo', 'add', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'update', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'view', 'view.php?id={course_module}', '{kialo}');
        $rules[] = new restore_log_rule('kialo', 'report', 'report.php?id={course_module}', '{kialo}');

        return $rules;
    }
}
