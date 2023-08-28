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
 * mod_kialo backup & restore functionality test.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onward, Kialo GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo\backup;

use advanced_testcase;
use backup;
use backup_controller;
use base_setting;
use restore_controller;
use restore_dbops;
use stdClass;

/**
 * Tests that a Kialo activity can be backed up and restored.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 Kialo GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_and_restore_test extends advanced_testcase {

    /**
     * Setup to ensure that fixtures are loaded.
     */
    public static function setupbeforeclass(): void {
        global $CFG;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
    }

    /**
     * Test on Kialo activity backup and restore.
     */
    public function test_backup_restore() {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();

        // Create one activity.
        $this->assertFalse($DB->record_exists('kialo', ['course' => $course->id]));
        $activity = $this->getDataGenerator()->create_module('kialo', [
                'course' => $course,
                'name' => 'Some Kialo Discussion Activity',
                'deployment_id' => '42lashf13.34ih',
                'discussion_title' => 'Test discussion',
        ]);

        // Execute course backup and restore.
        $newcourseid = $this->backup_and_restore($course, false);

        // Compare original and restored activities.
        $activity2 = $DB->get_record('kialo', ['course' => $newcourseid]);
        $this->assertEquals($newcourseid, $activity2->course);
        $this->assertEquals($activity->name, $activity2->name);
        $this->assertEquals($activity->intro, $activity2->intro);
        $this->assertEquals($activity->introformat, $activity2->introformat);
        $this->assertEquals($activity->deployment_id, $activity2->deployment_id);
    }

    /**
     * Backs a course up and restores it.
     *
     * @param stdClass $srccourse Course object to backup
     * @return int ID of newly restored course
     */
    private function backup_and_restore(stdClass $srccourse): int {
        global $USER, $CFG;

        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

        // Turn off file logging, otherwise it can't delete the file (Windows).
        $CFG->backup_file_logger_level = backup::LOG_NONE;

        // Do backup with default settings. MODE_IMPORT means it will just
        // create the directory and not zip it.
        $bc = new backup_controller(backup::TYPE_1COURSE, $srccourse->id,
                backup::FORMAT_MOODLE, backup::INTERACTIVE_NO, backup::MODE_IMPORT,
                $USER->id);

        // Don't need userdata.
        $bc->get_plan()->get_setting('users')->set_status(base_setting::NOT_LOCKED);
        $bc->get_plan()->get_setting('users')->set_value(false);

        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        // Do restore to new course with default settings.
        $newcourseid = restore_dbops::create_new_course(
                $srccourse->fullname, $srccourse->shortname . '_2', $srccourse->category
        );
        $rc = new restore_controller($backupid, $newcourseid,
                backup::INTERACTIVE_NO, backup::MODE_GENERAL, $USER->id,
                backup::TARGET_NEW_COURSE);

        // Don't need userdata.
        $rc->get_plan()->get_setting('users')->set_status(base_setting::NOT_LOCKED);
        $rc->get_plan()->get_setting('users')->set_value(false);

        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        return $newcourseid;
    }
}
