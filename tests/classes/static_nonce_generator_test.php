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
 * static nonce generator test.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onward, Kialo GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the static nonce generator.
 * @covers \mod_kialo\static_nonce_generator
 */
class static_nonce_generator_test extends \basic_testcase {
    public function test_generator() {
        $generator = new static_nonce_generator("NONCE1234");
        $nonce = $generator->generate();

        $this->assertNotNull($nonce);
        $this->assertEquals("NONCE1234", $nonce->getValue());
        $this->assertFalse($nonce->isExpired());
        $this->assertNull($nonce->getExpiredAt());
    }
}
