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

        // Discussion Title.
        $mform->addElement("text", "discussion_title", get_string("discussion_title", "mod_kialo"),
                array("size" => "64", "readonly" => true));
        $mform->setType("discussion_title", PARAM_TEXT);
        $mform->addRule('discussion_title', null, 'required', null, 'client');

        // Hidden copy of discussion URL filled by deeplinking. Form can only be submitted if this matches the field above,
        // which means the user successfully linked the discussion via the deeplinking button.
        $mform->addElement("hidden", "discussion_url", "");
        $mform->setType("discussion_url", PARAM_RAW);

        // Deployment ID, filled when selecting the discussion.
        $deploymentid = $this->get_deployment_id();
        $mform->addElement("hidden", "deployment_id", $deploymentid);
        $mform->setType("deployment_id", PARAM_RAW);

        // Deep Linking Button, allowing the user to select a discussion on Kialo.
        $deeplinkurl = (new moodle_url('/mod/kialo/lti_select.php', [
                "deploymentid" => $deploymentid,
                "courseid" => $COURSE->id,
        ]))->out(false);
        $mform->addElement("button", "kialo_select_discussion", get_string("select_discussion", "mod_kialo"));

        // Scripts that handle the deeplinking response from the other tab via postMessage.
        $defaultnameprefix = get_string('defaultactivitynameprefix', 'mod_kialo');
        $mform->addElement("html", "<script>
            var kialoSelectWindow = null;
            document.getElementById('id_kialo_select_discussion').addEventListener('click', () => {
                kialoSelectWindow = window.open('{$deeplinkurl}', '_blank');
            });
            window.addEventListener(
              'message',
              (event) => {
                  if (event.data.type !== 'kialo_discussion_selected') return;

                  // Fill in the deep-linked details.
                  document.querySelector('input[name=discussion_url]').value = event.data.discussionurl;
                  document.querySelector('input[name=deployment_id]').value = event.data.deploymentid;
                  document.querySelector('input[name=discussion_title]').value = event.data.discussiontitle;
                  
                  // Prefill activity name based on discussion title if user hasn't entered one yet.
                  const nameInput = document.querySelector('input[name=name]');
                  if (!nameInput.value) {
                      nameInput.value = ['{$defaultnameprefix}', event.data.discussiontitle].join(' ');
                  }

                  // Trigger closing of the selection tab.
                  kialoSelectWindow.postMessage({ type: 'kialo_selection_acknowledged' }, '*');
              }
            );
            </script>");
        $mform->addHelpButton("kialo_select_discussion", "select_discussion", "mod_kialo");

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }
}
