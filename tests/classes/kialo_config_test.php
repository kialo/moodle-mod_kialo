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
 * mod_kialo config test.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onward, Kialo GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

class kialo_config_test extends \advanced_testcase {
    public function test_default_tool_url() {
        // The variable TARGET_KIALO_URL is only set in Kialo test environments. By default it's not defined.
        putenv("TARGET_KIALO_URL=");

        // In production, kialo-edu.com is always the endpoint for our plugin.
        $this->assertEquals("https://www.kialo-edu.com", kialo_config::get_instance()->get_tool_url());
    }

    public function test_custom_tool_url() {
        putenv("TARGET_KIALO_URL=http://localhost:5000");
        $this->assertEquals("http://localhost:5000", kialo_config::get_instance()->get_tool_url());
    }

    public function test_private_key_generation() {
        // A key should have already been generated during installation by mod_kialo_verify_private_key() in upgradelib.php.
        $keychain = kialo_config::get_instance()->get_platform_keychain();
        $this->assertNotNull($keychain);

        $this->assertEquals("kialo", $keychain->getKeySetName());
        $this->assertNotEmpty($keychain->getPublicKey());
        $this->assertNotEmpty($keychain->getPrivateKey());
    }

    public function test_client_id() {
        // A clientid should have been generated during installation by mod_kialo_generate_client_id() in upgradelib.php.
        $this->assertNotEmpty(kialo_config::get_instance()->get_client_id());
    }

    public function test_get_platform() {
        $platform = kialo_config::get_instance()->get_platform();

        $this->assertEquals("https://www.example.com/moodle/mod/kialo", $platform->getAudience());
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_auth.php", $platform->getOidcAuthenticationUrl());
        $this->assertEquals("kialo-moodle-plugin", $platform->getIdentifier());
        $this->assertEquals("Kialo Moodle Plugin", $platform->getName());
    }

    public function test_get_tool() {
        putenv("TARGET_KIALO_URL=");
        $tool = kialo_config::get_instance()->get_tool();

        $this->assertEquals("kialo-edu", $tool->getIdentifier());
        $this->assertEquals("Kialo Edu", $tool->getName());
        $this->assertEquals("https://www.kialo-edu.com", $tool->getAudience());
        $this->assertEquals("https://www.kialo-edu.com/lti/launch", $tool->getLaunchUrl());
        $this->assertEquals("https://www.kialo-edu.com/lti/login", $tool->getOidcInitiationUrl());
        $this->assertEquals("https://www.kialo-edu.com/lti/deeplink", $tool->getDeepLinkingUrl());
    }

    public function test_registration() {
        $registration = kialo_config::get_instance()->create_registration("DEPLID1234");
        $this->assertNotNull($registration);

        $this->assertEquals("kialo-moodle-registration", $registration->getIdentifier());
        $this->assertNotNull($registration->getPlatform());
        $this->assertNotNull($registration->getTool());
        $this->assertEquals(kialo_config::get_instance()->get_client_id(), $registration->getClientId());
        $this->assertEquals($registration->getDefaultDeploymentId(), "DEPLID1234");
        $this->assertEquals($registration->getDeploymentIds(), ["DEPLID1234"]);
        $this->assertNotNull($registration->getPlatformKeyChain());
        $this->assertNull($registration->getToolKeyChain());
        $this->assertEquals("https://www.kialo-edu.com/lti/jwks.json", $registration->getToolJwksUrl());
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_jwks.php", $registration->getPlatformJwksUrl());
    }
}
