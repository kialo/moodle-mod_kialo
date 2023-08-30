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
 * @copyright   2023 Kialo GmbH <support@kialo-edu.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $hassiteconfig;
global $ADMIN;


require_once($CFG->dirroot . '/mod/kialo/lib.php');

// Terms and conditions need to have been accepted before the activity can be used.
if ($ADMIN->fulltree) {
    /** @var admin_settingpage $setting */
    $setting = new admin_setting_configcheckbox(
            'mod_kialo/acceptterms',
            new lang_string('acceptterms', 'mod_kialo'),
            new lang_string('acceptterms_desc', 'mod_kialo', [
                    "terms" => "https://www.kialo-edu.com/terms",
                    "privacy" => "https://www.kialo-edu.com/privacy",
                    "data_security" => "https://support.kialo-edu.com/en/hc/kialo-edu-data-security-and-privacy-plan/"
            ]),
            0
    );
    $settings->add($setting);
    $setting->set_updatedcallback('kialo_update_visibility_depending_on_accepted_terms');
}
