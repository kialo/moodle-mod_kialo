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
 * @category   activity
 * @copyright  2023 Kialo GmbH
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_kialo;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

/**
 * A static registration repository that always returns the same registration.
 *
 * phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
 * phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase
 */
class static_registration_repository implements RegistrationRepositoryInterface {

    /**
     * @var RegistrationInterface
     */
    private $registration;

    public function __construct(RegistrationInterface $registration) {
        $this->registration = $registration;
    }

    public function find(string $identifier): ?RegistrationInterface {
        if ($this->registration->getIdentifier() !== $identifier) {
            return null;
        }
        return $this->registration;
    }

    public function findAll(): array {
        return [$this->registration];
    }

    public function findByClientId(string $clientId): ?RegistrationInterface {
        if ($this->registration->getClientId() !== $clientId) {
            return null;
        }
        return $this->registration;
    }

    public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface {
        $platform = $this->registration->getPlatform();
        if ($platform->getAudience() !== $issuer) {
            return null;
        }
        if ($clientId !== null && $clientId !== $this->registration->getClientId()) {
            return null;
        }
        return $this->registration;
    }

    public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface {
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
