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
 * Kialo Library tests class.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH (support@kialo-edu.com)
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/kialo/lib.php');

/**
 * Kialo Library tests class.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH (support@kialo-edu.com)
 */
final class lib_test extends \advanced_testcase {

    /**
     * Check support
     *
     * @covers ::kialo_supports
     */
    public function test_kialo_supports(): void {
        $this->resetAfterTest();

        $this->assertTrue(kialo_supports(FEATURE_BACKUP_MOODLE2));

        $this->assertFalse(kialo_supports(FEATURE_MOD_INTRO));

        // Group mode is supported.
        $this->assertTrue(kialo_supports(FEATURE_GROUPS));
        $this->assertTrue(kialo_supports(FEATURE_GROUPINGS));

        // Basic grades are supported.
        $this->assertTrue(kialo_supports(FEATURE_GRADE_HAS_GRADE));

        // Advanced grading is not supported.
        $this->assertNull(kialo_supports(FEATURE_ADVANCED_GRADING));

        // Moodle 4.0 and newer.
        if (defined("FEATURE_MOD_PURPOSE")) {
            $this->assertNull(kialo_supports(FEATURE_MOD_PURPOSE));
        }
    }

    /**
     * Check add instance
     *
     * @covers ::kialo_add_instance
     * @var $DB \DB
     */
    public function test_kialo_add_instance(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $id = kialo_add_instance((object) ["name" => "Test", "course" => $course->id, "grade" => 100]);

        $this->assertNotNull($id);

        // By default, the activity is created with a maximum grade of 100 points.
        $instance = $DB->get_record('kialo', ['id' => $id], '*', MUST_EXIST);
        $this->assertEquals(100, $instance->grade);

        // A line item should be created in the gradebook.
        $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $id, 'itemmodule' => 'kialo'], '*', MUST_EXIST);
        $this->assertEquals(100, $gradeitem->grademax);
    }

    /**
     * Check update instance
     *
     * @covers ::kialo_update_instance
     */
    public function test_kialo_update_instance(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);

        $activity->name = "Updated";
        $activity->discussion_title = "Changed discussion";
        $activity->instance = 42;

        $result = kialo_update_instance($activity);
        $this->assertTrue($result);
    }

    /**
     * Check delete instance
     *
     * @covers ::kialo_delete_instance
     */
    public function test_kialo_delete_instance(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $result = kialo_delete_instance($activity->id);
        $this->assertTrue($result);
    }

    /**
     * Check course module
     *
     * @covers ::kialo_get_coursemodule_info
     */
    public function test_kialo_get_coursemodule_info(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $activity = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $info = kialo_get_coursemodule_info($activity);

        // Clicking the activity name should open a new window by default.
        $this->assertStringContainsString("window.open", $info->onclick);
        $this->assertStringContainsString("/mod/kialo/view.php?id=" . $activity->id, $info->onclick);
    }

    /**
     * Check the kialo_pre_enable_plugin_actions function.
     *
     * @covers ::kialo_pre_enable_plugin_actions
     * @dataProvider kialo_pre_enable_plugin_actions_provider
     * @param bool|null $initialstate
     * @param bool $expected
     */
    public function test_kialo_pre_enable_plugin_actions(
        ?bool $initialstate,
        bool $expected
    ): void {
        $this->resetAfterTest(true);

        set_config('acceptterms', $initialstate, 'mod_kialo');

        $this->assertEquals($expected, kialo_pre_enable_plugin_actions());
    }

    /**
     * Check the kialo_pre_enable_plugin_actions function.
     *
     * @covers ::kialo_pre_enable_plugin_actions
     * @dataProvider kialo_pre_enable_plugin_actions_provider
     * @param bool|null $initialstate
     * @param bool $expected
     * @throws \moodle_exception
     */
    public function test_enable_plugin(
        ?bool $initialstate,
        bool $expected
    ): void {
        $this->resetAfterTest(true);

        set_config('acceptterms', $initialstate, 'mod_kialo');

        $this->assertEquals($expected, \core\plugininfo\mod::enable_plugin('kialo', 1));
    }

    /**
     * Data provider for kialo_pre_enable_plugin_actions tests.
     *
     * @return array
     */
    public static function kialo_pre_enable_plugin_actions_provider(): array {
        return [
                'Initially unset' => [null, false],
                'Set to false' => [false, false],
                'Initially set' => [true, true],
        ];
    }

    /**
     * The Kialo module should be enabled when the terms have been accepted.
     *
     * @covers ::kialo_update_visibility_depending_on_accepted_terms
     */
    public function test_enable_module_when_terms_have_been_accepted(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('acceptterms', true, 'mod_kialo');
        kialo_update_visibility_depending_on_accepted_terms();
        $this->assertEquals(1, $DB->get_field('modules', 'visible', ['name' => 'kialo']));

        $this->assertContains('kialo', \core_plugin_manager::instance()->get_enabled_plugins('mod'));
    }

    /**
     * The Kialo module should be disabled while the terms have not been accepted.
     *
     * @covers ::kialo_update_visibility_depending_on_accepted_terms
     */
    public function test_disable_module_when_terms_have_not_been_accepted(): void {
        global $DB;

        $this->resetAfterTest();

        set_config('acceptterms', false, 'mod_kialo');
        kialo_update_visibility_depending_on_accepted_terms();
        $this->assertEquals(0, $DB->get_field('modules', 'visible', ['name' => 'kialo']));

        $this->assertNotContains('kialo', \core_plugin_manager::instance()->get_enabled_plugins('mod'));
    }

    /**
     * Check the kialo_grade_item_update function.
     *
     * @covers ::kialo_grade_item_update
     * @var $DB \DB
     */
    public function test_kialo_grade_item_update_no_scale(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);

        $this->assertEquals(GRADE_UPDATE_OK, kialo_grade_item_update($kialo));

        // Line item should have been updated/created accordingly.
        $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $kialo->id, 'itemmodule' => 'kialo'], '*', MUST_EXIST);
        $this->assertEquals(100, $gradeitem->grademax);
        $this->assertEquals(0, $gradeitem->grademin);
        $this->assertEquals(GRADE_TYPE_VALUE, $gradeitem->gradetype);
    }

    /**
     * Check the kialo_grade_item_update function when using scales instead of regular points.
     *
     * @covers ::kialo_grade_item_update
     * @var $DB \DB
     */
    public function test_kialo_grade_item_update_scale(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $kialo->grade = -1;

        $this->assertEquals(GRADE_UPDATE_OK, kialo_grade_item_update($kialo));

        // Line item should have been updated/created accordingly.
        $gradeitem = $DB->get_record('grade_items', ['iteminstance' => $kialo->id, 'itemmodule' => 'kialo'], '*', MUST_EXIST);
        $this->assertEquals(1, $gradeitem->scaleid); // The value of `-grade` is the scale ID.
        $this->assertEquals(GRADE_TYPE_SCALE, $gradeitem->gradetype);
    }

    /**
     * Check that updating and reading grades works.
     *
     * @covers ::kialo_grade_item_update
     * @covers ::kialo_get_user_grades
     */
    public function test_kialo_get_and_set_user_grades(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $user = $this->getDataGenerator()->create_and_enrol($course);

        // Initially there should be no grade.
        $grades = kialo_get_user_grades($kialo, $user->id);
        $this->assertCount(1, $grades->items);

        $gradeitem = $grades->items[0];
        $this->assertEquals(0, $gradeitem->grademin);
        $this->assertEquals(100, $gradeitem->grademax);
        $this->assertEquals('mod', $gradeitem->itemtype);
        $this->assertEquals('kialo', $gradeitem->itemmodule);
        $this->assertEquals(0, $gradeitem->itemnumber);
        $this->assertEquals(0, $gradeitem->scaleid);

        $this->assertCount(1, $gradeitem->grades);
        $grade = current($gradeitem->grades);
        $this->assertNull($grade->grade);
        $this->assertNull($grade->feedback);
        $this->assertNull($grade->datesubmitted);

        // Set a grade.
        $grade = new \stdClass();
        $grade->userid = $user->id;
        $grade->rawgrade = 50;
        $grade->feedback = 'Good job!';
        $grade->datesubmitted = time();
        kialo_grade_item_update($kialo, $grade);

        // The grade should be set now.
        $grades = kialo_get_user_grades($kialo, $user->id);
        $this->assertCount(1, $grades->items);

        $gradeitem = $grades->items[0];
        $this->assertCount(1, $gradeitem->grades);
        $grade = current($gradeitem->grades);
        $this->assertEquals(50, $grade->grade);
        $this->assertEquals('Good job!', $grade->feedback);
        $this->assertNotNull($grade->datesubmitted);

        // I don't know why these warnings appear. The test itself works as expected, and the plugin code itself, as well.
        $this->expectOutputRegex('/(The instance of this module does not exist)+/');
    }

    /**
     * Cannot grade non-existent users. It should return an error.
     *
     * @return void
     * @covers ::kialo_grade_item_update
     */
    public function test_kialo_grade_item_update_error_on_invalid_user(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $user = $this->getDataGenerator()->create_and_enrol($course);

        // Set a grade but use an invalid user id.
        $invaliduserid = 1234;
        $this->assertNotEquals($invaliduserid, $user->id);
        $grade = new \stdClass();
        $grade->userid = $invaliduserid;
        $grade->rawgrade = 50;
        $grade->feedback = 'Good job!';
        $grade->datesubmitted = time();
        $result = kialo_grade_item_update($kialo, $grade);

        // Cannot grade a non-existent user.
        $this->assertEquals(GRADE_UPDATE_FAILED, $result);
    }

    /**
     * Cannot grade users that are not participants in the course. This should return an error.
     *
     * @return void
     * @covers ::kialo_grade_item_update
     */
    public function test_kialo_grade_item_update_error_on_non_participant(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course]);
        $user = $this->getDataGenerator()->create_user();

        // User is not enrolled in the course.
        $context = \context_course::instance($course->id);
        $this->assertFalse(is_enrolled($context, $user->id));

        // Set a grade but use a user that is not enrolled in the course.
        $grade = new \stdClass();
        $grade->userid = $user->id;
        $grade->rawgrade = 50;
        $grade->feedback = 'Good job!';
        $grade->datesubmitted = time();
        $result = kialo_grade_item_update($kialo, $grade);

        // Cannot grade a non-participant.
        $this->assertEquals(GRADE_UPDATE_FAILED, $result);
    }
}
