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
 * LTI access token endpoint tests.
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
 * Tests the LTI flow.
 */
final class lti_token_test extends \advanced_testcase {
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

    protected function setUp(): void {
        parent::setUp();

        $this->backup_globals();
        $this->resetAfterTest();

        $this->user = $this->getDataGenerator()->create_user(["picture" => 42]);
        $this->setUser($this->user);

        $this->course = $this->getDataGenerator()->create_course();

        // Creates a Kialo activity.
        $this->module = $this->getDataGenerator()->create_module('kialo', ['course' => $this->course->id]);
        $this->cm = get_coursemodule_from_instance("kialo", $this->module->id);
        $this->cmid = $this->cm->id;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Tests the expected result when just calling this endpoint with a GET request without necessary parameters.
     * @return void
     * @covers \mod_kialo\lti_flow::generate_service_access_token
     */
    public function test_access_token_request_invalid_get(): void {
        $response = lti_flow::generate_service_access_token();
        $this->assertEquals(400, $response->getStatusCode());
        $this->assertStringContainsString("unsupported_grant_type", $response->getBody());
    }

    // Not testing the successful cases for the service access request and validation here due to effort and complexity.
    // It's already covered by the library's tests and end-to-end tests.
    // However, if there is time, eventually tests for these shoul be added:
    // - generate_service_access_token
    // - authenticate_service_request
    //
    // The tests should ensure that access tokens can be requested and used successfully.
}
