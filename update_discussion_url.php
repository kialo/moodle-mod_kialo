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

// phpcs:disable moodle.Files.RequireLogin.Missing

// TODO review imports
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/constants.php');
require_once(__DIR__ . '/vendor/autoload.php');

use mod_kialo\lti_flow;

lti_flow::authenticate_service_request([MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_SCOPE]);

// TODO Review which are actually required for us
$courseid = required_param('course_id', PARAM_INT);
$cmid = required_param('cmid', PARAM_INT);
$resourcelinkid = required_param('resource_link_id', PARAM_TEXT);

$requestbody = json_decode(file_get_contents('php://input'), true);

error_log("######################## hello from update_discussion_url.php");
error_log("######################## courseid=$courseid");
error_log("######################## cmid=$cmid");
error_log("######################## resourcelinkid=$resourcelinkid");
error_log("######################## requestbody:");
error_log(print_r($requestbody, true));

http_response_code(200);

// TODO do the actual update
