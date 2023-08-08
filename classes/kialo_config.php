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

    private string $tool_url = "https://www.kialo-edu.com";

    private function __construct() {
        $target_url_from_env = getenv('TARGET_KIALO_URL');
        if (!empty($target_url_from_env)) {
            $this->tool_url = $target_url_from_env;
        }
    }

    public function get_tool_url() {
        return $this->tool_url;
    }

    public function set_tool_url(string $url) {
        $this->tool_url = $url;
    }

    public static function get_instance() {
        if (self::$instance == null) {
            self::$instance = new kialo_config();
        }

        return self::$instance;
    }


    /**
     * Usually the tool URL is hardcoded as kialo-edu.com in production. But for testing we
     * need to adapt the tool url to localhost, the test or staging instance etc.
     * For the sake of simplicity this infers the correct tool URL from the targetUrl, which
     * usually should be the URL of a discussion or other endpoint on the desired Kialo instance.
     *
     * @param string $targeturl some endpoint on the desired Kialo instance
     * @return string the inferred tool URL
     *
     * TODO PM-42182: Remove this function
     */
    public function override_tool_url_for_target(string $targeturl): string {
        $urlparts = parse_url($targeturl);
        $port = ($urlparts['port'] !== 80 || $urlparts['port'] !== 443) ? ":" . $urlparts['port'] : "";
        $tool_url = $urlparts['scheme'] . '://' . $urlparts['host'] . $port;
        kialo_config::get_instance()->set_tool_url($tool_url);
        return $tool_url;
    }

    /**
     * The privatekey and kid are generated once when the plugin is installed, see upgradelib.php.
     *
     * @return KeyChainInterface
     * @throws \dml_exception
     */
    public function get_platform_keychain(): KeyChainInterface {
        $kid = get_config("mod_kialo", "kid");
        $privatekey_str = get_config("mod_kialo", "privatekey");
        $publickey_str = openssl_pkey_get_details(openssl_pkey_get_private($privatekey_str))['key'];

        return (new KeyChainFactory)->create(
                $kid,                            // [required] identifier (used for JWT kid header)
                'kialo',             // [required] key set name (for grouping)
                $publickey_str,                 // [required] public key (file or content)
                $privatekey_str,                // [optional] private key (file or content)
                '',          // [optional] private key passphrase (if existing)
                KeyInterface::ALG_RS256            // [optional] algorithm (default: RS256)
        );
    }

    /**
     * @return Platform
     */
    public function get_platform(): Platform {
        return new Platform(
                'kialo-moodle-plguin',                          // [required] identifier
                'Kialo Moodle Plugin',                            // [required] name
                (new moodle_url('/mod/kialo'))->out(),              // [required] audience
                (new moodle_url('/mod/kialo/lti_auth.php'))->out(), // [optional] OIDC authentication url
        );
    }

    public function get_tool(): Tool {
        return new Tool(
                'kialo-edu',
                'Kialo Edu',
                $this->tool_url,
                $this->tool_url . "/lti/login",
                $this->tool_url . '/lti/launch',
                $this->tool_url . '/lti/deep-linking'
        );
    }

    public function create_registration(?string $deployment_id = null): Registration {
        $tool = $this->get_tool();
        $platformJwksUrl = (new moodle_url('/mod/kialo/lti_jwks.php'))->out();
        $toolJwksUrl = $this->tool_url . "/lti/jwks.json";
        $deploymentIds = $deployment_id ? [$deployment_id] : [];

        return new Registration(
                'kialo-moodle-registration',
                'kialo-moodle-client',  # TODO: change this to something more unique?
                $this->get_platform(),
                $tool,
                $deploymentIds,
                $this->get_platform_keychain(),
                null,
                $platformJwksUrl,
                $toolJwksUrl,
        );
    }
}
