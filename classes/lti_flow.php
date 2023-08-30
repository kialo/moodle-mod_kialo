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

use context_module;
use GuzzleHttp\Psr7\ServerRequest;
use OAT\Library\Lti1p3Core\Exception\LtiException;
use OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface;
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

/**
 * Functions implementing the LTI steps.
 */
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
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
        } else {
            $roles[] = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner';
        }

        return $roles;
    }

    /**
     * Initializes an LTI flow that ends up just taking the user to the target_link_uri on the tool (i.e. Kialo).
     *
     * @param int $course_id
     * @param int $course_module_id
     * @param string $deployment_id
     * @param int $moodle_user_id
     * @param string|null $target_link_uri
     * @return string HTML form that will redirect the user to the LTI login endpoint
     */
    public static function init_resource_link($courseid, $coursemoduleid, $deploymentid, $moodleuserid) {
        $platform = new moodle_lti_platform();
        return $platform->init_resource_link($courseid, $coursemoduleid, $deploymentid, $moodleuserid);
    }

    /**
     * Finishes the LTI authentication flow, parsing the current request (the user should have been redirected here by Kialo).
     *
     * @param ValidatorInterface|null $validator Can be used to override validation behavior; only used by tests right now.
     * @return string HTML redirect form
     * @throws \OAT\Library\Lti1p3Core\Exception\LtiExceptionInterface
     * @throws \dml_exception
     */
    public static function lti_auth(): string {
        $platform = new moodle_lti_platform();
        return $platform->lti_auth();
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
    public static function validate_deep_linking_response(ServerRequestInterface $request,
            string $deploymentid): deep_linking_result {
        $kialoconfig = kialo_config::get_instance();
        $registration = $kialoconfig->create_registration($deploymentid);
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
     * @param int $courseid
     * @param string $moodleuserid
     * @param string $deploymentid
     * @return LtiMessageInterface
     * @throws LtiExceptionInterface
     * @throws \dml_exception
     */
    public static function init_deep_link(int $courseid, string $moodleuserid, string $deploymentid) {
        $kialoconfig = kialo_config::get_instance();

        $registration = $kialoconfig->create_registration($deploymentid);

        // In lti_auth.php we require the user to be logged into Moodle and have permissions on the course.
        // We also assert that it's the same moodle user that was used in the first step.
        $loginhint = "$courseid/$moodleuserid";

        $deeplinkingreturnurl = (new \moodle_url('/mod/kialo/lti_select.php'))->out(false);

        $builder = new PlatformOriginatingLaunchBuilder();

        // In the end we want to redirect to launch which handles the deep link request.
        $targetlinkuri = $kialoconfig->get_tool_url() . '/lti/launch';

        // Our PHP LTI library expects the data token to be a JWT token signed by the platform.
        $datatoken = self::create_platform_jwt_token(); // Empty because we don't need any data, just the signature.

        // See https://www.imsglobal.org/spec/lti-dl/v2p0#deep-linking-response-example.
        return $builder->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_DEEP_LINKING_REQUEST,
                $targetlinkuri,
                $loginhint, // Login hint that will be used afterwards by the platform to perform authentication.
                $deploymentid,
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
                ]
        );
    }

}
