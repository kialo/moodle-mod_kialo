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


namespace mod_kialo;

use moodle_url;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Tool\Tool;

/**
 * Defines capabilities for the Kialo activity module.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class kialo_config {
    /**
     * @var kialo_config
     */
    private static $instance = null;

    /**
     * By default, the tool's (Kialo's own) current public key will be downloaded during the LTI flow.
     *
     * This can be overriden for testing purposes.
     *
     * @var KeyChainInterface|null
     */
    public ?KeyChainInterface $toolkeychain = null;

    /**
     * Returns the URL of the Kialo instance to use. By default its the production instance https://www.kialo-edu.com.
     * For testing purposes it can be overriden by setting the environment variable TARGET_KIALO_URL.
     * @return array|string
     */
    public function get_tool_url() {
        $targeturlfromenv = getenv('TARGET_KIALO_URL');
        if (!empty($targeturlfromenv)) {
            return $targeturlfromenv;
        } else {
            return "https://www.kialo-edu.com";
        }
    }

    /**
     * Returns the kialo_config singleton.
     * @return kialo_config
     */
    public static function get_instance(): kialo_config {
        if (self::$instance == null) {
            self::$instance = new kialo_config();
        }

        return self::$instance;
    }

    /**
     * The privatekey and kid are generated once when the plugin is installed, see upgradelib.php.
     *
     * @return KeyChainInterface
     * @throws \dml_exception
     */
    public function get_platform_keychain(): KeyChainInterface {
        $kid = get_config("mod_kialo", "kid");
        $privatekeystr = get_config("mod_kialo", "privatekey");
        $publickeystr = openssl_pkey_get_details(openssl_pkey_get_private($privatekeystr))['key'];

        return (new KeyChainFactory)->create(
                $kid,                       // Identifier (used for JWT kid header).
                'kialo',                    // Key set name (for grouping).
                $publickeystr,              // Public key (file or content).
                $privatekeystr,             // Private key (file or content).
                '',                         // Our key has no passphrase.
                KeyInterface::ALG_RS256     // Algorithm.
        );
    }

    /**
     * The client ID used to identify this Moodle instance when communicating with Kialo.
     * Currently we have no need for this to be unique, so we can use a constant.
     * @return string
     */
    public function get_client_id(): string {
        return "kialo-moodle-client";
    }

    /**
     * Returns the platform interface representing the Kialo moodle plugin.
     * @return Platform
     */
    public function get_platform(): Platform {
        return new Platform(
                'kialo-moodle-plugin',                              // Identifier.
                'Kialo Moodle Plugin',                              // Name.
                (new moodle_url('/mod/kialo'))->out(),              // Audience.
                (new moodle_url('/mod/kialo/lti_auth.php'))->out(), // OIDC authentication url.
        );
    }

    /**
     * Returns the LTI tool interface representing kialo-edu.com.
     * @return Tool
     */
    public function get_tool(): Tool {
        $toolurl = $this->get_tool_url();
        return new Tool(
                'kialo-edu',                // Identifier.
                'Kialo Edu',                // Name.
                $toolurl,                   // Audience.
                $toolurl . "/lti/login",    // OIDC initiation url.
                $toolurl . '/lti/launch',   // Launch url.
                $toolurl . '/lti/deeplink'  // Deep linking url.
        );
    }

    /**
     * Creates a new LTI tool registration for Kialo and one specific deployment id.
     * @param string|null $deploymentid The deployment id to use, or null, if it's not relevant.
     * @return Registration
     * @throws \dml_exception
     */
    public function create_registration(?string $deploymentid = null): Registration {
        $tool = $this->get_tool();
        $platformjwksurl = (new moodle_url('/mod/kialo/lti_jwks.php'))->out();
        $tooljwksurl = $this->get_tool_url() . "/lti/jwks.json";
        $deploymentids = $deploymentid ? [$deploymentid] : [];

        return new Registration(
                'kialo-moodle-registration',    // Registration ID. Since we don't need to store this, we can use a constant.
                $this->get_client_id(),         // Client ID.
                $this->get_platform(),          // Platform.
                $tool,                          // Tool.
                $deploymentids,                 // Deployment IDs.
                $this->get_platform_keychain(), // Platform's keychain used for signing messages.
                $this->toolkeychain,            // Kialo's keychain for verifying messages. Usuallly downloaded from the JWKS URL.
                $platformjwksurl,               // JWKS URL for the platform. Unused by us.
                $tooljwksurl,                   // JWKS URL used to download Kialo's keyset.
        );
    }
}
