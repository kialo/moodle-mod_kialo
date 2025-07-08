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
 * Methods that implement LTI standard flows.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../constants.php');

use context_module;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use moodle_url;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessage;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use OAT\Library\Lti1p3Core\Security\Jwks\Fetcher\JwksFetcher;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder as JwtBuilder;
use OAT\Library\Lti1p3Core\Security\Jwt\Parser\Parser;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Security\OAuth2\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Validator\RequestAccessTokenValidator;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Functions implementing the LTI steps.
 */
class lti_flow {

    /**
     * The LTI standard requires a stable GUID to be send with the platform information.
     * See https://www.imsglobal.org/spec/lti/v1p3#platform-instance-claim.
     * We send a value uniqely identifying the Kialo plugin, but it's the same for all instances.
     */
    public const PLATFORM_GUID = 'f4be0d51-bf02-5520-b589-8e6d23515876';

    /**
     * The "product_family_code" that the plugin uses to identify itself during LTI requests to tools.
     * We send moodle's own product family code because we count the plugin as part of moodle.
     */
    public const PRODUCT_FAMILY_CODE = "moodle";

    /**
     * Can be used to override the default JwksFetcher. Used for testing purposes.
     *
     * @var null|JwksFetcher
     */
    public static $jwksfetcher = null;

    /**
     * Builds an LTI message for launching Kialo. This is used for both the resource link and deep linking flows.
     *
     * @param string $messagetype LTI message type, e.g. LtiResourceLinkRequest or LtiDeepLinkingRequest, see LtiMessageInterface.
     * @param string $targetlinkuri The URL that the user will be redirected to after the LTI flow.
     * @param string $deploymentid The unique deployment ID of this activity (used to link the discussion on Kialo's side).
     * @param string $moodleuserid The Moodle user ID of the user that is launching the activity.
     * @param int $courseid The Moodle course ID of the course that the activity is in.
     * @param array $roles The LTI roles to assign to the user, e.g. Instructor or Learner.
     * @param array $optionalclaims Optional claims to include in the LTI message.
     * @return LtiMessageInterface The LTI message that can be used to launch Kialo.
     * @throws LtiExceptionInterface
     * @throws \dml_exception
     */
    private static function build_platform_originating_launch(
        string $messagetype,
        string $targetlinkuri,
        string $deploymentid,
        string $moodleuserid,
        int $courseid,
        array $roles,
        array $optionalclaims
    ): LtiMessageInterface {
        $kialoconfig = kialo_config::get_instance();
        $registration = $kialoconfig->create_registration($deploymentid);

        // In lti_auth.php we require the user to be logged into Moodle and have permissions on the course.
        // We also assert that it's the same moodle user that was used in the first step.
        $loginhint = "$courseid/$moodleuserid";

        $builder = new PlatformOriginatingLaunchBuilder();
        $ltimessage = $builder->buildPlatformOriginatingLaunch(
            $registration,
            $messagetype,
            $targetlinkuri,
            $loginhint, // Login hint that will be used afterwards by the platform to perform authentication.
            $deploymentid,
            $roles,
            $optionalclaims,
        );
        $ltimessage->getParameters()->add(['kialo_plugin_version' => kialo_config::get_release()]);
        return $ltimessage;
    }

    /**
     * Assigns LTI roles based on the current user's roles in the given context (module).
     * Any users with the `mod/kialo:kialo_admin` capability (see `db/access.php`) are assigned the Instructor role.
     *
     * @param mixed $context Moodle module context (e.g. via `context_module::instance($cmid)`)
     * @return string[] list of LTI roles (according to LTI spec), e.g. Instructor or Learner.
     * @throws \coding_exception
     * @see https://www.imsglobal.org/spec/lti/v1p3#lis-vocabulary-for-context-roles
     */
    public static function assign_lti_roles($context): array {
        // Note: The $context parameter is intentionally not type-hinted as `context_module` because between Moodle 4.2 and other
        // versions the concrete type differs. In Moodle 4.2 it's `context_module`, in other versions it's `core\context\module`.
        // And since we need to support versions older than PHP 8.0, we can't use an union type here.

        $roles = [];
        if (has_capability('mod/kialo:kialo_admin', $context)) {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
        } else if (has_capability('mod/kialo:view', $context)) {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
        }

        return $roles;
    }

    /**
     * Generates a resource link ID based on the course module ID.
     * This is an arbitrary string, but it must be unique and identify the
     * Kialo module in Moodle so we can link back to it later.
     *
     * @param int $coursemoduleid
     * @return string
     */
    public static function resource_link_id(int $coursemoduleid): string {
        return 'resource-link-' . $coursemoduleid;
    }

