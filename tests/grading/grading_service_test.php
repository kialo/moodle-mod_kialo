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
 * Grading tests.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use mod_kialo\grading\grading_service;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the LTI flow.
 */
final class grading_service_test extends \advanced_testcase {
    /**
     * Copy of $_SERVER superglobal before the test.
     * @var array|null
     */
    private $server;

    /**
     * Copy of $_ENV superglobal before the test.
     * @var array|null
     */
    private $env;

    /**
     * Copy of $_GET superglobal before the test.
     * @var array|null
     */
    private $get;

    protected function setUp(): void {
        parent::setUp();

        $this->backup_globals();
        $this->resetAfterTest();
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Backs up superglobal variables modified by this test.
     *
     * @return void
     */
    private function backup_globals(): void {
        $this->server = $_SERVER;
        $this->env = $_ENV;
        $this->get = $_GET;
    }

    /**
     * Restores superglobal variables modified by this test.
     *
     * @return void
     */
    private function restore_globals(): void {
        if (null !== $this->server) {
            $_SERVER = $this->server;
        }
        if (null !== $this->env) {
            $_ENV = $this->env;
        }
        if (null !== $this->get) {
            $_GET = $this->get;
        }
    }

    /**
     * Tests getting the line item for a course module. This is used by Kialo to get the max. grade configured in the LMS,
     * as well as the endpoint to send grades to.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers \mod_kialo\grading\grading_service::get_line_item
     */
    public function test_get_line_item(): void {
        $maxgrade = 123;
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course->id, 'grade' => $maxgrade]);
        $coursemodule = get_coursemodule_from_instance("kialo", $kialo->id);

