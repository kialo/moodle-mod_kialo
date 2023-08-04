<?php

/**
 * Starts the LTI deeplinking flow to select a Kialo discussion when called via GET.
 * It also handles the LtiDeepLinkingResponse from the Kialo backend.
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

use GuzzleHttp\Psr7\ServerRequest;
use mod_kialo\deep_linking_result;
use mod_kialo\kialo_config;
use mod_kialo\lti_flow;
use OAT\Library\Lti1p3Core\Exception\LtiException;

$courseid = optional_param('courseid', 0, PARAM_INT);
$idtoken = optional_param("id_token", "", PARAM_TEXT);

require_login($courseid, false);

// Kialo's backend doesn't implement the DeepLinkingResponse yet, so we need to fake it for now.
// TODO PM-42186: Unfake this once the backend implements the deeplinking flow
$fake = true;

if ($courseid && !$fake) {
    // TODO PM-42182: Remove these lines
    $preselecteddiscussionurl = required_param('pdu', PARAM_URL);
    kialo_config::get_instance()->override_tool_url_for_target($preselecteddiscussionurl);

    // Since the deployment id corresponds to an activity id, but the activity hasn't been created yet,
    // when the deep linking happens, we need to use a different deployment id.
    $deploymentid = uniqid($courseid . $USER->id, true);

    $deeplinkmsg = lti_flow::init_deep_link(
            $courseid,
            $USER->id,
            $deploymentid,
            $preselecteddiscussionurl,
    );

    echo sprintf("<html><head><title>%s</title></head><body>", get_string("deeplinking_redirect", "mod_kialo"));
    // TODO: Should we show a loading screen here? right now it will just be a blank page
    echo $deeplinkmsg->toHtmlRedirectForm();
    echo "</body></html>";
} else if ($idtoken || $fake) {
    try {
        if (!$fake) {
            $link = lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals());
        } else {
            $link = new deep_linking_result(
                    md5(time()),
                    "http://localhost:5000/p/037d99d4-8df5-41ce-90b4-ac1b62120859/5804",
            );
        }
    } catch (LtiException $e) {
        // TODO PM-42186 error handling
    }

    // Kialo POSTed back to this URL, finishing the deep linking flow
    echo "<script>
    window.addEventListener(
        'message',
        (event) => {
            if (event.data.type === 'acknowledged') {
                window.close();
            }
        },
        false );
        window.opener.postMessage({type: \"selected\", deployment_id: \"{$link->deploymentid}\", discussion_url: \"{$link->discussionurl}\"}, \"*\");
    </script>";

    // the user should basically not see this, or just very briefly
    echo '<br><br><br><br><center>You can close this window now.</center>';
} else {
    // should not happen. display moodle error page
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("deeplinking_error_generic_title", "mod_kialo"));
    echo $OUTPUT->error_text(get_string("deeplinking_error_generic_description", "mod_kialo"));
    echo $OUTPUT->footer();
}