    /**
     * Returns the course module ID from the resource link ID.
     *
     * @param string $resourcelinkid
     * @return int
     */
    public static function parse_resource_link_id(string $resourcelinkid): int {
        return (int) preg_replace('/resource-link-/', '', $resourcelinkid);
    }

    /**
     * Initializes an LTI flow that ends up just taking the user to the target_link_uri on the tool (i.e. Kialo).
     *
     * @param int $courseid
     * @param int $coursemoduleid
     * @param string $deploymentid Usually KIALO_LTI_DEPLOYMENT_ID
     * @param string $moodleuserid
     * @param string $discussionurl
     * @param string|null $groupid
     * @param string|null $groupname
     * @return LtiMessageInterface
     * @throws LtiExceptionInterface
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public static function init_resource_link(
        int $courseid,
        int $coursemoduleid,
        string $deploymentid,
        string $moodleuserid,
        string $discussionurl,
        ?string $groupid = null,
        ?string $groupname = null
    ): LtiMessageInterface {
        $context = context_module::instance($coursemoduleid);
        $roles = self::assign_lti_roles($context);

        $customclaims = [];

        if (!empty($groupid) && !empty($groupname)) {
            $customclaims['kialoGroupId'] = $groupid;
            $customclaims['kialoGroupName'] = $groupname;
        }

        return self::build_platform_originating_launch(
            LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
            $discussionurl,
            $deploymentid,
            $moodleuserid,
            $courseid,
            $roles,
            [
                // See https://www.imsglobal.org/spec/lti/v1p3#resource-link-claim.
                new ResourceLinkClaim(self::resource_link_id($coursemoduleid), '', ''),

                // We provide the course ID as the context ID so that discussion links are scoped to the course.
                // See https://www.imsglobal.org/spec/lti/v1p3#context-claim.
                LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT => [
                    "id" => $courseid,
                ],

                LtiMessagePayloadInterface::CLAIM_LTI_CUSTOM => count($customclaims) > 0 ? $customclaims : null,
            ],
        );
    }

    /**
     * Validates an LTI deep link response from Kialo and returns the validated details, i.e. discussion details and deployment ID.
     *
     * @param ServerRequestInterface $request
     * @param string $deploymentid
     * @return deep_linking_result
     * @throws LtiException
     * @throws \dml_exception
     * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-example
     */
    public static function validate_deep_linking_response(
        ServerRequestInterface $request,
        string $deploymentid = KIALO_LTI_DEPLOYMENT_ID
    ): deep_linking_result {
        $kialoconfig = kialo_config::get_instance();
        $registration = $kialoconfig->create_registration($deploymentid);
        $registrationrepo = new static_registration_repository($registration);
        $noncerepo = new NonceRepository(moodle_cache::nonce_cache());

        $validator = new PlatformLaunchValidator($registrationrepo, $noncerepo, self::$jwksfetcher);
        $message = $validator->validateToolOriginatingLaunch($request);
        $payload = $message->getPayload();

        if ($message->hasError() || $payload === null) {
            throw new LtiException($message->getError());
        }

        if ($payload->getMessageType() !== LtiMessage::LTI_MESSAGE_TYPE_DEEP_LINKING_RESPONSE) {
            throw new LtiException('Expected LtiDeepLinkingResponse');
        }

        if ($payload->getDeepLinkingContentItems() === null) {
            throw new LtiException('Expected deep linking content items');
        }

        $items = $payload->getDeepLinkingContentItems()->getContentItems();
        if (count($items) !== 1) {
            throw new LtiException('Expected exactly one content item');
        }

        $content = $items[0];

        if ($content["type"] !== "ltiResourceLink") {
            throw new LtiException('Expected content item to be of type ltiResourceLink');
        }

        if (empty($content["url"])) {
            throw new LtiException('Expected content item to have a url');
        }

        return new deep_linking_result(
            $payload->getDeploymentId(),
            $content["url"],
            $content["title"] ?? "",
        );
    }

    /**
     * Creates a JWT signed by Moodle itself.
     *
     * @param array $headers
     * @param array $claims
     * @return string
     */
    private static function create_platform_jwt_token(
        array $headers = [],
        array $claims = []
    ): string {
        $platformkey = kialo_config::get_instance()->get_platform_keychain()->getPrivateKey();
        $jwtbuilder = new JwtBuilder();
        return $jwtbuilder->build($headers, $claims, $platformkey)->toString();
    }

