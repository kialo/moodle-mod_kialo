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
 * Starts the LTI flow to launch the Kialo app.
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
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$k = optional_param('k', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('kialo', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('kialo', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('kialo', array('id' => $k), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('kialo', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);

// get moodle user id
$userid = $USER->id;

$kialo_config = kialo_config::get_instance();
$registration = $kialo_config->create_registration($id);

$builder = new PlatformOriginatingLaunchBuilder();
$message = $builder->buildPlatformOriginatingLaunch(
        $registration,
        LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
        'http://localhost:5000/lti/launch3', // target link uri of the launch (final destination after OIDC flow)
        'loginHint42', // login hint that will be used afterwards by the platform to perform authentication
        $id,
        // will use the registration default deployment id, but you can pass a specific one
        [
                'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
        ],
        [
                new ResourceLinkClaim('discussionIdMaybe?', 'discussion title maybe?', 'discussion description maybe?'), // LTI claim representing the resource link of the launch
        ]
);

# TODO PM-41780: If something goes wrong above, show a helpful error
# TODO PM-42133: Improve the loading screen below
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title>Redirecting to Kialo...</title>
</head>
<body>
<?php
echo $message->toHtmlRedirectForm();
?>
</body>
</html>
