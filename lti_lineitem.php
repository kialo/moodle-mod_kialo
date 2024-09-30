<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Handles `GET /lti_lineitem.php` and `POST /lti_linteitem.php/scores` requests for
 * LTI 1.3 Assignment and Grading Service line items.
 * See LTI 1.3 Assignment and Grading Service specification: https://www.imsglobal.org/spec/lti-ags/v2p0.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var moodle_database $DB
 * @var stdClass $CFG see moodle's config.php
 */

// phpcs:disable moodle.Files.RequireLogin.Missing

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/constants.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once($CFG->libdir . '/gradelib.php');

use mod_kialo\grading\grading_service;
use mod_kialo\lti_flow;

// This request can only be performed with a valid access token obtained from the token endpoint.
lti_flow::authenticate_service_request(MOD_KIALO_LTI_AGS_SCOPES);

$courseid = required_param('course_id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$resourcelinkid = required_param('resource_link_id', PARAM_TEXT);

if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/scores') {
    // Receive a score for the line item via JSON request body.
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);

    if (grading_service::update_grade($courseid, $cmid, $data)) {
        http_response_code(204);
    } else {
        http_response_code(400);
    }
} else {
    // Return the line item information.
    $lineitem = grading_service::get_line_item($courseid, $cmid, $resourcelinkid);

    header('Content-Type: application/json; utf-8');
    echo json_encode($lineitem, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
