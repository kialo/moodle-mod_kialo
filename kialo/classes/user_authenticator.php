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

        // TODO: Implement authenticate() method to perform user authentication (ex: session, LDAP, etc)
        // TODO: Check if Moodle user is logged in
        $userid = $loginHint;
        assert($userid == $USER->id);
        // TODO: Return identity of the user
        return new user_authentication_result(true,
                new UserIdentity('userIdentifier', 'userName', 'me2@me.com'));
    }
}
