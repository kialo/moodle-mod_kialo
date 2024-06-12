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
 * Used in the LTI flow to generate a static nonce (instead of a new one everytime).
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;

/**
 * A static nonce generator that always returns the same nonce.
 */
class static_nonce_generator implements NonceGeneratorInterface {

    /**
     * @var string
     */
    private string $nonce;

    /**
     * Creates a new static nonce generator that always returns the given nonce.
     * @param string $nonce the nonce to return everytime.
     */
    public function __construct(string $nonce) {
        $this->nonce = $nonce;
    }

    /**
     * Returns the nonce that the class was created with.
     * @param int|null $ttl TTL is ignored, because it's not supported.
     * @return NonceInterface
     */
    public function generate(?int $ttl = null): NonceInterface {
        return new Nonce($this->nonce);
    }
}