    /**
     * Initializes an LTI flow for selecting a discussion on Kialo and then returning back to Moodle.
     *
     * @param int $courseid
     * @param string $moodleuserid
     * @param string $deploymentid
     * @return LtiMessageInterface
     * @throws LtiExceptionInterface
     * @throws \dml_exception
     */
    public static function init_deep_link(int $courseid, string $moodleuserid, string $deploymentid = KIALO_LTI_DEPLOYMENT_ID) {
        $kialoconfig = kialo_config::get_instance();

        $deeplinkingreturnurl = (new \moodle_url('/mod/kialo/lti_select.php'))->out(false);

        // In the end we want to redirect to launch which handles the deep link request.
        $targetlinkuri = $kialoconfig->get_tool_url() . '/lti/deeplink';

        // Our PHP LTI library expects the data token to be a JWT token signed by the platform.
        $datatoken = self::create_platform_jwt_token(); // Empty because we don't need any data, just the signature.

        // See https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-example.
        return self::build_platform_originating_launch(
            LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
            $targetlinkuri,
            $deploymentid,
            $moodleuserid,
            $courseid,
            ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'], // Only teachers can deeplink.
            [
                new DeepLinkingSettingsClaim(
                    $deeplinkingreturnurl,
                    [LtiResourceLinkInterface::TYPE],   // Accept_types.
                    ["window"],                         // Accept_presentation_document_targets.
                    null,                               // Accept_media_types, unused.
                    false,                              // AcceptMultiple: We just accept one discussion.
                    false,                              // AutoCreate.
                    null,                               // Title, unused.
                    null,                               // Text, unused.
                    $datatoken,
                ),

                // We provide the course ID as the context ID so that discussion links are scoped to the course.
                // See https://www.imsglobal.org/spec/lti/v1p3#context-claim.
                LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT => [
                    "id" => $courseid,
                ],
            ],
        );
    }

    /**
     * Finishes the LTI authentication flow, parsing the current request (the user should have been redirected here by Kialo).
     *
     * @return LtiMessageInterface Contains the launch details
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface
     * @throws \dml_exception
     */
    public static function lti_auth(): LtiMessageInterface {
        global $CFG;

        $kialoconfig = kialo_config::get_instance();
        $registration = $kialoconfig->create_registration();

        // Get related registration of the launch.
        $registrationrepository = new static_registration_repository($registration);
        $userauthenticator = new user_authenticator();

        $request = ServerRequest::fromGlobals();

        // The LTI library mistakenly generates a new nonce every time.
        // This works around the issue by providing our own correct nonce generator.
        // See https://github.com/oat-sa/lib-lti1p3-core/issues/154.
        $nonce = $request->getQueryParams()['nonce'] ?? '';
        $payloadbuilder = new MessagePayloadBuilder(new static_nonce_generator($nonce));
        $payloadbuilder->withClaim('kialo_plugin_version', kialo_config::get_release());

        // See https://www.imsglobal.org/spec/lti/v1p3#platform-instance-claim.
        $payloadbuilder->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_TOOL_PLATFORM, [
            'guid' => self::PLATFORM_GUID,
            'product_family_code' => self::PRODUCT_FAMILY_CODE,
            'version' => $CFG->version,
        ]);
        self::add_endpoints($payloadbuilder, $request);

        // Create the OIDC authenticator.
        $authenticator = new OidcAuthenticator($registrationrepository, $userauthenticator, $payloadbuilder);

