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
 * Registration repository needed for the LTI implementation.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase

/**
 * A static registration repository that always returns the same registration.
 */
class static_registration_repository implements RegistrationRepositoryInterface {
    /**
     * The registration that is returned by the repository.
     * @var RegistrationInterface
     */
    private $registration;

    /**
     * Creates a new registration repository that only contains the given registration.
     * @param RegistrationInterface $registration
     */
    public function __construct(RegistrationInterface $registration) {
        $this->registration = $registration;
    }

    /**
     * If the given identifier matches the registration identifier, the registration is returned.
     * @param string $identifier The registration identifier.
     * @return RegistrationInterface|null
     */
    public function find(string $identifier): ?RegistrationInterface {
        if ($this->registration->getIdentifier() !== $identifier) {
            return null;
        }
        return $this->registration;
    }

    /**
     * Returns a list with one item: the registration that was given in the constructor.
     * @return RegistrationInterface[]
     */
    public function findAll(): array {
        return [$this->registration];
    }

    /**
     * If the given client id matches the registration client id, the registration is returned.
     * @param string $clientId
     * @return RegistrationInterface|null
     */
    public function findByClientId(string $clientId): ?RegistrationInterface {
        if ($this->registration->getClientId() !== $clientId) {
            return null;
        }
        return $this->registration;
    }

    /**
     * If the given issuer matches the registration platform audience, the registration is returned.
     * @param string $issuer
     * @param string|null $clientId
     * @return RegistrationInterface|null
     */
    public function findByPlatformIssuer(string $issuer, ?string $clientId = null): ?RegistrationInterface {
        $platform = $this->registration->getPlatform();
        if ($platform->getAudience() !== $issuer) {
            return null;
        }
        if ($clientId !== null && $clientId !== $this->registration->getClientId()) {
            return null;
        }
        return $this->registration;
    }

    /**
     * If the given issuer matches the registration tool audience, the registration is returned.
     * @param string $issuer
     * @param string|null $clientId
     * @return RegistrationInterface|null
     */
    public function findByToolIssuer(string $issuer, ?string $clientId = null): ?RegistrationInterface {
        $tool = $this->registration->getTool();
        if ($issuer !== $tool->getAudience()) {
            return null;
        }
        if ($clientId !== null && $clientId !== $this->registration->getClientId()) {
            return null;
        }
        return $this->registration;
    }
}
