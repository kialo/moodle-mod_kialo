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
 * Display information about all the mod_kialo modules in the requested course.
 *
 * @package     mod_kialo
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

require_once(__DIR__ . '/lib.php');

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$coursecontext = context_course::instance($course->id);
require_capability('mod/kialo:view', $coursecontext);

$PAGE->set_url('/mod/kialo/index.php', ['id' => $id]);
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

$modulenameplural = get_string('modulenameplural', 'mod_kialo');
echo $OUTPUT->heading($modulenameplural);

$kialos = get_all_instances_in_course('kialo', $course);

if (empty($kialos)) {
    notice(new lang_string('noinstances', 'error', 'Kialo'), new moodle_url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';

if ($course->format == 'weeks') {
    $table->head = [get_string('week'), get_string('name')];
    $table->align = ['center', 'left'];
} else if ($course->format == 'topics') {
    $table->head = [get_string('topic'), get_string('name')];
    $table->align = ['center', 'left', 'left', 'left'];
} else {
    $table->head = [get_string('name')];
    $table->align = ['left', 'left', 'left'];
}

foreach ($kialos as $kialo) {
    if (!$kialo->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/kialo/view.php', ['id' => $kialo->coursemodule]),
            format_string($kialo->name, true),
            ['class' => 'dimmed']
        );
    } else {
        $link = html_writer::link(
            new moodle_url('/mod/kialo/view.php', ['id' => $kialo->coursemodule]),
            format_string($kialo->name, true)
        );
    }

    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = [$kialo->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();