        // Perform the login authentication (delegating to the $userAuthenticator with the hint 'loginHint').
        return $authenticator->authenticate($request);
    }

    /**
     * Adds all necessary service endpoint claims to the LTI payload.
     *
     * @param MessagePayloadBuilder $payloadbuilder Payload to add claims to (for the LTI authentication response)
     * @param ServerRequestInterface $request The LTI authentication request
     * @return void
     * @throws LtiExceptionInterface
     * @throws \moodle_exception
     */
    public static function add_endpoints(MessagePayloadBuilder $payloadbuilder, ServerRequestInterface $request): void {
        // Get required context for service params from original JWT token. See init_resource_link and init_deep_link.
        $originaltoken = (new Parser())->parse(LtiMessage::fromServerRequest($request)->getParameters()->get('lti_message_hint'));
        $courseid = $originaltoken->getClaims()->getMandatory(LtiMessagePayloadInterface::CLAIM_LTI_CONTEXT)['id'];
        $resourcelink = $originaltoken->getClaims()->get(LtiMessagePayloadInterface::CLAIM_LTI_RESOURCE_LINK);
        $agsparams = [
            "course_id" => $courseid,
        ];

        // Resource link claim is only present in resource link flows, not during deep linking.
        if ($resourcelink) {
            $agsparams['resource_link_id'] = $resourcelink['id'];

            $cmid = self::parse_resource_link_id($resourcelink['id']);
            $agsparams['cmid'] = $cmid;
            self::add_update_discussion_url_endpoint($payloadbuilder, [
                "cmid" => $cmid,
            ]);
        }
        self::add_ags_endpoints($payloadbuilder, $agsparams);
    }

    /**
     * Adds claims necessary to inform LTI consumers about the assignment and grading service we implemented
     * according to https://www.imsglobal.org/spec/lti-ags/v2p0. Essentially it provides the endpoints necessary
     * to use the service from the Kialo app (the LTI tool / consumer).
     *
     * @param MessagePayloadBuilder $payloadbuilder Payload to add claims to (for the LTI authentication response)
     * @param array $serviceparams
     * @return void
     * @throws LtiExceptionInterface
     * @throws \moodle_exception
     */
    public static function add_ags_endpoints(MessagePayloadBuilder $payloadbuilder, array $serviceparams): void {
        $payloadbuilder->withClaim(LtiMessagePayloadInterface::CLAIM_LTI_AGS, [
            "scope" => MOD_KIALO_LTI_AGS_SCOPES,

            // This is the endpoint used by Kialo to get the line item details and post student scores.
            "lineitem" => (new moodle_url('/mod/kialo/lti_lineitem.php', $serviceparams))->out(false),

            // The lineitems (plural) endpoint is used by Kialo to look up line items by resource link ID
            // only if the line item URL is not included in the launch data.
            // Since our plugin always includes the line item URL in the launch data (in this very claim),
            // Kialo never needs to use this endpoint.
            // So this endpoint is currently not necessary and therefore not implemented.
            // But if we end up implementing it, this is what it will be called.
            "lineitems" => (new moodle_url('/mod/kialo/lti_lineitems.php', $serviceparams))->out(false),
        ]);
    }

    /**
     * Adds claims for the custom endpoint of the Kialo plugin to update discussion URLs during the backup restore workflow.
     *
     * @param MessagePayloadBuilder $payloadbuilder Payload to add claims to (for the LTI authentication response)
     * @param array $serviceparams
     * @return void
     * @throws LtiExceptionInterface
     * @throws \moodle_exception
     */
    public static function add_update_discussion_url_endpoint(MessagePayloadBuilder $payloadbuilder, array $serviceparams): void {
        $payloadbuilder->withClaim(MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_ENDPOINT_CLAIM, [
            "scope" => [MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_SCOPE],
            "update_discussion_url" => (new moodle_url('/mod/kialo/update_discussion_url.php', $serviceparams))->out(false),
        ]);
    }
    /**
     * Generates an access token for the service to use when calling the LTI service endpoints.
     * @return ResponseInterface
     * @throws \dml_exception
     */
    public static function generate_service_access_token(): ResponseInterface {
        $kialoconfig = kialo_config::get_instance();
        $registrationrepo = $kialoconfig->get_registration_repository();

        $factory = new AuthorizationServerFactory(
            new ClientRepository($registrationrepo, null, new kialo_logger("ClientRepository")),
            new AccessTokenRepository(moodle_cache::access_token_cache(), new kialo_logger("AccessTokenRepository")),
            new ScopeRepository(array_map(
                fn ($scope): Scope => new Scope($scope),
                [...MOD_KIALO_LTI_AGS_SCOPES, MOD_KIALO_LTI_UPDATE_DISCUSSION_URL_SCOPE]
            )),
            $kialoconfig->get_platform_keychain()->getPrivateKey()->getContent(),
        );

        $keychainrepo = new static_keychain_repository($kialoconfig->get_platform_keychain());
        $generator = new AccessTokenResponseGenerator($keychainrepo, $factory);
        $request = ServerRequest::fromGlobals();
        $response = new Response();

        try {
            // Validate assertion, generate and sign access token response, using the key chain private key.
            $keychainidentifier = $kialoconfig->get_platform_keychain()->getIdentifier();
            $response = $generator->generate($request, $response, $keychainidentifier);
        } catch (OAuthServerException $exception) {
            $response = $exception->generateHttpResponse($response);
        }

        return $response;
    }

    /**
     * Authenticates a service request using the LTI access token. Throws an error if authentication fails.
     * @param array $scopes The scopes that the service request must have.
     * @return void
     * @throws \dml_exception
     */
    public static function authenticate_service_request(array $scopes): void {
        $kialoconfig = kialo_config::get_instance();
        $registrationrepo = $kialoconfig->get_registration_repository();
        $validator = new RequestAccessTokenValidator($registrationrepo, new kialo_logger("RequestAccessTokenValidator"));

        // Validate request provided access token using the registration platform public key, against allowed scopes.
        $result = $validator->validate(ServerRequest::fromGlobals(), $scopes);

        if ($result->hasError()) {
            throw new \dml_exception($result->getError());
        }
    }
}
