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
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the mod_kialo config.
 */
final class kialo_config_test extends \advanced_testcase {
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest();

        // The variable TARGET_KIALO_URL is only set in Kialo test environments. By default it's not defined.
        putenv("TARGET_KIALO_URL=");
        set_config('kialourl', null, 'mod_kialo');
    }

    /**
     * Tests the default tool URL.
     * @covers \mod_kialo\kialo_config::get_instance::get_tool_url
     */
    public function test_default_tool_url(): void {
        // In production, kialo-edu.com is always the endpoint for our plugin.
        $this->assertEquals("https://www.kialo-edu.com", kialo_config::get_instance()->get_tool_url());
    }

    /**
     * Tests that the tool URL can be overridden via environment variable.
     * @covers \mod_kialo\kialo_config::get_instance::get_tool_url
     */
    public function test_custom_tool_url_via_env(): void {
        putenv("TARGET_KIALO_URL=http://localhost:5000");
        $this->assertEquals("http://localhost:5000", kialo_config::get_instance()->get_tool_url());
    }

    /**
     * Tests that the tool URL can be overridden via environment config, and it that is has precedence over the env var.
     * @covers \mod_kialo\kialo_config::get_instance::get_tool_url
     */
    public function test_custom_tool_url_via_config(): void {
        set_config('kialourl', 'https://www.example.com', 'mod_kialo');
        putenv("TARGET_KIALO_URL=http://localhost:5000");
        $this->assertEquals("https://www.example.com", kialo_config::get_instance()->get_tool_url());
    }

    /**
     * Tests private key generation.
     * @covers \mod_kialo\kialo_config::get_instance::get_platform_keychain
     */
    public function test_private_key_generation(): void {
        // A key should have already been generated during installation by mod_kialo_verify_private_key() in upgradelib.php.
        $keychain = kialo_config::get_instance()->get_platform_keychain();
        $this->assertNotNull($keychain);

        $this->assertEquals("kialo", $keychain->getKeySetName());
        $this->assertNotEmpty($keychain->getPublicKey());
        $this->assertNotEmpty($keychain->getPrivateKey());
    }

    /**
     * Tests client id generation.
     * @covers \mod_kialo\kialo_config::get_instance::get_client_id
     */
    public function test_client_id(): void {
        // A clientid should have been generated during installation by mod_kialo_generate_client_id() in upgradelib.php.
        $this->assertNotEmpty(kialo_config::get_instance()->get_client_id());
    }

    /**
     * Tests the platform configuration.
     * @covers \mod_kialo\kialo_config::get_instance::get_platform
     */
    public function test_get_platform(): void {
        $platform = kialo_config::get_instance()->get_platform();

        $this->assertEquals("https://www.example.com/moodle/mod/kialo", $platform->getAudience());
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_auth.php", $platform->getOidcAuthenticationUrl());
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_token.php", $platform->getOAuth2AccessTokenUrl());
        $this->assertEquals("kialo-moodle-plugin", $platform->getIdentifier());
        $this->assertEquals("Kialo Moodle Plugin", $platform->getName());
    }


    /**
     * Tests the tool configuration.
     * @covers \mod_kialo\kialo_config::get_instance::get_tool
     * @dataProvider tool_provider
     */
    public function test_get_tool_parametrized(bool $usedeeplink, string $expectedoidcinitiationurl): void {
        // Ensure no environment variable interferes.
        putenv("TARGET_KIALO_URL=");
        // Get the tool with the parameter.
        $tool = kialo_config::get_instance()->get_tool($usedeeplink);

        // Assert the common values.
        $this->assertEquals("kialo-edu", $tool->getIdentifier());
        $this->assertEquals("Kialo Edu", $tool->getName());
        $this->assertEquals("https://www.kialo-edu.com", $tool->getAudience());
        $this->assertEquals("https://www.kialo-edu.com/lti/launch", $tool->getLaunchUrl());
        $this->assertEquals("https://www.kialo-edu.com/lti/deeplink", $tool->getDeepLinkingUrl());

        // Assert the URL that changes based on deep linking.
        $this->assertEquals($expectedoidcinitiationurl, $tool->getOidcInitiationUrl());
    }

    /**
     * Provides the tool configuration parameters.
     * @return array
     */
    public static function tool_provider(): array {
        return [
            // When not using deep linking.
            'normal tool' => [false, "https://www.kialo-edu.com/lti/start"],
            // When using deep linking.
            'deeplink tool' => [true, "https://www.kialo-edu.com/lti/login"],
        ];
    }

    /**
     * Tests the registration creation.
     * @covers \mod_kialo\kialo_config::get_instance::create_registration
     */
    public function test_registration(): void {
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

    /**
     * Tests the registration repository.
     * @covers \mod_kialo\kialo_config::get_instance::get_registration_repository
     */
    public function test_registration_repository(): void {
        $repo = kialo_config::get_instance()->get_registration_repository("DEPLID1234");
        $this->assertNotNull($repo);

        $this->assertNull($repo->find("NONEXISTENT"));

        $registration = $repo->find("kialo-moodle-registration");
        $this->assertNotNull($registration);
        $this->assertEquals("kialo-moodle-registration", $registration->getIdentifier());
    }
}
