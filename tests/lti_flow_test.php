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
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\KeyConverter;
use OAT\Library\Lti1p3Core\Security\Key\Key;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../vendor/autoload.php');

/**
 * Tests the LTI flow.
 */
final class lti_flow_test extends \advanced_testcase {
    /**
     * Message is signed by the platform (the Kialo Moodle plugin).
     */
    public const SIGNER_PLATFORM = 'platform';

    /**
     * Message is signed by the tool (Kialo Edu).
     */
    public const SIGNER_TOOL = 'tool';

    /**
     * Some random deployment id used for the tests.
     */
    public const EXAMPLE_DEPLOYMENT_ID = '2264e897a263eae4.74875925';

    /**
     * Current user (created and logged in in setUp).
     *
     * @var \stdClass
     */
    private $user;

    /**
     * Current course (created in setUp).
     *
     * @var \stdClass
     */
    private $course;

    /**
     * Current course module (created in setUp).
     *
     * @var \stdClass
     */
    private $cm;

    /**
     * Current module (created in setUp).
     *
     * @var \stdClass
     */
    private $module;

    /**
     * Current course module id (created in setUp).
     *
     * @var int
     */
    private $cmid;

    /**
     * Copy of $_SERVER superglobal before the test.
     * @var array|null
     */
    private $server;

    /**
     * Copy of $_ENV superglobal before the test.
     * @var array|null
     */
    private $env;

    /**
     * Copy of $_GET superglobal before the test.
     * @var array|null
     */
    private $get;

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
        $config = [
            "digest_alg" => "sha256",
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA,
        ];
        $res = openssl_pkey_new($config);
        openssl_pkey_export($res, $toolprivatekeystr);
        $toolpublickeystr = openssl_pkey_get_details(openssl_pkey_get_private($toolprivatekeystr))['key'];

        $toolkeychain = (new KeyChainFactory())->create(
            'example-key-id-1234', // Identifier (used for JWT kid header).
            'kialo', // Key set name (for grouping).
            $toolpublickeystr, // Public key (file or content).
            $toolprivatekeystr, // Private key (file or content).
            '', // Our key has no passphrase.
            KeyInterface::ALG_RS256                          // Algorithm.
        );

