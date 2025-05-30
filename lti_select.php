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
 * Starts the LTI deeplinking flow to select a Kialo discussion when called via GET.
 *
 * It also handles the LtiDeepLinkingResponse from the Kialo backend.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
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

$courseid = optional_param('courseid', 0, PARAM_INT);
$idtoken = optional_param("JWT", "", PARAM_TEXT);

if ($courseid) {
    // Called by our activity creation form in Moodle to start the deeplinking flow.
    $context = context_course::instance($courseid);
    $PAGE->set_context($context);
    require_login($courseid, false);
    require_capability('mod/kialo:addinstance', $context);

    $PAGE->set_url('/mod/kialo/lti_select.php', ['courseid' => $courseid]);
    $PAGE->set_title(get_string("redirect_title", "mod_kialo"));

    // This will throw an exception and result in a generic error page, if the deep linking response is invalid.
    try {
        $deeplinkmsg = lti_flow::init_deep_link(
            $courseid,
            $USER->id,
        );
    } catch (Throwable $e) {
        // Show Moodle's default error page including some debug info.
        throw new \moodle_exception('errors:deeplinking', 'kialo', '', null, $e->getMessage());
    }

    $output = $PAGE->get_renderer('mod_kialo');
    echo $output->render(new loading_page(
        get_string("redirect_title", "mod_kialo"),
        get_string("redirect_loading", "mod_kialo"),
        $deeplinkmsg->toHtmlRedirectForm()
    ));
} else if ($idtoken) {
    // Received LtiDeepLinkingResponse from Kialo.
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/mod/kialo/lti_select.php', ['idtoken' => $idtoken]);
    $PAGE->set_title(get_string('close_prompt', 'mod_kialo'));

    try {
        $link = lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals());
    } catch (Throwable $e) {
        // Show Moodle's default error page including some debug info.
        throw new \moodle_exception('errors:deeplinking', 'kialo', '', null, $e->getMessage());
    }

    // Inform the activity form about the successful selection. When acknowledged by the form, close the window.
    $message = json_encode([
            "type" => "kialo_discussion_selected",
            "discussionurl" => $link->discussionurl,
            "discussiontitle" => $link->discussiontitle,
    ]);
    echo "<script>
        window.parent.postMessage({$message}, '*');
    </script>";

    // The user should basically not see this, or just very briefly.
    echo "<br><br><br><br><center>" . get_string('close_prompt', 'mod_kialo') . "</center>";
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_url('/mod/kialo/lti_select.php', ['idtoken' => $idtoken, 'courseid' => $courseid]);

    $error = "errors:invalidrequest";
    if (empty($courseid)) {
        $error = "errors:missingcourseid";
    } else if (empty($idtoken)) {
        $error = "errors:missingidtoken";
    }
    $PAGE->set_title(get_string($error, 'mod_kialo'));

    // Should not happen (but could if someone intentionally calls this page with wrong params). Display moodle error page.
    throw new \moodle_exception('errors:deeplinking', 'kialo', "", null, get_string($error, 'mod_kialo'));
}
