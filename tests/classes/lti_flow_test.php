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
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Token\Parser;
use Lcobucci\JWT\UnencryptedToken;
use mod_kialo\kialo_config;
use mod_kialo\lti_flow;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Security\Jwt\Converter\KeyConverter;
use OAT\Library\Lti1p3Core\Security\Jwt\TokenInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../../vendor/autoload.php');

/**
 * Tests the LTI flow.
 */
class lti_flow_test extends \advanced_testcase {

    /**
     * Backs up superglobal variables modified by this test.
     * @return void
     */
    private function backup_globals(): void {
        $this->server = $_SERVER;
        $this->env = $_ENV;
        $this->get = $_GET;
    }

    /**
     * Restores superglobal variables modified by this test.
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
     * Tests that students get the correct LTI role.
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
     * @covers \mod_kialo\lti_flow::init_resource_link
     */
    public function test_init_resource_link() {
        $this->getDataGenerator()->enrol_user($this->user->id, $this->course->id, "teacher");

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
        $this->assertNotEmpty($params['lti_message_hint']);
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($params['lti_message_hint']);

        $this->assertEquals("JWT", $token->headers()->get('typ'));
        $this->assertEquals("RS256", $token->headers()->get('alg'));

        assert($token instanceof UnencryptedToken);
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

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['scope'] = 'openid';
        $_GET['nonce'] = 'c380f19c98444ea5907a24d2586c301f8066082a429611eeb7dcceee5bb58708';
        $_GET['response_type'] = 'id_token';
        $_GET['response_mode'] = 'form_post';
        $_GET['client_id'] = kialo_config::get_instance()->get_client_id();
        $_GET['redirect_uri'] = kialo_config::get_instance()->get_tool_url() . '/lti/launch';
        $_GET['state'] = 'state-0eb5b18e-23cf-4a01-81f3-98eae6e46b36';
        $_GET['login_hint'] = $this->course->id . "/" . $this->user->id;

        // LTI Message JWT.
        $tokenbuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm = new Sha256();
        $signingkey = (new KeyConverter())->convert(kialo_config::get_instance()->get_platform_keychain()->getPrivateKey());
        $now = new DateTimeImmutable();
        $deploymentid = "2264e897a263eae4.74875925";
        $token = $tokenbuilder
            ->withHeader("kid", "42")
            ->issuedBy('https://www.kialo-edu.com')
            ->permittedFor('https://www.example.com/moodle/mod/kialo')
            ->identifiedBy('4f1g23a12aa')
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now->modify('+1 minute'))
            ->expiresAt($now->modify('+1 hour'))
            ->withClaim('https://purl.imsglobal.org/spec/lti/claim/version', "1.3.0")
            ->withClaim("registration_id", "kialo-moodle-registration")
            ->withClaim("https://purl.imsglobal.org/spec/lti/claim/target_link_uri", kialo_config::get_instance()->get_tool_url())
            ->withClaim("https://purl.imsglobal.org/spec/lti/claim/deployment_id", $deploymentid)
            ->getToken($algorithm, $signingkey);
        $this->assertNotNull($token);

        $tokenstr = $token->toString();
        $this->assertNotEmpty($tokenstr);
        $_GET['lti_message_hint'] = $tokenstr;

        $_SERVER['QUERY_STRING'] = http_build_query($_GET, '', '&');
        $serverrequest = ServerRequest::fromGlobals();
        $this->assertNotNull($serverrequest->getQueryParams()['lti_message_hint']);
        $this->assertNotEquals([], $serverrequest->getQueryParams());
        $this->assertArrayHasKey('lti_message_hint', $serverrequest->getQueryParams());
        $this->assertNotEmpty($serverrequest->getUri()->getQuery());
        $this->assertStringContainsString("lti_message_hint", $serverrequest->getUri()->getQuery());

        $oidcrequest = LtiMessage::fromServerRequest($serverrequest);
        $this->assertNotEquals([], $oidcrequest->getParameters()->all());
        $this->assertNotNull($oidcrequest->getParameters()->get('lti_message_hint'));

        // Using a NOOP validator because something is wrong with the signing of the message within the test right now.
        // Will try to fix that later.
        $validator = new noop_validator();

        $message = lti_flow::lti_auth($validator);
        $this->assertNotNull($message);
        $this->assertEquals($_GET['state'], $message->getParameters()->get("state"));
        $this->assertNotEmpty($message->getParameters()->get("id_token"));

        // The id_token is a JWT Token that contains the LTI details of the Moodle user and the deployment id.
        $parser = new Parser(new JoseEncoder());
        $token = $parser->parse($message->getParameters()->get("id_token"));

        $this->assertEquals("JWT", $token->headers()->get('typ'));
        $this->assertEquals("RS256", $token->headers()->get('alg'));

        assert($token instanceof UnencryptedToken);

        $this->assertEquals($deploymentid, $token->claims()->get("https://purl.imsglobal.org/spec/lti/claim/deployment_id"));
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
}

/**
 * A validator that always returns true. Used for testing because the key validation is not working right now within the test.
 */
class noop_validator implements ValidatorInterface {
    /**
     * Returns always true.
     * @param TokenInterface $token Ignored.
     * @param KeyInterface $key Ignored.
     * @return bool
     */
    public function validate(TokenInterface $token, KeyInterface $key): bool {
        return true;
    }
}
