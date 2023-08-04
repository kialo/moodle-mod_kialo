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
        global $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Discussion URL
        $mform->addElement("url", "discussion_url", get_string("discussion_url", "mod_kialo"), array("size" => "64"));
        $mform->setType("discussion_url", PARAM_RAW);
        $mform->addRule('discussion_url', null, 'required', null, 'client');
        $mform->addRule('discussion_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        // Hidden copy of discussion URL filled by deeplinking. Form can only be submitted if this matches the field above,
        // which means the user successfully linked the discussion via the deeplinking button.
        $mform->addElement("hidden", "discussion_url_hidden", "");
        $mform->addRule('discussion_url_hidden', null, 'required', null, 'client');

        // TODO PM-42186: When the deeplink was finished, display the title of the selected discussion here

        // Deployment ID, filled when selecting the discussion
        $mform->addElement("hidden", "deployment_id", "");
        $mform->addRule('deployment_id', null, 'required', null, 'client');

        // TODO: show an error when the hidden fields weren't filled (because deeplinking didn't happen yet)

        // Deep Linking Button
        $deeplinkurl = (new moodle_url('/mod/kialo/lti_select.php', [
                "courseid" => $COURSE->id,
        ]))->out(true);
        $mform->addElement("button", "kialo_select_discussion", get_string("select_discussion", "mod_kialo"));
        $mform->addElement("html", sprintf('
        <script>
        var selectWindow = null;
        function start_deeplink() {
            var starturl = "%s&pdu=" + encodeURIComponent(document.getElementById("id_discussion_url").value);
            selectWindow = window.open(starturl, "_blank");
        }
        window.addEventListener(
          "message",
          (event) => {
              console.log(event);
              if (event.data.type !== "selected") return;
            
              document.querySelector("input[name=discussion_url_hidden]").value = event.data.discussion_url;
              document.querySelector("input[name=deployment_id]").value = event.data.deployment_id;
            
              // trigger closing of the selection tab
              console.log("SENDING ACK");
              selectWindow.postMessage({ type: "acknowledged" }, "*");
          },
          false,
        );
        document.getElementById("id_kialo_select_discussion").addEventListener("click", start_deeplink);
        </script>
        ', $deeplinkurl));
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
}