        return $toolkeychain;
    }

    protected function setUp(): void {
        parent::setUp();

        $this->backup_globals();
        $this->resetAfterTest();

        $this->user = $this->getDataGenerator()->create_user(["picture" => 42]);
        $this->setUser($this->user);

        $this->course = $this->getDataGenerator()->create_course();

        // Creates a Kialo activity.
        $this->module = $this->getDataGenerator()->create_module('kialo', ['course' => $this->course->id]);
        $this->cm = get_coursemodule_from_instance("kialo", $this->module->id);
        $this->cmid = $this->cm->id;
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
     * @param string $value a JWT
     * @return UnencryptedToken parsed token
     */
    private function assert_jwt_signed_by_platform(string $value): UnencryptedToken {
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
     * Provides test scenarios for the role assignment test.
     *
     * @return \array[][] lists of Moodle roles and expected LTI roles.
     */
    public static function provide_lti_role_assertions(): array {
        return [
            "student" => [["student"], ["http://purl.imsglobal.org/vocab/lis/v2/membership#Learner"]],
            "teacher" => [["teacher"], ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"]],
            "editingteacher" => [["editingteacher"], ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"]],
            "student and teacher at the same time" => [
                ["editingteacher", "student"], ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"],
            ],
            "manager" => [["manager"], ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"]],
            "not enrolled" => [[], []],
        ];
    }

    /**
     * Tests that Moodle roles are correctly mapped to LTI roles.
     *
     * @param array $moodleroles List of Moodle roles the user has in the course.
     * @param array $expectedltiroles List of expected LTI roles the user should be assigned.
     * @return void
     * @throws \coding_exception
     * @covers       \mod_kialo\lti_flow::assign_lti_roles
     * @dataProvider provide_lti_role_assertions
     */
    public function test_assign_lti_roles(array $moodleroles, array $expectedltiroles): void {
        $this->resetAfterTest(true);

        foreach ($moodleroles as $moodlerole) {
            $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, $moodlerole);
        }

        $role = lti_flow::assign_lti_roles(context_module::instance($this->cmid));

        $this->assertEquals($expectedltiroles, $role);
    }

    /**
     * Tests the platform configuration.
     *
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_non_embedded_resource_link(): void {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        // Construct the initial LTI message sent to Kialo when the user clicks on the activity.
        $deploymentid = "random-string-123";
        $discussionurl = "random-discussion-url.com";
        $message = lti_flow::init_resource_link(
            $this->course->id,
            $this->cmid,
            $deploymentid,
            $this->user->id,
            $discussionurl,
        );

        $this->assertStringContainsString('/lti/start', $message->getUrl());
    }

    /**
     * Tests the initial LTI flow step, when Moodle redirects the user to Kialo.
     *
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_resource_link(): void {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        // Construct the initial LTI message sent to Kialo when the user clicks on the activity.
        $deploymentid = "random-string-123";
        $discussionurl = "random-discussion-url.com";
        $message = lti_flow::init_resource_link(
            $this->course->id,
            $this->cmid,
            $deploymentid,
            $this->user->id,
            $discussionurl,
        );
        $this->assertNotNull($message);

        $params = $message->getParameters()->jsonSerialize();

        $this->assertStringContainsString('/lti/start', $message->getUrl());

        $this->assertEquals('https://www.example.com/moodle/mod/kialo', $params['iss']);
        $this->assertEquals($this->course->id . "/" . $this->user->id, $params['login_hint']);
        $this->assertEquals($discussionurl, $params['target_link_uri']);
        $this->assertEquals($deploymentid, $params['lti_deployment_id']);
        $this->assertEquals(kialo_config::get_instance()->get_client_id(), $params['client_id']);

        // The message hint is a JWT Token that contains the LTI details.
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        $this->assertEquals("JWT", $token->headers()->get('typ'));
        $this->assertEquals("RS256", $token->headers()->get('alg'));
        $this->assertEquals(
            "LtiResourceLinkRequest",
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type")
        );
        $this->assertEquals($deploymentid, $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
        $this->assertEquals(
            ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"],
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/roles")
        );
        $this->assertEquals(
            $discussionurl,
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/target_link_uri")
        );
        $this->assertNotNull($token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/resource_link"));
        $this->assertEquals(kialo_config::get_release(), $params["kialo_plugin_version"]);

        $this->assertEquals($token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/context")["id"], $this->course->id);

        // By default there are no custom claims for groups.
        $this->assertNull($token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/custom"));
    }

    /**
     * Tests the initial LTI flow step with groups.
     *
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_resource_link_with_groups(): void {
        // Given one student in a group.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");
        $group = $this->getDataGenerator()->create_group([
            'courseid' => $this->course->id,
            'name' => 'Group 1',
        ]);
        $this->getDataGenerator()->create_group_member([
            'groupid' => $group->id,
            'userid' => $this->user->id,
        ]);

        $groupinfo = (object) [
            'groupid' => $group->id,
            'groupname' => $group->name,
        ];

        // Construct the initial LTI message sent to Kialo when the user clicks on the activity.
        $deploymentid = "random-string-123";
        $discussionurl = "random-discussion-url.com";
        $message = lti_flow::init_resource_link(
            $this->course->id,
            $this->cmid,
            $deploymentid,
            $this->user->id,
            $discussionurl,
            $groupinfo->groupid,
            $groupinfo->groupname
        );
        $this->assertNotNull($message);

        $params = $message->getParameters()->jsonSerialize();

        // The message hint is a JWT Token that contains the LTI details.
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        // Group information is passed via custom claims.
        $this->assertEquals([
            "kialoGroupId" => $group->id,
            "kialoGroupName" => $group->name,
        ], $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/custom"));
    }

    /**
     * Tests the initial LTI flow step with groups.
     *
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_resource_link_with_grouping(): void {
        // Given one student in a group.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");
        $group = $this->getDataGenerator()->create_group([
            'courseid' => $this->course->id,
            'name' => 'Group 1',
        ]);
        $grouping = $this->getDataGenerator()->create_grouping([
            'courseid' => $this->course->id,
            'name' => 'Grouping 1',
        ]);
        $this->getDataGenerator()->create_grouping_group([
            'groupingid' => $grouping->id,
            'groupid' => $group->id,
        ]);
        $this->getDataGenerator()->create_group_member([
            'groupid' => $group->id,
            'userid' => $this->user->id,
        ]);

        $groupinfo = (object) [
            'groupid' => "grouping-{$grouping->id}",
            'groupname' => $grouping->name,
        ];

        // Construct the initial LTI message sent to Kialo when the user clicks on the activity.
        $deploymentid = "random-string-123";
        $discussionurl = "random-discussion-url.com";
        $message = lti_flow::init_resource_link(
            $this->course->id,
            $this->cmid,
            $deploymentid,
            $this->user->id,
            $discussionurl,
            $groupinfo->groupid,
            $groupinfo->groupname
        );
        $this->assertNotNull($message);

        $params = $message->getParameters()->jsonSerialize();

        // The message hint is a JWT Token that contains the LTI details.
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        // Group information is passed via custom claims.
        $this->assertEquals([
            "kialoGroupId" => "grouping-{$grouping->id}",
            "kialoGroupName" => $grouping->name,
        ], $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/custom"));
    }

    /**
     * Prepares a standard LTI auth request as would be received by the plugin from Kialo in the LTI flow.
     *
     * @param string|array $signer self::SIGNER_PLATFORM, self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable|null $callback Callback to add custom claims to the token or modify other $_GET params.
     * @return void
     * @throws \Exception
     */
    private function prepare_lti_auth_request($signer = self::SIGNER_PLATFORM, ?callable $callback = null) {
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
        $tokenstr = $this->create_signed_jwt($signer, function ($builder) use ($callback) {
            $builder
                ->withClaim('https://purl.imsglobal.org/spec/lti/claim/message_type', 'LtiResourceLinkRequest')
                ->withClaim(
                    "https://purl.imsglobal.org/spec/lti/claim/target_link_uri",
                    kialo_config::get_instance()->get_tool_url()
                )
                ->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT, [
                    "id" => $this->course->id,
                ])
                ->withClaim("registration_id", "kialo-moodle-registration");

            if ($callback) {
                $callback($builder);
            }
        });
        $this->assertNotEmpty($tokenstr);

        $_GET['lti_message_hint'] = $tokenstr;
        $_SERVER['QUERY_STRING'] = http_build_query($_GET, '', '&');
    }

    /**
     * Tests the 2nd step of the LTI flow, when Kialo redirects the user back to Moodle.
     *
     * @covers \mod_kialo\lti_flow::lti_auth
     */
    public function test_lti_auth(): void {
        global $CFG;

        // The current user must be at least a student in the course. But this LTI step works the same for students and teachers.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");

        // Given a redirect GET request from Kialo with the LTI auth response.
        $this->prepare_lti_auth_request(self::SIGNER_PLATFORM, function (Builder $builder) {
            $builder->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK, [
                "id" => lti_flow::resource_link_id($this->cmid),
            ]);
        });

        // Do the actual LTI auth step, as if the user was just redirected to the plugin by Kialo.
        $message = lti_flow::lti_auth();

        $this->assertNotNull($message);
        $this->assertEquals($_GET['state'], $message->getParameters()->get("state"));
        $this->assertNotEmpty($message->getParameters()->get("id_token"));

        // The id_token is a JWT Token that contains the LTI details of the Moodle user and the deployment id.
        $token = $this->assert_jwt_signed_by_platform($message->getParameters()->get("id_token"));

        $this->assertEquals(
            "LtiResourceLinkRequest",
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type")
        );
        $this->assertEquals(
            self::EXAMPLE_DEPLOYMENT_ID,
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id")
        );
        $this->assertEquals("1.3.0", $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/version"));
        $this->assertEquals(core_date::get_user_timezone_object($this->user)->getName(), $token->claims()->get("zoneinfo"));
        $this->assertEquals($this->user->username, $token->claims()->get("preferred_username"));
        $this->assertEquals($this->user->id, $token->claims()->get("sub"));
        $this->assertEquals($this->user->firstname . " " . $this->user->lastname, $token->claims()->get("name"));
        $this->assertEquals($this->user->email, $token->claims()->get("email"));
        $this->assertEquals($this->user->lang, $token->claims()->get("locale"));

        $this->assertEquals(kialo_config::get_release(), $token->claims()->get("kialo_plugin_version"));

        $this->assertEquals([
            "guid" => lti_flow::PLATFORM_GUID,
            "product_family_code" => lti_flow::PRODUCT_FAMILY_CODE,
            "version" => $CFG->version,
        ], $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/tool_platform"));

        global $PAGE, $USER;
        $expectedpicture = new \user_picture($USER);
        $expectedpicture->size = 128;
        $this->assertEquals($expectedpicture->get_url($PAGE), $token->claims()->get("picture"));
    }

    /**
     * The LTI authentication response should contain information about the AGS (Assignment and Grading) service endpoints.
     *
     * @covers \mod_kialo\lti_flow::lti_auth
     */
    public function test_lti_auth_response_contains_grading_service(): void {
        // The current user must be at least a student in the course. But this LTI step works the same for students and teachers.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");

        // Given a redirect GET request from Kialo with the LTI auth response.
        $this->prepare_lti_auth_request(self::SIGNER_PLATFORM, function (Builder $builder) {
            $builder->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK, [
                "id" => lti_flow::resource_link_id($this->cmid),
            ]);
        });

        // The response message should contain information about the Assignment and Grading service endpoints of the plugin.
        $message = lti_flow::lti_auth();
        $token = $this->assert_jwt_signed_by_platform($message->getParameters()->get("id_token"));
        $claim = $token->claims()->get(LtiMessagePayloadInterface::CLAIM_LTI_AGS);

        $this->assertEquals(MOD_KIALO_LTI_AGS_SCOPES, $claim["scope"]);

        $courseid = $this->course->id;
        $cmid = $this->cmid;
        $resourcelinkid = lti_flow::resource_link_id($cmid);

        $this->assertEquals(
            "https://www.example.com/moodle" .
            "/mod/kialo/lti_lineitem.php?course_id={$courseid}&resource_link_id={$resourcelinkid}&cmid={$cmid}",
            $claim["lineitem"]
        );

        // Unused, but included for potential future use.
        $this->assertEquals(
            "https://www.example.com/moodle" .
            "/mod/kialo/lti_lineitems.php?course_id={$courseid}&resource_link_id={$resourcelinkid}&cmid={$cmid}",
            $claim["lineitems"]
        );
    }

    /**
     * The LTI authentication response should contain information about update_discussion_url endpoint.
     *
     * @covers \mod_kialo\lti_flow::lti_auth
     */
    public function test_lti_auth_response_contains_update_discussion_url_endpoint(): void {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");

        $this->prepare_lti_auth_request(self::SIGNER_PLATFORM, function (Builder $builder) {
            $builder->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK, [
                "id" => lti_flow::resource_link_id($this->cmid),
            ]);
        });

        // The response message should contain information about the discussion url update endpoints of the plugin.
        $message = lti_flow::lti_auth();
        $token = $this->assert_jwt_signed_by_platform($message->getParameters()->get("id_token"));
        $claim = $token->claims()->get(MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_ENDPOINT_CLAIM);

        $this->assertEquals([MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_SCOPE], $claim["scope"]);
        $this->assertEquals(
            "https://www.example.com/moodle" .
            "/mod/kialo/update_discussion_url.php?cmid={$this->cmid}",
            $claim["update_discussion_url"]
        );
    }

    /**
     * Provides test scenarios of invalid LTI auth requests.
     *
     * @return array[] lists of invalid LTI auth requests.
     * @throws \dml_exception
     */
    public static function provide_invalid_lti_auth_builders(): array {
        $maliciouskeychain = self::generate_tool_keychain();
        $platformkeychain = kialo_config::get_instance()->get_platform_keychain();
        $normalclientid = kialo_config::get_instance()->get_client_id();

        return [
            // Not just anybody can sign the JWT.
            "invalid signature" => [
                [
                    "key" => $maliciouskeychain->getPrivateKey()->getContent(),
                    "iss" => $normalclientid,
                    "aud" => "https://www.example.com/moodle/mod/kialo",
                    "kid" => $platformkeychain->getIdentifier(),
                ],
                null,
                new LtiException("Invalid message hint"),
            ],
            "wrong Moodle user" => [
                self::SIGNER_PLATFORM,
                function (Builder $builder) {
                    $othercourseid = "42";
                    $invaliduserid = "9999";
                    $_GET["login_hint"] = "$othercourseid/$invaliduserid";
                },
                "/^OIDC authentication failed.*/",
            ],
            "missing state parameter" => [
                self::SIGNER_PLATFORM,
                function (Builder $builder) {
                    unset($_GET['state']);
                },
                new LtiException("OIDC authentication failed: Missing mandatory state"),
            ],
            "missing login_hint" => [
                self::SIGNER_PLATFORM,
                function (Builder $builder) {
                    unset($_GET['login_hint']);
                },
                new LtiException("OIDC authentication failed: Missing mandatory login_hint"),
            ],
            "wrong registration id" => [
                self::SIGNER_PLATFORM,
                function (Builder $builder) {
                    $builder->withClaim("registration_id", "wrong-registration");
                },
                new LtiException("Invalid message hint registration id claim"),
            ],
        ];
    }

    /**
     * Tests invalid LTI auth requests.
     *
     * @param mixed $signer self::SIGNER_PLATFORM, self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable|null $builder Callback to modify the token (receives a JWT Builder).
     * @param \Exception $exception expected exception
     * @return void
     * @throws LtiException
     * @throws \dml_exception
     * @dataProvider provide_invalid_lti_auth_builders
     * @covers       \mod_kialo\lti_flow::validate_deep_linking_response
     */
    public function test_invalid_lti_auth($signer, ?callable $builder, $exception): void {
        // The current user must be at least a student in the course. But this LTI step works the same for students and teachers.
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "student");

        // Given a redirect GET request from Kialo with the LTI auth response.
        $this->prepare_lti_auth_request($signer, $builder);

        if (is_string($exception)) {
            $this->expectExceptionMessageMatches($exception);
        } else if ($exception) {
            $this->expectExceptionObject($exception);
        }

        // Do the actual LTI auth step, as if the user was just redirected to the plugin by Kialo.
        lti_flow::lti_auth();
    }

    /**
     * Tests the initial deep linking request (Moodle -> Kialo).
     *
     * @covers \mod_kialo\lti_flow::init_deep_link
     */
    public function test_init_deep_link(): void {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

        $deploymentid = "random-string-123";
        $message = lti_flow::init_deep_link($this->course->id, $this->user->id, $deploymentid);
        $this->assertNotNull($message);

        $this->assertStringContainsString('/lti/start', $message->getUrl());

        $params = $message->getParameters()->jsonSerialize();
        $this->assertEquals('https://www.example.com/moodle/mod/kialo', $params['iss']);
        $this->assertEquals($this->course->id . "/" . $this->user->id, $params['login_hint']);
        $this->assertEquals(kialo_config::get_instance()->get_tool_url() . '/lti/deeplink', $params['target_link_uri']);
        $this->assertEquals($deploymentid, $params['lti_deployment_id']);
        $this->assertEquals(kialo_config::get_instance()->get_client_id(), $params['client_id']);

        // The message hint is a JWT Token that contains the LTI details.
        $this->assertNotEmpty($params['lti_message_hint']);
        $token = $this->assert_jwt_signed_by_platform($params['lti_message_hint']);

        $this->assertEquals(
            "LtiDeepLinkingRequest",
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/message_type")
        );
        $this->assertEquals($deploymentid, $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
        $this->assertEquals(
            ["http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor"],
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/roles")
        );
        $this->assertEquals(
            kialo_config::get_instance()->get_tool_url() . '/lti/deeplink',
            $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/target_link_uri")
        );
        $this->assertEquals(kialo_config::get_release(), $params["kialo_plugin_version"]);

        $settings = $token->claims()->get("https://purl.imsglobal.org/spec/lti-dl/claim/deep_linking_settings");
        $this->assertEquals("https://www.example.com/moodle/mod/kialo/lti_select.php", $settings["deep_link_return_url"]);
        $this->assertEquals(["ltiResourceLink"], $settings["accept_types"]);
        $this->assertEquals(["window"], $settings["accept_presentation_document_targets"]);
        $this->assertEquals(false, $settings["accept_multiple"]);
        $this->assertEquals(false, $settings["auto_create"]);

        // The LTI library used on Kialo's side requires this to be a JWT with no particular content, but signed by the plugin.
        $this->assert_jwt_signed_by_platform($settings['data']);

        $this->assertEquals($token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/context")["id"], $this->course->id);
    }

    /**
     * Creates a deep link response JWT and sets the current request's query string to contain it.
     *
     * @param string $signer self::SIGNER_PLATFORM or self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable|null $callable Callback to modify the token.
     * @return void
     * @throws \Exception
     */
    private function prepare_deep_link_response_request($signer, ?callable $callable = null) {
        $_GET['JWT'] = $this->create_signed_jwt($signer, function (Builder $builder) use ($callable) {
            $builder->withClaim('https://purl.imsglobal.org/spec/lti/claim/message_type', 'LtiDeepLinkingResponse');
            $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                [
                    "type" => "ltiResourceLink",
                    "title" => "Selected Kialo Discussion",
                    "url" => "https://www.kialo-edu.com/discussion-title-1234",
                    "custom" => [],
                ],
            ]);

            // The "data" claim needs to be a JWT signed by the platform, content doesn't matter.
            $builder->withClaim(
                "https://purl.imsglobal.org/spec/lti-dl/claim/data",
                $this->create_signed_jwt(self::SIGNER_PLATFORM)
            );

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
    public function test_validate_deep_link_response(): void {
        // Given a redirect GET request from Kialo with the deep linking response.
        $this->prepare_deep_link_response_request(self::SIGNER_TOOL);

        // The response should be validated successfully.
        $result = lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals(), self::EXAMPLE_DEPLOYMENT_ID);

        // And the selected discussion details should be returned.
        $this->assertEquals(self::EXAMPLE_DEPLOYMENT_ID, $result->deploymentid);
        $this->assertEquals("Selected Kialo Discussion", $result->discussiontitle);
        $this->assertEquals("https://www.kialo-edu.com/discussion-title-1234", $result->discussionurl);
    }

    /**
     * Provides test scenarios of invalid deep link response request.
     *
     * @return array[]
     * @throws \dml_exception
     */
    public static function provide_invalid_deeplink_builders(): array {
        $maliciouskeychain = self::generate_tool_keychain();
        $platformkeychain = kialo_config::get_instance()->get_platform_keychain();
        $normalclientid = kialo_config::get_instance()->get_client_id();

        return [
            "invalid issuer" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->issuedBy("invalid-issuer");
                },
                new LtiException("No matching registration found platform side"),
            ],
            "missing issuer" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->issuedBy('');
                },
                new LtiException("No matching registration found platform side"),
            ],
            // Not just anybody can sign the JWT.
            "invalid signature" => [
                [
                    "key" => $maliciouskeychain->getPrivateKey()->getContent(),
                    "iss" => $normalclientid,
                    "aud" => "https://www.example.com/moodle/mod/kialo",
                    "kid" => $platformkeychain->getIdentifier(),
                ],
                null,
                new LtiException("JWT validation failure"),
            ],
            "missing nonce" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->withClaim("nonce", "");
                },
                new LtiException("JWT nonce claim is missing"),
            ],
            "missing content items" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", []);
                },
                new LtiException("Expected exactly one content item"),
            ],
            "wrong content type" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                        [
                            "type" => "link",
                            "url" => "https://www.kialo-edu.com/discussion-title-1234",
                        ],
                    ]);
                },
                new LtiException("Expected content item to be of type ltiResourceLink"),
            ],
            "missing content item URL" => [
                self::SIGNER_TOOL,
                function (Builder $builder) {
                    $builder->withClaim("https://purl.imsglobal.org/spec/lti-dl/claim/content_items", [
                        [
                            "type" => "ltiResourceLink",
                            "title" => "Selected Kialo Discussion",
                        ],
                    ]);
                },
                new LtiException("Expected content item to have a url"),
            ],
        ];
    }

    /**
     * Tests invalid deep link responses.
     *
     * @param mixed $signer self::SIGNER_PLATFORM, self::SIGNER_TOOL, or ['iss' => string, 'aud' => string, 'key' => string].
     * @param callable|null $builder Callback to modify the token (receives a JWT Builder).
     * @param \Exception $exception expected exception
     * @return void
     * @throws LtiException
     * @throws \dml_exception
     * @dataProvider provide_invalid_deeplink_builders
     * @covers       \mod_kialo\lti_flow::validate_deep_linking_response
     */
    public function test_invalid_deep_link_response($signer, ?callable $builder, $exception): void {
        // Given an invalid redirect GET request from Kialo with the deep linking response.
        $this->prepare_deep_link_response_request($signer, $builder);

        // The validation should fail.
        $this->expectExceptionObject($exception);
        lti_flow::validate_deep_linking_response(ServerRequest::fromGlobals(), self::EXAMPLE_DEPLOYMENT_ID);
    }
}
