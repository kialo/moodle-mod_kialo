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
 * Via helper tests.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the view helpers.
 */
final class kialo_view_test extends \advanced_testcase {

    protected function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);
    }

    /**
     * Creates a new course with group mode enabled or not, along with a Kialo activity in the course.
     * @param bool $coursegroupmode whether the course should have group mode enabled
     * @param bool $modulegroupmode whether the activity should have group mode enabled
     * @return \stdClass
     * @throws \coding_exception
     */
    private function create_course_and_activity(
        bool $coursegroupmode = true,
        bool $modulegroupmode = true
    ): \stdClass {
        $result = new \stdClass();
        $result->course = $this->getDataGenerator()->create_course((object) [
            // Our app doesn't distinguish between SEPARATEGROUPS and VISIBLEGROUPS. It's both handled the same.
            'groupmode' => $coursegroupmode ? SEPARATEGROUPS : NOGROUPS,
        ]);
        $result->module = $this->getDataGenerator()->create_module('kialo', [
            'course' => $result->course->id,
            'groupmode' => $coursegroupmode || $modulegroupmode ? SEPARATEGROUPS : NOGROUPS,
        ]);
        $result->cm = get_coursemodule_from_instance("kialo", $result->module->id);

        // One default group in the course for convenience.
        $result->group = ($coursegroupmode || $modulegroupmode)
            ? $this->getDataGenerator()->create_group(['courseid' => $result->course->id]) : null;

        return $result;
    }

    /**
     * Tests the group info retrieval when group mode is disabled.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_no_group_mode(): void {
        // When group mode is disabled in the course and activity.
        $subject = $this->create_course_and_activity(false, false);

        // There should be no group info.
        $groupinfo = kialo_view::get_current_group_info($subject->cm, $subject->course);
        $this->assertNull($groupinfo->groupid);
        $this->assertNull($groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is disabled on the activity but enabled on the course (not forced).
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_course_level_group_mode(): void {
        // Group mode is enabled in the course, but not forced on activities.
        $subject = $this->create_course_and_activity(true, false);

        // By default the activity is created with group mode disabled, so there should be no group info.
        $groupinfo = kialo_view::get_current_group_info($subject->cm, $subject->course);
        $this->assertNull($groupinfo->groupid);
        $this->assertNull($groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is enabled, but the user has no group.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_course_level_group_mode_user_without_group(): void {
        $subject = $this->create_course_and_activity(true, true);
        $this->getDataGenerator()->enrol_user($this->user->id, $subject->course->id, "student");
        // User is not part of a group, even though group mode is enabled.

        // The user is not in any group, so there should be no group info.
        $groupinfo = kialo_view::get_current_group_info($subject->cm, $subject->course);
        $this->assertNull($groupinfo->groupid);
        $this->assertNull($groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is enabled and the user is in a group.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_module_level_group_mode(): void {
        // Both the course and module have group mode enabled.
        $test = $this->create_course_and_activity(true, true);
        $this->getDataGenerator()->enrol_user($this->user->id, $test->course->id, "student");
        $this->getDataGenerator()->create_group_member(['groupid' => $test->group->id, 'userid' => $this->user->id]);

        // So we should get the group infos.
        $groupinfo = kialo_view::get_current_group_info($test->cm, $test->course);
        $this->assertEquals($test->group->id, $groupinfo->groupid);
        $this->assertEquals($test->group->name, $groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is enabled but the user is a teacher.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_for_teachers(): void {
        $test = $this->create_course_and_activity(true, false);
        $this->getDataGenerator()->enrol_user($this->user->id, $test->course->id, "editingteacher");
        $this->getDataGenerator()->create_group_member(['groupid' => $test->group->id, 'userid' => $this->user->id]);

        // Admins have access to all groups, so there should be no specific group info.
        $groupinfo = kialo_view::get_current_group_info($test->cm, $test->course);
        $this->assertNull($groupinfo->groupid);
        $this->assertNull($groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is enabled but has multiple groups. Only the information
     * for one (the active) group can be sent.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_multiple_groups(): void {
        $test = $this->create_course_and_activity(true, true);
        $this->getDataGenerator()->enrol_user($this->user->id, $test->course->id, "student");

        // Create another group and add the user to it.
        $group2 = $this->getDataGenerator()->create_group(['courseid' => $test->course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group2->id, 'userid' => $this->user->id]);

        // The user is in multiple groups, but only one is allowed for the activity. In this case the most recent group
        // is active. Our plugin doesn't particularly care which group is active.
        $groupinfo = kialo_view::get_current_group_info($test->cm, $test->course);
        $this->assertEquals($group2->id, $groupinfo->groupid);
        $this->assertEquals($group2->name, $groupinfo->groupname);
    }

    /**
     * Tests the group info retrieval when group mode is enabled and there is a grouping.
     * Currently, our plugin ignores groupings for group assignments.
     *
     * @return void
     * @covers \mod_kialo\kialo_view::get_current_group_info
     */
    public function test_group_info_grouping(): void {
        $test = $this->create_course_and_activity(true, true);
        $this->getDataGenerator()->enrol_user($this->user->id, $test->course->id, "student");
        $this->getDataGenerator()->create_group_member(['groupid' => $test->group->id, 'userid' => $this->user->id]);

        // Create a grouping and add the course to it.
        $grouping = $this->getDataGenerator()->create_grouping(['courseid' => $test->course->id]);
        $this->getDataGenerator()->create_grouping_group(['groupingid' => $grouping->id, 'groupid' => $test->group->id]);

        // The user is in a group that is part of a grouping, but the grouping is not relevant for the activity right now.
        // That means, we don't take into account grouping information for group assignments in Kialo.
        $groupinfo = kialo_view::get_current_group_info($test->cm, $test->course);
        $this->assertEquals($test->group->id, $groupinfo->groupid);
        $this->assertEquals($test->group->name, $groupinfo->groupname);
    }
}
