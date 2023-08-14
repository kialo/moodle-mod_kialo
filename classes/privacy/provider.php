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
 * Privacy subsystem implementation for mod_kialo.
 *
 * @package mod_kialo
 * @author Kialo GmbH <support@kialo-edu.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2023 onwards Kialo GmbH (https://www.kialo-edu.com/)
 * @see https://docs.moodle.org/dev/Privacy_API
 */

namespace mod_kialo\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\metadata\null_provider;

defined('MOODLE_INTERNAL') || die();

/**
 * We do not store any personal data in our Moodle database table.
 * But we send and may store some personal data when provisioning accounts on Kialo.
 * See `classes/user_authenticator.php` or the `classes/lti_flow.php` for details.
 *
 * @package    mod_kialo
 * @category   privacy
 * @copyright  2023 onward Kialo GmbH (https://www.kialo-edu.com)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider, null_provider {
    public static function get_metadata(collection $collection): collection {
        $collection->add_external_location_link(
                'kialo',
                [
                        'userid' => 'privacy:metadata:kialo:userid',
                        'username' => 'privacy:metadata:kialo:username',
                        'fullname' => 'privacy:metadata:kialo:fullname',
                        'language' => 'privacy:metadata:kialo:language',
                        'timezone' => 'privacy:metadata:kialo:timezone',
                        'picture' => 'privacy:metadata:kialo:picture',
                        'email' => 'privacy:metadata:kialo:email',
                        'courseid' => 'privacy:metadata:kialo:courseid',
                        'role' => 'privacy:metadata:kialo:role',
                ],
                'privacy:metadata:kialo'
        );

        return $collection;
    }

    public static function get_reason(): string {
        return "privacy:metadata:kialo:nullproviderreason";
    }
}
