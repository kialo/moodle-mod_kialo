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
 * User authenticator result implementation needed for the LTI implementation.
 *
 * @package    mod_kialo
 * @category   activity
 * @copyright  2023 Kialo GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

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
