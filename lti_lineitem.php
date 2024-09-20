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
 * Handles GET and POST (/scores) requests for LTI 1.3 Assignment and Grading Service line items.
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

use mod_kialo\grading\line_item;
use mod_kialo\lti_flow;
use OAT\Library\Lti1p3Core\Exception\LtiException;

lti_flow::authenticate_service_request(MOD_KIALO_LTI_AGS_SCOPES);

$courseid = required_param('course_id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$resourcelinkid = required_param('resource_link_id', PARAM_TEXT);
$module = get_coursemodule_from_id('kialo', $cmid, $courseid, false, MUST_EXIST);
$moduleinstance = $DB->get_record('kialo', ['id' => $module->instance], '*', MUST_EXIST);

$gradeitem = grade_item::fetch(['iteminstance' => $module->instance, 'itemtype' => 'mod']);
if (!$gradeitem) {
    throw new LtiException("Grade item for module CMID=$cmid (instance={$module->instance}) not found");
}

if (isset($_SERVER['PATH_INFO']) && $_SERVER['PATH_INFO'] == '/scores') {
    // Receive a score for the line item via JSON request body.
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    $userid = $data['userId'];
    $scoregiven = isset($data['scoreGiven']) ? max(0, min(floatval($data['scoreGiven']), $gradeitem->grademax)) : null;
    $comment = $data['comment'];
    $timestamp = isset($data['timestamp']) ? strtotime($data['timestamp']) : time();

    $grades = [
        'userid' => $userid,
        'feedback' => $comment,
        'dategraded' => $timestamp,
    ];
    if ($scoregiven !== null) {
        $grades['rawgrade'] = $scoregiven;
    } else {
        $grades['rawgrade'] = null;
    }

    $result = kialo_grade_item_update($moduleinstance, $grades);
    if ($result === GRADE_UPDATE_OK || $result === GRADE_UPDATE_MULTIPLE) {
        http_response_code(204);
    } else {
        http_response_code(400);
    }
} else {
    // Return the line item information.
    $lineitem = new line_item();
    $lineitem->id = (new moodle_url($_SERVER['REQUEST_URI']))->out(false);
    $lineitem->label = $module->name;
    $lineitem->scoreMaximum = floatval($gradeitem->grademax);
    $lineitem->resourceLinkId = $resourcelinkid;

    header('Content-Type: application/json; utf-8');
    echo json_encode($lineitem, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
}
