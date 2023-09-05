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
 * LTI flow tests.
 *
 * @package    mod_kialo
 * @category   test
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

use context_module;
use core_date;
use DateTimeImmutable;
use GuzzleHttp\Psr7\ServerRequest;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Validator;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\KeyConverter;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the LTI flow.
 */
class lti_flow_test extends \advanced_testcase {

    const SIGNER_PLATFORM = 'platform';
    const SIGNER_TOOL = 'tool';
    const EXAMPLE_DEPLOYMENT_ID = '2264e897a263eae4.74875925';

    /**
     * In production the tool's (Kialo's) public key is downloaded from the platform (Moodle) during the LTI flow.
     * For this test we generate a new keypair and override the tool keychain in kialo_config, instead.    *
     *
     * @return void
     */
    public static function setUpBeforeClass(): void {
        parent::setUpBeforeClass();

        kialo_config::get_instance()->toolkeychain = self::generate_tool_keychain();
    }

    /**
     * Generates a random keychain.
     *
     * @return \OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface
     */
    private static function generate_tool_keychain(): KeyChainInterface {
        $config = array(
                "digest_alg" => "sha256",
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
        );
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $toolprivatekeystr);
        $toolpublickeystr = openssl_pkey_get_details(openssl_pkey_get_private($toolprivatekeystr))['key'];

        $toolkeychain = (new KeyChainFactory())->create(
                'example-key-id-1234',                           // Identifier (used for JWT kid header).
                'kialo',                                         // Key set name (for grouping).
                $toolpublickeystr,                               // Public key (file or content).
                $toolprivatekeystr,                              // Private key (file or content).
                '',                                              // Our key has no passphrase.
                KeyInterface::ALG_RS256                          // Algorithm.
        );

