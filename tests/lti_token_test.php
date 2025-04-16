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

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Tests the LTI flow.
 */
final class lti_token_test extends \advanced_testcase {

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
