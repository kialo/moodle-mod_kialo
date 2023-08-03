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
 * The main mod_kialo configuration form.
 *
 * @package     mod_kialo
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @see https://docs.moodle.org/dev/Form_API
 * @var stdClass $CFG see ../moodle/config-dist.php for available fields
 */

use mod_kialo\deep_link_form;
use mod_kialo\lti_flow;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

require_once('vendor/autoload.php');

/**
 * Module instance settings form.
 *
 * @package     mod_kialo
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_kialo_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;
        global $USER;
        global $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Discussion URL
        $mform->addElement("url", "discussion_url", get_string("discussion_url", "mod_kialo"), array("size" => "64"));
        $mform->setType("discussion_url", PARAM_RAW);
        $mform->addRule('discussion_url', null, 'required', null, 'client');
        $mform->addRule('discussion_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Deep Linking Button
        $mform->addElement("button", "kialo_select_discussion", get_string("select_discussion", "mod_kialo"));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('kialoname', 'mod_kialo'), array('size' => '64'));

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'kialoname', 'mod_kialo');

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    public function display() {
        parent::display();

        // it's important that this form is printed after the actual form, since forms can't be nested
        $this->display_lti_form();
    }

    public function display_lti_form() {
        global $COURSE;
        global $USER;

        // Generates a default HTML form for submitting LTI deep link request,
        // and a script function `submit_deeplink` which can be called by the button above.
        $deeplinkmsg = lti_flow::init_deep_link(
                $COURSE->id, $USER->id, "get discussion URL from input field above"
        );
        $ltiform = new deep_link_form($deeplinkmsg);
        echo $ltiform->create_form("id_kialo_select_discussion");
    }
}
