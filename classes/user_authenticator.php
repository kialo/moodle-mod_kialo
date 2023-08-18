<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use context_course;
use core_date;
use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

class user_authenticator implements UserAuthenticatorInterface {
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

        return new user_authentication_result(true,
                new UserIdentity(
                        $USER->id,
                        fullname($USER),
                        $USER->email,
                        $USER->firstname,
                        $USER->lastname,
                        $USER->middlename,
                        $USER->lang,
                        (new \user_picture($USER))->get_url($PAGE),
                        // Additional claims our app needs, but which are not required fields in LTI.
                        // Using OIDC standard claims, see https://openid.net/specs/openid-connect-core-1_0.html#StandardClaims.
                        [
                                "zoneinfo" => core_date::get_user_timezone_object()->getName(),
                                "preferred_username" => $USER->username,
                        ]
                ));
    }
}
