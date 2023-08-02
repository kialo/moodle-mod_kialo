<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\Security\User\UserAuthenticatorInterface;
use OAT\Library\Lti1p3Core\User\UserIdentity;

class user_authenticator implements UserAuthenticatorInterface {
    public function authenticate(
            RegistrationInterface $registration,
            string $loginHint
    ): UserAuthenticationResultInterface {
        global $USER;
        global $PAGE;

        $userid = $loginHint;
        assert($userid == $USER->id);

        return new user_authentication_result(true,
                # TODO PM-42104: Add language and timezone
                new UserIdentity(
                        $USER->username,
                        fullname($USER),
                        $USER->email,
                        $USER->firstname,
                        $USER->lastname,
                        $USER->middlename,
                        null,
                        (new \user_picture($USER))->get_url($PAGE),
                ));
    }
}
