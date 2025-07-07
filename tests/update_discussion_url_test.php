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
 * Update discussion URL tests class.
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
 * Update discussion URL tests class.
 *
 * @package   mod_kialo
 * @copyright 2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Kialo GmbH (support@kialo-edu.com)
 */
final class update_discussion_url_test extends \advanced_testcase {

    /**
     * Test updating discussion URL successfully.
     *
     * @covers ::kialo_update_discussion_url
     */
    public function test_kialo_update_discussion_url_success(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course and kialo activity.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', [
            'course' => $course,
            'discussion_url' => 'https://example.com/original-url',
        ]);

        // Get the course module.
        $cm = get_coursemodule_from_instance('kialo', $kialo->id);

        // Test updating the discussion URL.
        $newurl = 'https://example.com/new-discussion-url';
        kialo_update_discussion_url($cm->id, $newurl);

        // Verify the URL was updated in the database.
        $updatedkialo = $DB->get_record('kialo', ['id' => $kialo->id], '*', MUST_EXIST);
        $this->assertEquals($newurl, $updatedkialo->discussion_url);

        // Verify timemodified was updated.
        $this->assertGreaterThan($kialo->timemodified, $updatedkialo->timemodified);
    }

    /**
     * Test updating discussion URL with invalid course module ID.
     *
     * @covers ::kialo_update_discussion_url
     */
    public function test_kialo_update_discussion_url_invalid_cmid(): void {
        $this->resetAfterTest();

        // Use a non-existent course module ID.
        $invalidcmid = 9999;
        $newurl = 'https://example.com/new-discussion-url';

        // Test updating with invalid cmid should throw exception.
        $this->expectException(\dml_missing_record_exception::class);
        kialo_update_discussion_url($invalidcmid, $newurl);
    }

    /**
     * Test updating discussion URL with empty URL.
     *
     * @covers ::kialo_update_discussion_url
     */
    public function test_kialo_update_discussion_url_empty_url(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course and kialo activity.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', [
            'course' => $course,
            'discussion_url' => 'https://example.com/original-url',
        ]);

        // Get the course module.
        $cm = get_coursemodule_from_instance('kialo', $kialo->id);

        // Test updating with empty URL.
        kialo_update_discussion_url($cm->id, '');

        // Verify the URL was cleared in the database.
        $updatedkialo = $DB->get_record('kialo', ['id' => $kialo->id], '*', MUST_EXIST);
        $this->assertEquals('', $updatedkialo->discussion_url);
    }

    /**
     * Test that the function handles long URLs correctly.
     *
     * @covers ::kialo_update_discussion_url
     */
    public function test_kialo_update_discussion_url_long_url(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course and kialo activity.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', [
            'course' => $course,
        ]);

        // Get the course module.
        $cm = get_coursemodule_from_instance('kialo', $kialo->id);

        // Test with a URL that's at the field limit (255 characters).
        $longurl = 'https://example.com/' . str_repeat('a', 235); // 255 chars total.
        kialo_update_discussion_url($cm->id, $longurl);

        // Verify the URL was updated in the database.
        $updatedkialo = $DB->get_record('kialo', ['id' => $kialo->id], '*', MUST_EXIST);
        $this->assertEquals($longurl, $updatedkialo->discussion_url);
    }

    /**
     * Test that timemodified is updated when discussion URL changes.
     *
     * @covers ::kialo_update_discussion_url
     */
    public function test_kialo_update_discussion_url_timemodified(): void {
        global $DB;
        $this->resetAfterTest();

        // Create a course and kialo activity.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', [
            'course' => $course,
            'discussion_url' => 'https://example.com/original-url',
        ]);

        // Get the course module.
        $cm = get_coursemodule_from_instance('kialo', $kialo->id);

        // Store the original timemodified.
        $originaltimemodified = $kialo->timemodified;

        // Wait a second to ensure different timestamps.
        sleep(1);

        // Test updating the discussion URL.
        $newurl = 'https://example.com/updated-url';
        kialo_update_discussion_url($cm->id, $newurl);

        // Verify timemodified was updated.
        $updatedkialo = $DB->get_record('kialo', ['id' => $kialo->id], '*', MUST_EXIST);
        $this->assertGreaterThan($originaltimemodified, $updatedkialo->timemodified);
    }
}
