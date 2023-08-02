<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Security\User\Result\UserAuthenticationResultInterface;
use OAT\Library\Lti1p3Core\User\UserIdentityInterface;

class user_authentication_result implements UserAuthenticationResultInterface {
    /** @var bool */
    private $success;

    /** @var UserIdentityInterface|null */
    private $userIdentity;

    public function __construct(bool $success, ?UserIdentityInterface $userIdentity = null) {
        $this->success = $success;
        $this->userIdentity = $userIdentity;
    }

    public function isSuccess(): bool {
        return $this->success;
    }

    public function isAnonymous(): bool {
        return null === $this->userIdentity;
    }

    public function getUserIdentity(): ?UserIdentityInterface {
        return $this->userIdentity;
    }
}
