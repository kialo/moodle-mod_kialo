<?php

namespace mod_kialo;

use GuzzleHttp\Psr7\ServerRequest;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Builder\MessagePayloadBuilder;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;
use OAT\Library\Lti1p3Core\Message\Payload\LtiMessagePayloadInterface;
use OAT\Library\Lti1p3Core\Security\Oidc\OidcAuthenticator;

class lti_flow {
    public static function lti_init_launch(string $deployment_id, string $moodle_user_id, ?string $target_link_uri = null) {
        $kialo_config = kialo_config::get_instance();
        $registration = $kialo_config->create_registration($deployment_id);

        $builder = new PlatformOriginatingLaunchBuilder();
        return $builder->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                $target_link_uri ?? 'TBD', // the final destination URL will decided by our backend
                $moodle_user_id, // login hint that will be used afterwards by the platform to perform authentication
                $deployment_id,
                [
                    // TODO: set appropriate roles
                        'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner' // role
                ],
                [
                    // the resource link claim is required in the spec, but we don't use it
                    // https://www.imsglobal.org/spec/lti/v1p3#resource-link-claim
                    new ResourceLinkClaim('resource-link-' . $deployment_id, '', ''),
                ]
        );
    }

    public static function lti_auth(): LtiMessageInterface {
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
        $authenticator = new OidcAuthenticator($registrationRepository, $userAuthenticator, $payloadBuilder);

        // Perform the login authentication (delegating to the $userAuthenticator with the hint 'loginHint')
        return $authenticator->authenticate($request);
    }
}
