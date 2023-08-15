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
 * Plugin strings are defined here.
 *
 * @package     mod_kialo
 * @category    string
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Kialo Debate';
$string['modulenameplural'] = 'Kialo Debates';
$string['pluginname'] = 'Kialo Debate';
$string['hello'] = 'Hello World!';
$string['kialo:addinstance'] = 'Add a new Kialo Debate';
$string['pluginadministration'] = 'Edit Kialo Debate';
$string['kialoname'] = 'Activity Name';
$string['kialoname_help'] = 'Get help';
$string['kialosettings'] = 'Settings';
$string['kialofieldset'] = 'Kialo Fieldset';
$string['discussion_id'] = 'Discussion Id';
$string['select_discussion'] = 'Select Discussion';
$string['select_discussion_help'] =
        'Opens Kialo in a new tab to select a discussion to link to this activity. Requires a Kialo account.';
$string['discussion_title'] = 'Discussion Title';
$string['deploymentid'] = "Discussion Linked";
$string['cachedef_nonces'] = "Used to store temporary nonces to secure LTI requests.";

$string['deeplinking_error_generic_title'] = "Discussion Selection Error";
$string['deeplinking_error_generic_description'] = "Something went wrong with the discussion selection. Please try again.";

// Displayed while redirecting to Kialo during the deeplinking flow.
$string['lti_select_redirect_title'] = "Loading";
$string['lti_select_redirect_loading'] = "Loading";

// Displayed initially when the student opens the activity, redirecting to Kialo's LTI login endpoint.
$string['view_redirect_title'] = "Loading";
$string['view_redirect_loading'] = "Loading";

// Displayed when users were redirected back from Kialo's LTI login to Moodle, redirecting now to Kialo's LTI launch endpoint.
$string['lti_auth_redirect_title'] = "Loading";
$string['lti_auth_redirect_loading'] = "Loading";

// Errors.
$string["nopermissiontoview"] = "You do not have permission to view this activity.";

// Privacy API.
$string['privacy:metadata:kialo'] = <<<TXT
User data needs to be exchanged with Kialo Edu in order to automatically create accounts for Moodle users on kialo-edu.com,
and to make the user experience is as seamless as possible.
TXT;

$string['privacy:metadata:kialo:userid'] = 'The userid is sent from Moodle to allow you to access your data on Kialo Edu.';
$string['privacy:metadata:kialo:email'] = 'The email address is sent from Moodle to allow you to access your data on Kialo Edu.';
$string['privacy:metadata:kialo:username'] = 'The user name is sent from Moodle to set the default user name Kialo Edu.';
$string['privacy:metadata:kialo:fullname'] = 'Your full name is sent to Kialo Edu to allow a better user experience.';
$string['privacy:metadata:kialo:language'] =
        'Your language preference is sent to Kialo Edu to automatically set the user interface language.';
$string['privacy:metadata:kialo:timezone'] =
        'Your time zone preference is sent to Kialo Edu to automatically set the user time zone.';
$string['privacy:metadata:kialo:picture'] = 'Your account picture is sent to Kialo Edu to allow a better user experience.';
$string['privacy:metadata:kialo:role'] =
        'The user\'s role in the course is used to determine the correct permissions in the Kialo discussion.';
$string['privacy:metadata:kialo:courseid'] = 'The ID of the course the user is accessing Kialo Edu from.';
$string['privacy:metadata:kialo:nullproviderreason'] =
        'No user data is stored by our plugin in the Moodle database. Any data we use is stored externally on kialo-edu.com.';
