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
 * @noinspection PhpIllegalPsrClassPathInspection classname must not match filename in this case due to moodle conventions
 */

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

    private function get_deployment_id(): string {
        global $COURSE;
        global $USER;

        // Since the deployment id corresponds to an activity id, but the activity hasn't been created yet,
        // when the deep linking happens, we need to use a different deployment id.
        $deploymentid = uniqid($COURSE->id . $USER->id, true);
        $_SESSION["kialo_deployment_id"] = $deploymentid;

        return $deploymentid;
    }

    /**
     * Defines forms elements
     */
    public function definition() {
        // See https://github.com/moodle/moodle/blob/master/course/edit_form.php for an example.
        global $CFG;
        global $COURSE;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

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

        // Discussion URL.
        $mform->addElement("text", "discussion_url", get_string("discussion_url", "mod_kialo"), array("size" => "64"));
        $mform->setType("discussion_url", PARAM_RAW);
        $mform->addRule('discussion_url', null, 'required', null, 'client');
        $mform->addRule('discussion_url', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        // TODO PM-42266: make discussion_Url readonly or hide the field alltogether.

        // Discussion Title.
        $mform->addElement("text", "discussion_title", get_string("discussion_title", "mod_kialo"),
                array("size" => "64", "readonly" => true));
        $mform->setType("discussion_title", PARAM_TEXT);

        // Hidden copy of discussion URL filled by deeplinking. Form can only be submitted if this matches the field above,
        // which means the user successfully linked the discussion via the deeplinking button.
        $mform->addElement("hidden", "discussion_url_hidden", "");
        $mform->setType("discussion_url_hidden", PARAM_RAW);

        // TODO PM-42262: When the deeplink was finished, display the title of the selected discussion here.

        // Deployment ID, filled when selecting the discussion.
        $deploymentid = $this->get_deployment_id();
        $mform->addElement("hidden", "deployment_id", $deploymentid);
        $mform->setType("deployment_id", PARAM_RAW);

        // TODO: Show an error when the hidden fields weren't filled (because deeplinking didn't happen yet).

        // Deep Linking Button, allowing the user to select a discussion on Kialo.
        $deeplinkurl = (new moodle_url('/mod/kialo/lti_select.php', [
            "deploymentid" => $deploymentid,
            "courseid" => $COURSE->id,
        ]))->out(false);
        $mform->addElement("button", "kialo_select_discussion", get_string("select_discussion", "mod_kialo"));
        $mform->addElement("html", "
        <script>
        var selectWindow = null;
        function start_deeplink() {
            // pdu = preselected discussion url
            var starturl = \"{$deeplinkurl}&pdu=\" + encodeURIComponent(document.getElementById(\"id_discussion_url\").value);
            selectWindow = window.open(starturl, \"_blank\");
        }
        window.addEventListener(
          \"message\",
          (event) => {
              if (event.data.type !== \"selected\") return;

              // fill in the deep-linked details
              document.querySelector(\"input[name=discussion_url_hidden]\").value = event.data.discussion_url;
              document.querySelector(\"input[name=deployment_id]\").value = event.data.deployment_id;
              document.querySelector(\"input[name=discussion_title]\").value = event.data.discussion_title;

              // trigger closing of the selection tab
              selectWindow.postMessage({ type: \"acknowledged\" }, \"*\");
          },
          false,
        );
        document.getElementById(\"id_kialo_select_discussion\").addEventListener(\"click\", start_deeplink);
        </script>
        ");
        $mform->addHelpButton("kialo_select_discussion", "select_discussion", "mod_kialo");

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
