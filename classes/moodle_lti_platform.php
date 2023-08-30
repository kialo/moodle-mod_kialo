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
 * Wrapper around Moodle's LTI platform to provide a consistent interface.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- This is a library file, not a page.

namespace mod_kialo;

use context_module;
use core_date;

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/lti/lib.php');
require_once($CFG->dirroot . '/mod/lti/locallib.php');

/**
 * Wrapper around Moodle's LTI platform to provide a consistent interface.
 *
 * typeid = deploymentid
 */
class moodle_lti_platform {
    public function moodle_lti_config(?string $deploymentid, int $courseid, ?int $cmid = null, ?int $kialoid = null) {
        $kialoconfig = kialo_config::get_instance();
        $toolurl = $kialoconfig->get_tool_url();

        return (object) [
                "id" => $kialoid,
                "lti_toolurl" => $toolurl,
                "lti_initiatelogin" => $toolurl . '/lti/login',
                "lti_toolurl_ContentItemSelectionRequest" => $toolurl . '/lti/deeplink',
                "typeid" => $deploymentid,
                "lti_forcessl" => '1',
                "lti_clientid" => $kialoconfig->get_client_id(),
                "cmid" => $cmid,
                "course" => $courseid,
                "intro" => "",
                "introformat" => 5,
                "sendname" => LTI_SETTING_ALWAYS,
            "sendemailaddr" => LTI_SETTING_ALWAYS,
        ];
    }

    public function init_resource_link(int $courseid, int $coursemoduleid, string $deploymentid, int $moodleuserid): string {
        global $DB, $CFG;

        $kialo = $DB->get_record('kialo', ['deployment_id' => $deploymentid], '*', MUST_EXIST);
        if (!$kialo) {
            throw new \moodle_exception("errors:ltilogin", "kialo", "", null, "invalid deployment id");
        }

        $config = $this->moodle_lti_config($deploymentid, $courseid, $coursemoduleid, $kialo->id);
        $instance = (object) ["id" => $kialo->id];
        $msgtype = 'basic-lti-launch-request';
        $form = lti_initiate_login($courseid, $coursemoduleid, $instance, $config, $msgtype, '', '', $moodleuserid);

        // Customize the issuer URL to signal that is the Kialo plugin that is initiating the login, not Moodle's default LTI.
        $form = str_replace($CFG->wwwroot, $CFG->wwwroot . '/mod/kialo', $form);

        return $form;
    }

