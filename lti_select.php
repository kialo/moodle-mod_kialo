<?php

/**
 * Starts the LTI deeplinking flow to select a Kialo discussion.
 *
 * @package     mod_kialo
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @var moodle_page $PAGE
 * @var core_renderer $OUTPUT
 * @var moodle_database $DB
 * @var stdClass $USER
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once('vendor/autoload.php');

use mod_kialo\kialo_config;
use mod_kialo\lti_flow;

// Course module id.
$courseid = required_param('courseid', PARAM_INT);
require_login($courseid, false);

// TODO PM-42182: Remove these lines
$preselecteddiscussionurl = required_param('pdu', PARAM_URL);
kialo_config::get_instance()->override_tool_url_for_target($preselecteddiscussionurl);

// Since the deployment id corresponds to an activity id, but the activity hasn't been created yet,
// when the deep linking happens, we need to use a different deployment id.
$deploymentid = md5($courseid . $USER->id . time());

$deeplinkmsg = lti_flow::init_deep_link(
        $courseid,
        $USER->id,
        $deploymentid,
        $preselecteddiscussionurl,
);

echo $deeplinkmsg->toHtmlRedirectForm();
