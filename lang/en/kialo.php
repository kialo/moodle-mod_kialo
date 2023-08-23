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
 * Note: Due to technical limitations of Moodle's translation system AMOS,
 * string concatenation, interpolation, and some other string features are not allowed.
 * All strings must be simple assignments of scalar values, i.e. `$string['key'] = 'value';`.
 *
 * @package     mod_kialo
 * @category    string
 * @copyright   2023 Kialo Inc. <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['modulename'] = 'Kialo Discussion';
$string['modulenameplural'] = 'Kialo Discussions';
$string['pluginname'] = 'Kialo Discussion';
$string['hello'] = 'Hello World!';
$string['kialo:addinstance'] = 'Add a new Kialo Discussion';
$string['pluginadministration'] = 'Edit Kialo Discussion';
$string['kialoname'] = 'Activity Name';
$string['kialosettings'] = 'Settings';
$string['kialofieldset'] = 'Kialo Fieldset';
$string['select_discussion'] = 'Select Discussion';
$string['select_discussion_help'] =
        'Opens Kialo in a new tab to select a discussion to link to this activity. Requires a Kialo account.';
$string['discussion_title'] = 'Discussion Name';
$string['deploymentid'] = "Discussion Linked";
$string['cachedef_nonces'] = "Used to store temporary nonces to secure LTI requests.";
$string['defaultactivitynameprefix'] = "Kialo Discussion:";

// Help texts.
$string['modulename_help'] =
        'The Kialo activity allows you to embed a Kialo discussion into your Moodle course. Students can participate in the discussion directly from Moodle, without having to create a separate Kialo account. The Kialo activity is a great way to foster critical thinking and collaborative learning.';
$string['modulename_link'] = 'https://support.kialo-edu.com/en/hc/moodle';

// Displayed while redirecting to Kialo during the LTI flows.
$string['redirect_title'] = "Loading";
$string['redirect_loading'] = "Loading";

// Errors.
$string["errors:nopermissiontoview"] = "You do not have permission to view this activity.";
$string["errors:ltiauth"] = "Authentication failed due to an unexpected error. Please try again.";
$string["errors:resourcelink"] = "Activity cannot be displayed, due to an unexpected error. Please try again.";
$string['errors:deeplinking'] = "Something went wrong with the discussion selection. Please try again.";

// Privacy API.
$string['privacy:metadata:kialo'] =
        'User data needs to be exchanged with Kialo Edu in order to automatically create accounts' .
        ' for Moodle users on kialo-edu.com, and to make the user experience as seamless as possible.';

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

// Settings.
$string["acceptterms"] = "Accept Terms of Service";
$string["acceptterms_desc"] =
        'By checking the checkbox you accept Kialo Edu’s <a href="{$a->terms}" target="_blank">terms of service</a> on behalf of ' .
        'all users of this Moodle instance. <a href="{$a->privacy}" target="_blank">Click here</a> to have a look at our ' .
        'Data Security and Privacy Plan.';
$string["termsnotaccepted"] =
        'Before enabling this plugin, you must confirm that you have read and accepted the <a href="{$a}">terms of service</a>.';
