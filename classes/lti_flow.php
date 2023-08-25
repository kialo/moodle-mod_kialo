<?php

namespace mod_kialo;

use context_module;
use GuzzleHttp\Psr7\ServerRequest;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\Launch\Validator\Platform\PlatformLaunchValidator;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\DeepLinkingSettingsClaim;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Resource\LtiResourceLink\LtiResourceLinkInterface;
use OAT\Library\Lti1p3Core\Security\Jwt\Builder\Builder as JwtBuilder;
use OAT\Library\Lti1p3Core\Security\Jwt\Validator\ValidatorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceRepository;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;
use Psr\Http\Message\ServerRequestInterface;

class lti_flow {
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
            array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor');
        } else {
            array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner');
        }

        return $roles;
    }

    /**
     * Initializes an LTI flow that ends up just taking the user to the target_link_uri on the tool (i.e. Kialo).
     *
     * @param int $course_id
     * @param int $course_module_id
     * @param string $deployment_id
     * @param string $moodle_user_id
     * @param string|null $target_link_uri
     * @return LtiMessageInterface
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface
     * @throws \coding_exception
     */
    public static function init_resource_link(int $course_id, int $course_module_id, string $deployment_id, string $moodle_user_id) {
        $kialo_config = kialo_config::get_instance();
        $registration = $kialo_config->create_registration($deployment_id);
        $context = context_module::instance($course_module_id);
        $roles = self::assign_lti_roles($context);

        // In lti_auth.php we require the user to be logged into Moodle and have permissions on the course.
        // We also assert that it's the same moodle user that was used in the first step.
        $login_hint = "$course_id/$moodle_user_id";

        $builder = new PlatformOriginatingLaunchBuilder();
        return $builder->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                $kialo_config->get_tool_url(), // unused, as the final destination URL will be decided by our backend
                $login_hint, // login hint that will be used afterwards by the platform to perform authentication
                $deployment_id,
                $roles,
                [
                    // the resource link claim is required in the spec, but we don't use it
                    // https://www.imsglobal.org/spec/lti/v1p3#resource-link-claim
                        new ResourceLinkClaim('resource-link-' . $deployment_id, '', ''),
                ]
        );
    }

    /**
     * @param ServerRequestInterface $request
     * @return deep_linking_result
     * @throws LtiException
     * @see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-example
     */
    public static function validate_deep_linking_response(ServerRequestInterface $request,
            string $deployment_id): deep_linking_result {
        $kialo_config = kialo_config::get_instance();
        $registration = $kialo_config->create_registration($deployment_id);
        $registrationrepo = new static_registration_repository($registration);
        $noncerepo = new NonceRepository(moodle_cache::nonce_cache());

        $validator = new PlatformLaunchValidator($registrationrepo, $noncerepo);
        $message = $validator->validateToolOriginatingLaunch($request);
        $payload = $message->getPayload();

        if ($message->hasError() || $payload === null) {
            throw new LtiException($message->getError());
        }

        if ($payload->getMessageType() !== "LtiDeepLinkingResponse") {
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

        if (!$content["url"]) {
            throw new LtiException('Expected content item to have a url');
        }

        return new deep_linking_result(
                $payload->getDeploymentId(),
                $content["url"] ?? "",
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
     * @param int $course_id
     * @param int $course_module_id
     * @param string $moodle_user_id
     * @return LtiMessageInterface
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface
     * @throws \coding_exception
     */
    public static function init_deep_link(int $course_id, string $moodle_user_id, string $deployment_id) {
        $kialoconfig = kialo_config::get_instance();

        $registration = $kialoconfig->create_registration($deployment_id);

        // In lti_auth.php we require the user to be logged into Moodle and have permissions on the course.
        // We also assert that it's the same moodle user that was used in the first step.
        $loginhint = "$course_id/$moodle_user_id";

        $deeplinkingreturnurl = (new \moodle_url('/mod/kialo/lti_select.php'))->out(false);

        $builder = new PlatformOriginatingLaunchBuilder();

        // in the end we want to redirect to launch which handles the deep link request
        $targetlinkuri = $kialoconfig->get_tool_url() . '/lti/launch';

        // our PHP LTI library expects the data token to be a JWT token signed by the platform
        $datatoken = self::create_platform_jwt_token(); // empty because we don't need any data, just the signature

        // see https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-example
        return $builder->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
                $targetlinkuri,
                $loginhint, // login hint that will be used afterwards by the platform to perform authentication
                $deployment_id,
                ['http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'], // only teachers can deeplink
                [
                        new DeepLinkingSettingsClaim(
                                $deeplinkingreturnurl,
                                [LtiResourceLinkInterface::TYPE], // accept_types
                                ["window"], // accept_presentation_document_targets
                                null,
                                false, // acceptMultiple
                                false, // autoCreate
                                null, // title, unused
                                null, // text, unused
                                $datatoken,
                        ),
                ]
        );
    }

    public static function lti_auth(?ValidatorInterface $validator = null): LtiMessageInterface {
        $kialo_config = kialo_config::get_instance();
        $registration = $kialo_config->create_registration();

        // Get related registration of the launch
        $registrationRepository = new static_registration_repository($registration);
        $userAuthenticator = new user_authenticator();

        $request = ServerRequest::fromGlobals();

        // The LTI library mistakenly generates a new nonce, this works around the issue by providing our own correct nonce generator.
        // See https://github.com/oat-sa/lib-lti1p3-core/issues/154.
        $nonce = $request->getQueryParams()['nonce'];
        $payloadBuilder = new MessagePayloadBuilder(new static_nonce_generator($nonce));

        // Create the OIDC authenticator
        $authenticator = new OidcAuthenticator($registrationRepository, $userAuthenticator, $payloadBuilder, $validator);

        // Perform the login authentication (delegating to the $userAuthenticator with the hint 'loginHint')
        return $authenticator->authenticate($request);
    }
}