    public function lti_auth(): string {
        global $SESSION, $USER, $DB;
        // TODO: Validate redirect URI and deployment ID?

        // LTI request parameters.
        $scope = optional_param('scope', '', PARAM_TEXT);
        $responsetype = optional_param('response_type', '', PARAM_TEXT);
        $clientid = optional_param('client_id', '', PARAM_TEXT);
        $redirecturi = optional_param('redirect_uri', '', PARAM_URL);
        $loginhint = optional_param('login_hint', '', PARAM_TEXT);
        $ltimessagehintenc = optional_param('lti_message_hint', '', PARAM_TEXT);
        $state = optional_param('state', '', PARAM_TEXT);
        $responsemode = optional_param('response_mode', '', PARAM_TEXT);
        $nonce = optional_param('nonce', '', PARAM_TEXT);
        $prompt = optional_param('prompt', '', PARAM_TEXT);

        // Validate the request.
        $error = "";
        $ok = !empty($scope) && !empty($responsetype) && !empty($clientid) &&
                !empty($redirecturi) && !empty($loginhint) &&
                !empty($nonce);
        if (!$ok) {
            $error = "invalid request";
        } else if ($scope !== 'openid') {
            $error = "invalid scope";
        } else if ($responsetype !== "id_token") {
            $error = "unsupported response type";
        } else if ($clientid !== kialo_config::get_instance()->get_client_id()) {
            $error = "unauthorized client";
        } else if ($loginhint !== $USER->id) {
            $error = "access denied";
        } else if ($prompt !== "none") {
            $error = "invalid prompt";
        } else if ($responsemode !== "form_post") {
            $error = "invalid response mode";
        }

        if (!empty($error)) {
            throw new \moodle_exception("errors:ltiauth", "kialo", "", null, "Invalid request");
        }

        $ltimessagehint = json_decode($ltimessagehintenc);
        $launchid = $ltimessagehint->launchid;

        list($courseid, $typeid, $id, $messagetype, $foruserid, $titleb64, $textb64) = explode(',', $SESSION->$launchid, 7);
        $deploymentid = $typeid;

        assert($id);
        $course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
        $cm = get_coursemodule_from_id('kialo', $id, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        require_login($course, true, $cm);
        require_capability('mod/lti:view', $context);

        // Ensure there is actually a Kialo activity matching the deployment ID.
        // TODO: Also somehow validate that the user is allowed to access the activity?
        $kialo = $DB->get_record('kialo', ['deployment_id' => $deploymentid], '*', MUST_EXIST);
        if (!$kialo) {
            throw new \moodle_exception("errors:ltiauth", "kialo", "", null, "invalid deployment id");
        }

        $config = $this->moodle_lti_config($deploymentid, $courseid, $cm->id, $kialo->id);
        list($endpoint, $params) = $this->get_launch_data($config, $nonce, $messagetype, intval($foruserid));

        if (isset($state)) {
            $params['state'] = $state;
        }
        unset($SESSION->lti_message_hint);

        $r = '<form action="' . $redirecturi . "\" name=\"ltiAuthForm\" id=\"ltiAuthForm\" " .
                "method=\"post\" enctype=\"application/x-www-form-urlencoded\">\n";
        if (!empty($params)) {
            foreach ($params as $key => $value) {
                $key = htmlspecialchars($key, ENT_COMPAT);
                $value = htmlspecialchars($value, ENT_COMPAT);
                $r .= "  <input type=\"hidden\" name=\"{$key}\" value=\"{$value}\"/>\n";
            }
        }
        $r .= "</form>\n";
        $r .= "<script type=\"text/javascript\">\n" .
                "//<![CDATA[\n" .
                "document.ltiAuthForm.submit();\n" .
                "//]]>\n" .
                "</script>\n";
        return $r;
    }

    /**
     * @param \stdClass $config
     * @param string $nonce
     * @param string $messagetype
     * @param int $foruserid
     * @return array
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function get_launch_data($config, $nonce, $messagetype = 'basic-lti-launch-request', $foruserid = 0) {
        global $PAGE, $USER, $CFG;

        $messagetype = $messagetype ? $messagetype : 'basic-lti-launch-request';
        $endpoint = lti_ensure_url_is_https($config->lti_toolurl);
        $orgid = parse_url($CFG->wwwroot)['host'];
        $ltiversion = '1.3.0';

        $course = $PAGE->course;
        $requestparams = lti_build_request($config, (array) $config, $course, null, false, $messagetype, $foruserid);
        $requestparams = array_merge($requestparams, lti_build_standard_message($config, $orgid, $ltiversion, $messagetype));
        $requestparams['launch_presentation_document_target'] = 'window';

        // User details (standard LTI / OIDC claims).
        $requestparams['custom_name'] = fullname($USER);
        $requestparams['custom_email'] = $USER->email;
        $requestparams['custom_given_name'] = $USER->firstname;
        $requestparams['custom_family_name'] = $USER->lastname;
        $requestparams['custom_middle_name'] = $USER->middlename;
        $requestparams['custom_locale'] = $USER->lang;
        $requestparams['custom_picture'] = (new \user_picture($USER))->get_url($PAGE);
        $requestparams['custom_zoneinfo'] = core_date::get_user_timezone_object()->getName();
        $requestparams['custom_preferred_username'] = $USER->username;

        // Override issuer
        $requestparams['iss'] = $CFG->wwwroot . '/mod/kialo';

        // Sign the message.
        $key = $config->lti_clientid;
        $parms = lti_sign_jwt($requestparams, $endpoint, $key, $config->typeid, $nonce);

        $endpointurl = new \moodle_url($endpoint);
        $endpointparams = $endpointurl->params();

        // Strip querystring params in endpoint url from $parms to avoid duplication.
        if (!empty($endpointparams) && !empty($parms)) {
            foreach (array_keys($endpointparams) as $paramname) {
                if (isset($parms[$paramname])) {
                    unset($parms[$paramname]);
                }
            }
        }

        return array($endpoint, $parms);
    }
}
