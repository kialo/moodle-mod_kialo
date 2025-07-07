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
 * Handles `POST /lti_update_discussion_url.php` requests for the LTI 1.3 Kialo plugin
 *
 * @package    mod_kialo
 * @copyright  2025 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/constants.php');
require_once(__DIR__ . '/vendor/autoload.php');

use mod_kialo\lti_flow;

lti_flow::authenticate_service_request([MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_SCOPE]);

$cmid = required_param('cmid', PARAM_INT);

$requestbody = json_decode(file_get_contents('php://input'), true);

// PM-49506 do the actual update when the function is implemented.

http_response_code(200);
