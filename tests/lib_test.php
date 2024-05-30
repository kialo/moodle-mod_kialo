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

        // Grades and groups are not supported yet, but will be in the future.
        $this->assertNull(kialo_supports(FEATURE_GROUPS));
        $this->assertNull(kialo_supports(FEATURE_GROUPINGS));
        $this->assertNull(kialo_supports(FEATURE_GRADE_HAS_GRADE));
        $this->assertNull(kialo_supports(FEATURE_ADVANCED_GRADING));

        // Moodle 4.0 and newer.
        if (defined("FEATURE_MOD_PURPOSE")) {
            $this->assertEquals(MOD_PURPOSE_COLLABORATION, kialo_supports(FEATURE_MOD_PURPOSE));
        }
    }

    /**
     * Check add instance
     *
     * @covers ::kialo_add_instance
     */
    public function test_kialo_add_instance(): void {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $id = kialo_add_instance((object) ["name" => "Test", "course" => $course->id]);

        $this->assertNotNull($id);
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
}
