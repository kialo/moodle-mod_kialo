<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

/**
 * A static user authentication result that always returns the same result.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
 * phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
 */
class user_authentication_result implements UserAuthenticationResultInterface {
    /** @var bool */
    private $success;

    /** @var UserIdentityInterface|null */
    private $useridentity;

    public function __construct(bool $success, ?UserIdentityInterface $userIdentity = null) {
        $this->success = $success;
        $this->useridentity = $userIdentity;
    }

    public function isSuccess(): bool {
        return $this->success;
    }

    public function isAnonymous(): bool {
        return null === $this->useridentity;
    }

    public function getUserIdentity(): ?UserIdentityInterface {
        return $this->useridentity;
    }
}
