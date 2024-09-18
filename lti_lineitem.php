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
 * When an LTI message is launching a resource associated to one and only one lineitem,
 * the claim must include the endpoint URL for accessing the associated line item;
 * in all other cases, this property must be either blank or not included in the claim.
 *
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/constants.php');
require_once(__DIR__ . '/vendor/autoload.php');
require_once($CFG->libdir . '/gradelib.php');

use mod_kialo\grading\line_item;
use mod_kialo\kialo_logger;
use mod_kialo\lti_flow;

$logger = new kialo_logger("lti_lineitem");
$logger->info("LTI lineitem request received.", $_POST ?? $_GET ?? []);

lti_flow::authenticate_service_request(MOD_KIALO_LTI_AGS_SCOPES);

$courseid = required_param('course_id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$resourcelinkid = required_param('resource_link_id', PARAM_TEXT);
$module = get_coursemodule_from_id('kialo', $cmid, $courseid);
if (!$module) {
    die("Module $cmid not found");
}
$gradeitem = grade_item::fetch(['iteminstance' => $module->instance, 'itemtype' => 'mod']);
if (!$gradeitem) {
    die("Grade item for module CMID=$cmid (instance={$module->instance}) not found");
}

$lineitem = new line_item();
$lineitem->id = $_SERVER['REQUEST_URI'];
$lineitem->label = $module->name;
$lineitem->scoremaximum = floatval($gradeitem->grademax);
$lineitem->resourcelinkid = $resourcelinkid;

header('Content-Type: application/json; utf-8');
echo json_encode($lineitem, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
