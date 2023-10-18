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

global $CFG;

use mod_kialo\lti_flow;
use mod_kialo\output\loading_page;

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$k = optional_param('k', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('kialo', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('kialo', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('kialo', ['id' => $k], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('kialo', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

$context = context_module::instance($cm->id);

require_login($course, false, $cm);
require_capability('mod/kialo:view', $context);

if (isguestuser() || is_guest($context)) {
    throw new \moodle_exception('errors:noguestaccess', 'kialo');
}

try {
    $message = lti_flow::init_resource_link(
        $course->id,
        $cm->id,
        $moduleinstance->deployment_id,
        $USER->id,
        $moduleinstance->discussion_url
    );

    $output = $PAGE->get_renderer('mod_kialo');
    echo $output->render(new loading_page(
        get_string("redirect_title", "mod_kialo"),
        get_string("redirect_loading", "mod_kialo"),
        $message->toHtmlRedirectForm()
    ));
} catch (Throwable $e) {
    // Show Moodle's default error page including some debug info.
    throw new \moodle_exception('errors:resourcelink', 'kialo', '', null, $e->getMessage());
}
