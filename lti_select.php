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
use mod_kialo\lti_flow;
use mod_kialo\output\loading_page;
use OAT\Library\Lti1p3Core\Exception\LtiException;

$courseid = optional_param('courseid', 0, PARAM_INT);
$idtoken = optional_param("JWT", "", PARAM_TEXT);
$deploymentid = optional_param("deploymentid", "", PARAM_TEXT);

require_login($courseid, false);

if ($courseid) {
    // Called by our activity creation form in Moodle to start the deeplinking flow.

    // TODO PM-42182: Remove these lines.
    $preselecteddiscussionurl = required_param('pdu', PARAM_URL);

    $deeplinkmsg = lti_flow::init_deep_link(
            $courseid,
            $USER->id,
            $deploymentid,
            $preselecteddiscussionurl,
    );

    $output = $PAGE->get_renderer('mod_kialo');
    echo $output->render(new loading_page(
            get_string("lti_select_redirect_title", "mod_kialo"),
            get_string("lti_select_redirect_loading", "mod_kialo"),
            $deeplinkmsg->toHtmlRedirectForm()
    ));
} else if ($idtoken) {
    // Received LtiDeepLinkingResponse from Kialo.
    try {
        $deploymentid = $_SESSION["kialo_deployment_id"];
        $link = lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals(), $deploymentid);
    } catch (LtiException $e) {
        // TODO PM-42186 error handling.
        echo 'LTI ERROR: ' . $e->getMessage();
        echo "<br>";
        echo $e->getTraceAsString();
        die();
    }

    echo "<script>
    window.addEventListener(
        'message',
        (event) => {
            if (event.data.type === 'acknowledged') {
                window.close();
            }
        },
        false );
        window.opener.postMessage({ 
            type: \"selected\", 
            deployment_id: \"{$link->deploymentid}\", 
            discussion_url: \"{$link->discussionurl}\", 
            discussion_title: \"{$link->discussiontitle}\"
        }, \"*\");
    </script>";

    // The user should basically not see this, or just very briefly.
    echo '<br><br><br><br><center>You can close this window now.</center>';
} else {
    // Should not happen. display moodle error page.
    echo $OUTPUT->header();
    echo $OUTPUT->heading(get_string("deeplinking_error_generic_title", "mod_kialo"));
    echo $OUTPUT->error_text(get_string("deeplinking_error_generic_description", "mod_kialo"));
    echo $OUTPUT->footer();
}
