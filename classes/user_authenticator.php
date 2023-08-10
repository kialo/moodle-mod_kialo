<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

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

        require_login(intval($courseid), false);

        if ($userid !== $USER->id) {
            return new user_authentication_result(false);
        }

        return new user_authentication_result(true,
                new UserIdentity(
                        $USER->username,
                        fullname($USER),
                        $USER->email,
                        $USER->firstname,
                        $USER->lastname,
                        $USER->middlename,
                        $USER->lang,
                        (new \user_picture($USER))->get_url($PAGE),
                        [
                                "zoneinfo" => core_date::get_user_timezone_object()->getName(),
                        ]
                ));
    }
}
