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
 * Key chain repository needed for the LTI implementation.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainRepositoryInterface;

// phpcs:disable moodle.NamingConventions.ValidFunctionName.LowercaseMethod
// phpcs:disable moodle.NamingConventions.ValidVariableName.VariableNameLowerCase

/**
 * A static keychain repository that always returns the same keychain.
 */
class static_keychain_repository implements KeyChainRepositoryInterface {

    /**
     * The registration that is returned by the repository.
     * @var KeyChainInterface
     */
    private $keychain;

    /**
     * Creates a new registration repository that only contains the given registration.
     * @param KeyChainInterface $keychain
     */
    public function __construct(KeyChainInterface $keychain) {
        $this->keychain = $keychain;
    }

    /**
     * If the given identifier matches the keychain identifier, the keychain is returned.
     * @param string $identifier The registration identifier.
     * @return RegistrationInterface|null
     */
    public function find(string $identifier): ?KeyChainInterface {
        if ($this->keychain->getIdentifier() !== $identifier) {
            return null;
        }
        return $this->keychain;
    }

    /**
     * If the given key set name matches the keychain set name, the keychain is returned.
     * @param string $keySetName
     * @return array|KeyChainInterface[]
     */
    public function findByKeySetName(string $keySetName): array {
        if ($this->keychain->getKeySetName() !== $keySetName) {
            return [];
        }
        return [$this->keychain];
    }
}
