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
 * Static registration repository test.
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
 * @covers \mod_kialo\static_registration_repository
 */
class static_registration_repository_test extends \basic_testcase {

    /**
     * @covers \mod_kialo\static_registration_repository::find
     * @covers \mod_kialo\static_registration_repository::findAll
     * @covers \mod_kialo\static_registration_repository::findByClientId
     */
    public function test_repository() {
        $reg = kialo_config::get_instance()->create_registration("42");
        $this->assertEquals("kialo-moodle-registration", $reg->getIdentifier());

        $repo = new static_registration_repository($reg);

        $this->assertEquals($reg, $repo->find("kialo-moodle-registration"));
        $this->assertNull($repo->find("some-other-registration"));
        $this->assertEquals([$reg], $repo->findAll());
        $this->assertEquals($reg, $repo->findByClientId($reg->getClientId()));
    }

    /**
     * @covers \mod_kialo\static_registration_repository::findByPlatformIssuer
     */
    public function test_find_by_platform_issuer() {
        $reg = kialo_config::get_instance()->create_registration("42");
        $repo = new static_registration_repository($reg);

        $this->assertEquals($reg, $repo->findByPlatformIssuer("https://www.example.com/moodle/mod/kialo"));
        $this->assertNull($repo->findByPlatformIssuer("https://www.other.site"));
    }

    /**
     * @covers \mod_kialo\static_registration_repository::findByToolIssuer
     */
    public function test_find_by_tool_issuer() {
        $reg = kialo_config::get_instance()->create_registration("42");
        $repo = new static_registration_repository($reg);

        $this->assertEquals($reg, $repo->findByToolIssuer("https://www.kialo-edu.com"));
        $this->assertNull($repo->findByToolIssuer("https://some.other.tool"));
    }
}
