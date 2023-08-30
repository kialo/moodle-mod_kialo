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
 * The task that provides all the steps to perform a complete backup is defined here.
 *
 * @package     mod_kialo
 * @category    backup
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/kialo/backup/moodle2/backup_kialo_stepslib.php');

/**
 * Implements Moodle's backup interface.
 */
class backup_kialo_activity_task extends \backup_activity_task {

    /**
     * This plugin has no backup settings.
     */
    protected function define_my_settings() {
        return;
    }

    /**
     * Defines the single backup step.
     * @return void
     * @throws base_task_exception
     */
    protected function define_my_steps() {
        $this->add_step(new backup_kialo_activity_structure_step('kialo_structure', 'kialo.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of choices.
        $search = "/(" . $base . "\/mod\/kialo\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@KIALOINDEX*$2@$', $content);

        // Link to choice view by moduleid.
        $search = "/(" . $base . "\/mod\/kialo\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@KIALOVIEWBYID*$2@$', $content);

        return $content;
    }
}