        return $toolkeychain;
    }

    protected function setUp(): void {
        parent::setUp();

        $this->backup_globals();
        $this->resetAfterTest();

        $this->user = $this->getDataGenerator()->create_user();
        $this->setUser($this->user);

        $this->course = $this->getDataGenerator()->create_course();

        // Creates a Kialo activity.
        $this->module = $this->getDataGenerator()->create_module('kialo', array('course' => $this->course->id));
        $this->cmid = get_coursemodule_from_instance("kialo", $this->module->id)->id;
    }

    protected function tearDown(): void {
        $this->restore_globals();
        parent::tearDown();
    }

    /**
     * Backs up superglobal variables modified by this test.
     *
     * @return void
     */
    private function backup_globals(): void {
        $this->server = $_SERVER;
        $this->env = $_ENV;
        $this->get = $_GET;
    }

    /**
     * Restores superglobal variables modified by this test.
     *
     * @return void
     */
    private function restore_globals(): void {
        if (null !== $this->server) {
            $_SERVER = $this->server;
        }
        if (null !== $this->env) {
            $_ENV = $this->env;
        }
        if (null !== $this->get) {
            $_GET = $this->get;
        }
    }

    /**
     * Creates a signed JWT.
     *
     * @param string|array $signer self::SIGNER_PLATFORM, self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable|null $callback Callback to add custom claims to the token.
     * @return string Signed JWT.
     * @throws \Exception
     */
    private function create_signed_jwt($signer, ?callable $callback = null): string {
        $deploymentid = self::EXAMPLE_DEPLOYMENT_ID;
        $tokenbuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()))
            ->identifiedBy('4f1g23a12aa') // Random, unique JWT id.
            ->withClaim('https://purl.imsglobal.org/spec/lti/claim/version', "1.3.0")
            ->withClaim("registration_id", "kialo-moodle-registration")
            ->withClaim("https://purl.imsglobal.org/spec/lti/claim/deployment_id", $deploymentid)
            ->withClaim("nonce", "fc5fdc6d-5dd6-47f4-b2c9-5d1216e9b771");

        $now = new DateTimeImmutable("now", new \DateTimeZone("UTC"));
        $tokenbuilder->issuedAt($now);
        $tokenbuilder->expiresAt($now->modify('+1 hour'));

        // JWT needs to be signed by either the tool (Kialo) or the platform (Kialo Moodle plugin).
        $algorithm = new Sha256();
        $converter = new KeyConverter();

        if ($signer == self::SIGNER_PLATFORM) {
            $keychain = kialo_config::get_instance()->get_platform_keychain();
            $signingkey = $converter->convert($keychain->getPrivateKey());
            $tokenbuilder->withHeader("kid", $keychain->getIdentifier());
            $tokenbuilder->issuedBy("https://www.example.com/moodle/mod/kialo");
            $tokenbuilder->permittedFor("https://www.kialo-edu.com"); // The 'aud' header.
        } else if ($signer == self::SIGNER_TOOL) {
            // See https://www.imsglobal.org/spec/security/v1p0/#tool-jwt.
            $keychain = kialo_config::get_instance()->toolkeychain;
            $signingkey = $converter->convert($keychain->getPrivateKey());
            $tokenbuilder->withHeader("kid", $keychain->getIdentifier());
            $tokenbuilder->issuedBy("kialo-moodle-client");
            $tokenbuilder->permittedFor("https://www.example.com/moodle/mod/kialo"); // The 'aud' header.
        } else if (is_array($signer)) {
            // Assume signer is an arbitrary private key.
            $signingkey = $converter->convert(new Key($signer['key'], '', KeyInterface::ALG_RS256));
            $tokenbuilder->withHeader("kid", $signer['kid'] ?? md5($signer['key']));
            $tokenbuilder->issuedBy($signer['iss']);
            $tokenbuilder->permittedFor($signer['aud']); // The 'aud' header.
        } else {
            $this->assertFalse(false, "Invalid signer: " . $signer);
        }

        // Add custom claims.
        if ($callback) {
            $callback($tokenbuilder);
        }

        return $tokenbuilder->getToken($algorithm, $signingkey)->toString();
    }

    /**
     * Asserts that the given string is a valid JWT signed by the plugin (LTI platform).
     *
     * @param $value string
     * @return UnencryptedToken parsed token
     * @throws \dml_exception
     */
    private function assert_jwt_signed_by_platform($value): UnencryptedToken {
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($value);

        $this->assertEquals("JWT", $token->headers()->get('typ'));
        $this->assertEquals("RS256", $token->headers()->get('alg'));

        assert($token instanceof UnencryptedToken);

        $this->assertNotEmpty($token->payload());
        $this->assertNotNull($token->signature());

        // Check signature.
        $validator = new Validator();
        $keychain = kialo_config::get_instance()->get_platform_keychain();
        $signer = new Sha256();
        $key = InMemory::plainText($keychain->getPublicKey()->getContent());
        $this->assertTrue($validator->validate($token, new SignedWith($signer, $key)));

        return $token;
    }

    /**
     * Tests that students get the correct LTI role.
     *
     * @covers \mod_kialo\lti_flow::assign_lti_roles
     */
    public function test_assign_lti_roles_for_student() {
        $this->resetAfterTest(true);

        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");

        $role = lti_flow::assign_lti_roles(context_module::instance($this->cmid));
        $this->assertEquals(["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"], $role);
    }

    /**
     * Tests that teachers get the correct LTI role.
     *
     * @covers \mod_kialo\lti_flow::assign_lti_roles
     */
    public function test_assign_lti_roles_for_teacher() {
        $this->resetAfterTest(true);

        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "editingteacher");

        $role = lti_flow::assign_lti_roles(context_module::instance($this->cmid));
        $this->assertEquals(["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"], $role);
    }

    /**
     * Tests that teaching assistants get the correct LTI role.
     *
     * @covers \mod_kialo\lti_flow::assign_lti_roles
     */
    public function test_assign_lti_roles_for_teaching_assistant() {
        $this->resetAfterTest(true);

        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        $role = lti_flow::assign_lti_roles(context_module::instance($this->cmid));
        $this->assertEquals(["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"], $role);
    }

    /**
     * Tests the initial LTI flow step, when Moodle redirects the user to Kialo.
     *
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_resource_link() {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        // Construct the initial LTI message sent to Kialo when the user clicks on the activity.
        $deploymentid = "random-string-123";
        $message = lti_flow::init_resource_link($this->course->id, $this->cmid, $deploymentid, $this->user->id);
        $this->assertNotNull($message);

        $params = $message->getParameters()->jsonSerialize();
        $this->assertEquals('https://www.example.com/moodle/mod/kialo', $params['iss']);
        $this->assertEquals($this->course->id . "/" . $this->user->id, $params['login_hint']);
        $this->assertEquals(kialo_config::get_instance()->get_tool_url(), $params['target_link_uri']);
        $this->assertEquals($deploymentid, $params['lti_deployment_id']);
        $this->assertEquals(kialo_config::get_instance()->get_client_id(), $params['client_id']);

        // The message hint is a JWT Token that contains the LTI details.
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        $this->assertEquals("JWT", $token->headers()->get('typ'));
        $this->assertEquals("RS256", $token->headers()->get('alg'));
        $this->assertEquals("LtiResourceLinkRequest",
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type"));
        $this->assertEquals($deploymentid, $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
        $this->assertEquals(["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"],
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/roles"));
        $this->assertEquals(kialo_config::get_instance()->get_tool_url(),
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/target_link_uri"));
        $this->assertNotNull($token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/resource_link"));
    }

    /**
     * Tests the 2nd step of the LTI flow, when Kialo redirects the user back to Moodle.
     *
     * @covers \mod_kialo\lti_flow::lti_auth
     */
    public function test_lti_auth(): void {
        global $PAGE, $USER;

        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        // Given a redirect GET request from Kialo with the LTI auth response.
        $_GET['scope'] = 'openid';
        $_GET['nonce'] = 'c380f19c98444ea5907a24d2586c301f8066082a429611eeb7dcceee5bb58708';
        $_GET['response_type'] = 'id_token';
        $_GET['response_mode'] = 'form_post';
        $_GET['client_id'] = kialo_config::get_instance()->get_client_id();
        $_GET['redirect_uri'] = kialo_config::get_instance()->get_tool_url() . '/lti/launch';
        $_GET['state'] = 'state-0eb5b18e-23cf-4a01-81f3-98eae6e46b36';
        $_GET['login_hint'] = $this->course->id . "/" . $this->user->id;

        // LTI Message JWT - created and signed by the Moodle plugin initially and then passed around.
        $tokenstr = $this->create_signed_jwt(self::SIGNER_PLATFORM, function($builder) {
            $builder
                ->withClaim('https://purl.imsglobal.org/spec/lti/claim/message_type', 'LtiResourceLinkRequest')
                ->withClaim("https://purl.imsglobal.org/spec/lti/claim/target_link_uri",
                        kialo_config::get_instance()->get_tool_url())
                ->withClaim("registration_id", "kialo-moodle-registration");
        });
        $this->assertNotEmpty($tokenstr);

        $_GET['lti_message_hint'] = $tokenstr;
        $_SERVER['QUERY_STRING'] = http_build_query($_GET, '', '&');

        // Do the actual LTI auth step, as if the user was just redirected to the plugin by Kialo.
        $message = lti_flow::lti_auth();

        $this->assertNotNull($message);
        $this->assertEquals($_GET['state'], $message->getParameters()->get("state"));
        $this->assertNotEmpty($message->getParameters()->get("id_token"));

        // The id_token is a JWT Token that contains the LTI details of the Moodle user and the deployment id.
        $token = $this->assert_jwt_signed_by_platform($message->getParameters()->get("id_token"));

        $this->assertEquals("LtiResourceLinkRequest",
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type"));
        $this->assertEquals(self::EXAMPLE_DEPLOYMENT_ID,
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
        $this->assertEquals("1.3.0", $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/version"));
        $this->assertEquals(core_date::get_user_timezone_object($this->user)->getName(), $token->claims()->get("zoneinfo"));
        $this->assertEquals($this->user->username, $token->claims()->get("preferred_username"));
        $this->assertEquals($this->user->id, $token->claims()->get("sub"));
        $this->assertEquals($this->user->firstname . " " . $this->user->lastname, $token->claims()->get("name"));
        $this->assertEquals($this->user->email, $token->claims()->get("email"));
        $this->assertEquals($this->user->lang, $token->claims()->get("locale"));
        $expectedpicture = new \user_picture($USER);
        $expectedpicture->size = 128;
        $this->assertEquals($expectedpicture->get_url($PAGE), $token->claims()->get("picture"));
    }

    /**
     * Tests the initial deep linking request (Moodle -> Kialo).
     *
     * @covers \mod_kialo\lti_flow::init_deep_link
     */
    public function test_deep_link() {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        $deploymentid = "random-string-123";
        $message = lti_flow::init_deep_link($this->course->id, $this->user->id, $deploymentid);
        $this->assertNotNull($message);

        $params = $message->getParameters()->jsonSerialize();
        $this->assertEquals('https://www.example.com/moodle/mod/kialo', $params['iss']);
        $this->assertEquals($this->course->id . "/" . $this->user->id, $params['login_hint']);
        $this->assertEquals(kialo_config::get_instance()->get_tool_url() . '/lti/deeplink', $params['target_link_uri']);
        $this->assertEquals($deploymentid, $params['lti_deployment_id']);
        $this->assertEquals(kialo_config::get_instance()->get_client_id(), $params['client_id']);

        // The message hint is a JWT Token that contains the LTI details.
        $this->assertNotEmpty($params['lti_message_hint']);
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        $this->assertEquals("LtiDeepLinkingRequest",
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type"));
        $this->assertEquals($deploymentid, $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
        $this->assertEquals(["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"],
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/roles"));
        $this->assertEquals(kialo_config::get_instance()->get_tool_url() . '/lti/deeplink',
                $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/target_link_uri"));

        $settings = $token->claims()->get("https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings");
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_select.php", $settings["deep_link_return_url"]);
        $this->assertEquals(["ltiResourceLink"], $settings["accept_types"]);
        $this->assertEquals(["window"], $settings["accept_presentation_document_targets"]);
        $this->assertEquals(false, $settings["accept_multiple"]);
        $this->assertEquals(false, $settings["auto_create"]);

        // The LTI library used on Kialo's side requires this to be a JWT with no particular content, but signed by the plugin.
        $this->assert_jwt_signed_by_platform($settings['data']);
    }

    /**
     * Creates a deep link response JWT and sets the current request's query string to contain it.
     *
     * @param $signer string self::SIGNER_PLATFORM or self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param $callable callable|null Callback to modify the token.
     * @return void
     * @throws \Exception
     */
    private function prepare_deep_link_response($signer, ?callable $callable = null) {
        $_GET['JWT'] = $this->create_signed_jwt($signer, function(Builder $builder) use ($callable) {
            $builder->withClaim('https://purl.imsglobal.org/spec/lti/claim/message_type', 'LtiDeepLinkingResponse');
            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                    [
                            "type" => "ltiResourceLink",
                            "title" => "Selected Kialo Discussion",
                            "url" => "https://www.kialo-edu.com/discussion-title-1234",
                            "custom" => [],
                    ]
            ]);

            // The "data" claim needs to be a JWT signed by the platform, content doesn't matter.
            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/data",
                    $this->create_signed_jwt(self::SIGNER_PLATFORM));

            if ($callable) {
                $callable($builder);
            }
        });
        $_SERVER['QUERY_STRING'] = http_build_query($_GET, '', '&');
    }

    /**
     * Tests the deep linking response validation.
     *
     * This is validating the request where the user is redirected back to Moodle after they selected a discussion
     * on Kialo.
     *
     * @return void
     * @covers \mod_kialo\lti_flow::validate_deep_linking_response
     */
    public function test_validate_deep_link_response() {
        // Given a redirect GET request from Kialo with the deep linking response.
        $this->prepare_deep_link_response(self::SIGNER_TOOL);

        // The response should be validated successfully.
        $result = lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals(), self::EXAMPLE_DEPLOYMENT_ID);

        // And the selected discussion details should be returned.
        $this->assertEquals(self::EXAMPLE_DEPLOYMENT_ID, $result->deploymentid);
        $this->assertEquals("Selected Kialo Discussion", $result->discussiontitle);
        $this->assertEquals("https://www.kialo-edu.com/discussion-title-1234", $result->discussionurl);
    }

    public static function provide_invalid_deeplink_builders() {
        $maliciouskeychain = self::generate_tool_keychain();
        $platformkeychain = kialo_config::get_instance()->get_platform_keychain();
        $normalclientid = kialo_config::get_instance()->get_client_id();

        return [
                "invalid issuer" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->issuedBy("invalid-issuer");
                        },
                        new LtiException("No matching registration found platform side"),
                ],
                "missing issuer" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->issuedBy('');
                        },
                        new LtiException("No matching registration found platform side")
                ],
                // Not just anybody can sign the JWT.
                "invalid signature" => [
                        [
                                "key" => $maliciouskeychain->getPrivateKey()->getContent(),
                                "iss" => $normalclientid,
                                "aud" => "https://www.example.com/moodle/mod/kialo",
                                "kid" => $platformkeychain->getIdentifier()],
                        null,
                        new LtiException("JWT validation failure")
                ],
                "missing nonce" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->withClaim("nonce", "");
                        },
                        new LtiException("JWT nonce claim is missing")
                ],
                "missing content items" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", []);
                        },
                        new LtiException("Expected exactly one content item")
                ],
                "wrong content type" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                                    [
                                            "type" => "link",
                                            "url" => "https://www.kialo-edu.com/discussion-title-1234",
                                    ]
                            ]);
                        },
                        new LtiException("Expected content item to be of type ltiResourceLink")
                ],
                "missing content item URL" => [
                        self::SIGNER_TOOL,
                        function(Builder $builder) {
                            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                                    [
                                            "type" => "ltiResourceLink",
                                            "title" => "Selected Kialo Discussion",
                                    ]
                            ]);
                        },
                        new LtiException("Expected content item to have a url")
                ],
        ];
    }

    /**
     * Tests invalid deep link responses.
     *
     * @param $signer string|array self::SIGNER_PLATFORM, self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable $builder Callback to modify the token (receives a JWT Builder).
     * @param $exception \Exception expected exception
     * @return void
     * @throws LtiException
     * @throws \dml_exception
     * @dataProvider provide_invalid_deeplink_builders
     * @covers       \mod_kialo\lti_flow::validate_deep_linking_response
     */
    public function test_invalid_deep_link_response($signer, ?callable $builder, $exception) {
        // Given an invalid redirect GET request from Kialo with the deep linking response.
        $this->prepare_deep_link_response($signer, $builder);

        // The validation should fail.
        $this->expectExceptionObject($exception);
        lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals(), self::EXAMPLE_DEPLOYMENT_ID);
    }
}
