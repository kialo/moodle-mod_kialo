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
 * User authentication implementation needed for the LTI implementation.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use context_course;
use core_date;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

/**
 * An LTI user authenticator that checks if the correct Moodle user is logged in.
 *
 * Also checks that the user is part of the same course module,
 * and has the required capabilities. The returned user identity contains personal information needed to automatically
 * create a new account on Kialo.
 */
class user_authenticator implements UserAuthenticatorInterface {
    /**
     * Authenticates the user.
     * @param RegistrationInterface $registration
     * @param string $loginhint The login hint is in the form of "course_id/moodle_user_id".
     * @return UserAuthenticationResultInterface
     * @throws \coding_exception
     * @throws \moodle_exception
     * @throws \require_login_exception
     */
    public function authenticate(RegistrationInterface $registration, string $loginhint): UserAuthenticationResultInterface {
        global $USER;
        global $PAGE;

        // The login hint is in the form of "course_id/moodle_user_id".
        [$courseid, $userid] = explode("/", $loginhint);
        $courseid = intval($courseid);

        require_login($courseid, false);

        $context = context_course::instance($courseid);

        if ($userid !== $USER->id || !has_capability("mod/kialo:view", $context)) {
            return new user_authentication_result(false);
        }

        $avatar = new \user_picture($USER);
        $avatar->size = 128;

        return new user_authentication_result(
            true,
            new UserIdentity(
                $USER->id,
                fullname($USER),
                $USER->email,
                $USER->firstname,
                $USER->lastname,
                $USER->middlename,
                $USER->lang,
                $USER->picture ? $avatar->get_url($PAGE) : null, // If no picture is set, we don't send the default picture.
                // Additional claims our app needs, but which are not required fields in LTI.
                // Using OIDC standard claims, see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims.
                [
                        "zoneinfo" => core_date::get_user_timezone_object()->getName(),
                        "preferred_username" => $USER->username,
                ]
            )
        );
    }
}
