<?php

namespace mod_kialo;

use moodle_url;
use OAT\Library\Lti1p3Core\Platform\Platform;
use OAT\Library\Lti1p3Core\Registration\Registration;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;
use OAT\Library\Lti1p3Core\Tool\Tool;

class kialo_config {
    private static $instance = null;

    public function get_tool_url() {
        $targeturlfromenv = getenv('TARGET_KIALO_URL');
        if (!empty($targeturlfromenv)) {
            return $targeturlfromenv;
        } else {
            return "https://www.kialo-edu.com";
        }
    }

    public static function get_instance() {
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
     * @return Platform
     */
    public function get_platform(): Platform {
        return new Platform(
                'kialo-moodle-plguin',                              // Identifier.
                'Kialo Moodle Plugin',                              // Name.
                (new moodle_url('/mod/kialo'))->out(),              // Audience.
                (new moodle_url('/mod/kialo/lti_auth.php'))->out(), // OIDC authentication url.
        );
    }

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

    public function create_registration(?string $deploymentid = null): Registration {
        $tool = $this->get_tool();
        $platformjwksurl = (new moodle_url('/mod/kialo/lti_jwks.php'))->out();
        $tooljwksurl = $this->get_tool_url() . "/lti/jwks.json";
        $deploymentids = $deploymentid ? [$deploymentid] : [];

        return new Registration(
                'kialo-moodle-registration',        // Registration ID. Since we don't need to store this, we can use a constant.
                $this->get_client_id(),             // Client ID.
                $this->get_platform(),              // Platform.
                $tool,                              // Tool.
                $deploymentids,                     // Deployment IDs.
                $this->get_platform_keychain(),     // Platform's keychain used for signing messages.
                null,                               // Kialo's keychain for verifying messages. Is downloaded from the JWKS URL.
                $platformjwksurl,                   // JWKS URL for the platform. Unused by us.
                $tooljwksurl,                       // JWKS URL used to download Kialo's keyset.
        );
    }
}