        $courseid = $course->id;
        $coursemoduleid = $coursemodule->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $lineitem = grading_service::get_line_item($courseid, $coursemoduleid, $resourcelinkid);
        $this->assertEquals("https://www.example.com/moodle" . $endpoint, $lineitem->id);
        $this->assertEquals($coursemodule->name, $lineitem->label);
        $this->assertEquals($maxgrade, $lineitem->scoreMaximum);
        $this->assertEquals($resourcelinkid, $lineitem->resourceLinkId);
    }

    /**
     * Tests getting the line item for a course module when we have same courseid and iteminstance for moodle's build-in
     * lti client and the plugin.
     * This is used by Kialo to get the max. grade configured in the LMS,
     * as well as the endpoint to send grades to.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers \mod_kialo\grading\grading_service::get_line_item
     */
    public function test_get_line_item_conflict_between_moodle_lti_client_and_plugin(): void {
        $maxgrade = 123;
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course->id, 'grade' => $maxgrade]);
        // Create a grade item for the moodle's build-in lti client with same iteminstance and courseid.
        $this->getDataGenerator()->create_grade_item(['iteminstance' => $kialo->id, 'courseid' => $course->id,
            'itemmodule' => 'lti', 'itemtype' => 'mod']);
        $coursemodule = get_coursemodule_from_instance("kialo", $kialo->id);

        $courseid = $course->id;
        $coursemoduleid = $coursemodule->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $lineitem = grading_service::get_line_item($courseid, $coursemoduleid, $resourcelinkid);
        $this->assertEquals("https://www.example.com/moodle" . $endpoint, $lineitem->id);
        $this->assertEquals($coursemodule->name, $lineitem->label);
        $this->assertEquals($maxgrade, $lineitem->scoreMaximum);
        $this->assertEquals($resourcelinkid, $lineitem->resourceLinkId);
    }

    /**
     * Tests getting the line item for a course module. This is used by Kialo to get the max. grade configured in the LMS,
     * as well as the endpoint to send grades to.
     * Activities created with previous versions have no grade book item.
     * We just return the max grade default value of 100 in this case.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers \mod_kialo\grading\grading_service::get_line_item
     * @var moodle_database $DB
     */
    public function test_get_line_item_with_missing_grade_book_entry(): void {
        global $DB;

        $maxgrade = 100;
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course->id, 'grade' => null]);
        $coursemodule = get_coursemodule_from_instance("kialo", $kialo->id);

        // Delete the grade book item.
        $DB->delete_records("grade_items", ["iteminstance" => $kialo->id]);

        $courseid = $course->id;
        $coursemoduleid = $coursemodule->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $lineitem = grading_service::get_line_item($courseid, $coursemoduleid, $resourcelinkid);
        $this->assertEquals("https://www.example.com/moodle" . $endpoint, $lineitem->id);
        $this->assertEquals($coursemodule->name, $lineitem->label);
        $this->assertEquals($maxgrade, $lineitem->scoreMaximum);
        $this->assertEquals($resourcelinkid, $lineitem->resourceLinkId);
    }

    /**
     * Scores should be written to the gradebook as expected.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers \mod_kialo\grading\grading_service::update_grade
     */
    public function test_write_scores(): void {
        // Given a Kialo activity with a user without grades.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance("kialo", $kialo->id);
        $user = $this->getDataGenerator()->create_and_enrol($course);

        $courseid = $course->id;
        $coursemoduleid = $coursemodule->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        // When a score is posted to the LTI line item endpoint.
        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $score = 72;
        $feedback = "nice try";
        $data = [
            'userId' => $user->id,
            'comment' => $feedback,
            'scoreGiven' => $score,
            'timestamp' => '2023-01-01T00:00:00Z',
        ];

        $result = grading_service::update_grade($courseid, $coursemoduleid, $data);
        $this->assertTrue($result);

        // The gradebook entry should have been created accordingly.
        $grades = kialo_get_user_grades($kialo, $user->id);
        $this->assertCount(1, $grades->items);

        $gradeitem = current($grades->items);
        $this->assertEquals('mod', $gradeitem->itemtype);
        $this->assertEquals('kialo', $gradeitem->itemmodule);
        $this->assertEquals($coursemodule->instance, $gradeitem->iteminstance);
        $this->assertEquals($kialo->name, $gradeitem->name);
        $this->assertEquals($gradeitem->grademax, 100);

        $this->assertCount(1, $gradeitem->grades);
        $grade = current($gradeitem->grades);
        $this->assertEquals($score, $grade->grade);
        $this->assertEquals($feedback, $grade->feedback);
        $this->assertEquals(FORMAT_MOODLE, $grade->feedbackformat);

        // I don't know why these warnings appear. The test itself works as expected, and the plugin code itself, as well.
        $this->expectOutputRegex('/(The instance of this module does not exist)+/');
    }


    /**
     * Activities created with previous versions have no grade book item.
     * It should be created when the score is written.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \dml_exception
     * @covers \mod_kialo\grading\grading_service::update_grade
     * @var moodle_database $DB
     */
    public function test_write_scores_without_grade_book_item(): void {
        global $DB;

        // Given a Kialo activity with a user without grades.
        $course = $this->getDataGenerator()->create_course();
        $kialo = $this->getDataGenerator()->create_module('kialo', ['course' => $course->id]);
        $coursemodule = get_coursemodule_from_instance("kialo", $kialo->id);
        $user = $this->getDataGenerator()->create_and_enrol($course);

        $courseid = $course->id;
        $coursemoduleid = $coursemodule->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        // Delete the grade book item.
        $DB->delete_records("grade_items", ["iteminstance" => $kialo->id]);

        // When a score is posted to the LTI line item endpoint.
        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $score = 72;
        $feedback = "nice try";
        $data = [
            'userId' => $user->id,
            'comment' => $feedback,
            'scoreGiven' => $score,
            'timestamp' => '2023-01-01T00:00:00Z',
        ];

        $result = grading_service::update_grade($courseid, $coursemoduleid, $data);
        $this->assertTrue($result);

        // The gradebook entry should have been created accordingly.
        $grades = kialo_get_user_grades($kialo, $user->id);
        $this->assertCount(1, $grades->items);

        $gradeitem = current($grades->items);
        $this->assertEquals('mod', $gradeitem->itemtype);
        $this->assertEquals('kialo', $gradeitem->itemmodule);
        $this->assertEquals($coursemodule->instance, $gradeitem->iteminstance);
        $this->assertEquals($kialo->name, $gradeitem->name);
        $this->assertEquals($gradeitem->grademax, 100);

        $this->assertCount(1, $gradeitem->grades);
        $grade = current($gradeitem->grades);
        $this->assertEquals($score, $grade->grade);
        $this->assertEquals($feedback, $grade->feedback);
        $this->assertEquals(FORMAT_MOODLE, $grade->feedbackformat);

        // I don't know why these warnings appear. The test itself works as expected, and the plugin code itself, as well.
        $this->expectOutputRegex('/(The instance of this module does not exist)+/');
    }

    /**
     * Ensure get_line_item only fetches grade item from the same course.
     *
     * @return void
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiException
     * @throws \coding_exception
     * @throws \moodle_exception
     * @covers \mod_kialo\grading\grading_service::get_line_item
     */
    public function test_get_line_item_ignores_other_course_grade_item(): void {
        $maxgrade = 77;

        // Course A with Kialo module and grade max 77.
        $coursea = $this->getDataGenerator()->create_course();
        $kialoa = $this->getDataGenerator()->create_module('kialo', ['course' => $coursea->id, 'grade' => $maxgrade]);
        $cma = get_coursemodule_from_instance('kialo', $kialoa->id);

        // Course B with a conflicting grade_item (same iteminstance).
        $courseb = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->create_grade_item([
            'iteminstance' => $kialoa->id,
            'courseid' => $courseb->id,
            'itemmodule' => 'kialo',
            'itemtype' => 'mod',
            'grademax' => 5,
        ]);

        $courseid = $coursea->id;
        $coursemoduleid = $cma->id;
        $resourcelinkid = lti_flow::resource_link_id($coursemoduleid);

        $endpoint = "/mod/kialo/lti_lineitem.php?course_id={$courseid}&cmid={$coursemoduleid}&resource_link_id={$resourcelinkid}";
        $_SERVER['REQUEST_URI'] = $endpoint;

        $lineitem = grading_service::get_line_item($courseid, $coursemoduleid, $resourcelinkid);
        $this->assertEquals("https://www.example.com/moodle" . $endpoint, $lineitem->id);
        $this->assertEquals($cma->name, $lineitem->label);
        // Should respect Course A's grade item, not the conflicting one in Course B.
        $this->assertEquals($maxgrade, $lineitem->scoreMaximum);
        $this->assertEquals($resourcelinkid, $lineitem->resourceLinkId);
    }
}
