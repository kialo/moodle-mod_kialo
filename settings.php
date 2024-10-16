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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Adds admin settings for the plugin.
 *
 * @package     mod_kialo
 * @category    admin
 * @copyright   2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_kialo\admin\kialo_configcheckbox;

defined('MOODLE_INTERNAL') || die();

global $hassiteconfig;
global $ADMIN;

require_once($CFG->dirroot . '/mod/kialo/lib.php');
require_once(__DIR__ . '/constants.php');

// Terms and conditions need to have been accepted before the activity can be used.
if ($ADMIN->fulltree) {
    $termswerealreadyaccepted = get_config('mod_kialo', 'acceptterms') == 1;
    $acceptterms = new kialo_configcheckbox(
        'mod_kialo/acceptterms',
        new lang_string('acceptterms', 'mod_kialo'),
        new lang_string('acceptterms_desc', 'mod_kialo', [
            "terms" => MOD_KIALO_TERMS_LINK,
            "privacy" => MOD_KIALO_PRIVACY_LINK,
            "data_security" => MOD_KIALO_DATA_SECURITY_LINK,
        ]),
        $termswerealreadyaccepted ? 1 : 0,
    );

    // Once the terms have been accepted, they cannot be unaccepted.
    if ($termswerealreadyaccepted) {
        $acceptterms->force_readonly(get_config('mod_kialo', 'acceptterms'));
    }

    // Enable the module once the terms have been accepted.
    $acceptterms->set_updatedcallback('kialo_update_visibility_depending_on_accepted_terms');

    /** @var admin_settingpage $settings */
    $settings->add($acceptterms);

    // For internal Kialo use only: Allow changing the Kialo target URL.
    if (!empty(getenv('TARGET_KIALO_URL'))) {
        $kialourl = new admin_setting_configtext(
            'mod_kialo/kialourl',
            new lang_string('kialourl', 'mod_kialo'),
            new lang_string('kialourl_desc', 'mod_kialo'),
            '',  // If left blank, this defaults to the TARGET_KIALO_URL env var or 'https://www.kialo-edu.com'.
        );

        $settings->add($kialourl);
    }
}
