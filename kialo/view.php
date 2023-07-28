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
 * Prints an instance of mod_kialo.
 *
 * @package     mod_kialo
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */

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

$modulecontext = context_module::instance($cm->id);

//$event = \mod_kialo\event\course_module_viewed::create(array(
//    'objectid' => $moduleinstance->id,
//    'context' => $modulecontext
//));
//$event->add_record_snapshot('course', $course);
//$event->add_record_snapshot('kialo', $moduleinstance);
//$event->trigger();

//$PAGE->set_url('/mod/kialo/view.php', array('id' => $cm->id));
//$PAGE->set_title(format_string($moduleinstance->name));
//$PAGE->set_heading(format_string($course->fullname));
//$PAGE->set_context($modulecontext);

//echo $OUTPUT->header();

// TODO: How to properly check if this is a student or teacher
if (has_capability('mod/kialo:addinstance', $modulecontext)) {
    redirect($moduleinstance->discussion_url, 'redirecting to Kialo...');
} else {
    echo "STUDENT";
}

//echo $OUTPUT->footer();
