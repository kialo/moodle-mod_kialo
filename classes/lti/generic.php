<?php

namespace mod_kialo\lti;

use context_module;
use mod_kialo\kialo_config;
use mod_kialo\lti_flow;
use OAT\Library\Lti1p3Core\Message\Launch\Builder\PlatformOriginatingLaunchBuilder;
use OAT\Library\Lti1p3Core\Message\LtiMessageInterface;
use OAT\Library\Lti1p3Core\Message\Payload\Claim\ResourceLinkClaim;

class generic_lti_platform extends lti_platform {

    public function init_resource_link(int $courseid, int $coursemoduleid, string $deploymentid, string $moodleuserid): string {
        $kialoconfig = kialo_config::get_instance();
        $registration = $kialoconfig->create_registration($deploymentid);
        $context = context_module::instance($coursemoduleid);
        $roles = lti_flow::assign_lti_roles($context);

        // In lti_auth.php we require the user to be logged into Moodle and have permissions on the course.
        // We also assert that it's the same moodle user that was used in the first step.
        $loginhint = "$courseid/$moodleuserid";

        $builder = new PlatformOriginatingLaunchBuilder();
        return $builder->buildPlatformOriginatingLaunch(
                $registration,
                LtiMessageInterface::LTI_MESSAGE_TYPE_RESOURCE_LINK_REQUEST,
                $kialoconfig->get_tool_url(), // Unused, as the final destination URL will be decided by our backend.
                $loginhint, // Login hint that will be used afterwards by the platform to perform authentication.
                $deploymentid,
                $roles,
                [
                    // The resource link claim is required in the spec, but we don't use it
                    // https://www.imsglobal.org/spec/lti/v1p3#resource-link-claim.
                        new ResourceLinkClaim('resource-link-' . $deploymentid, '', ''),
                ]
        )->toHtmlRedirectForm();
    }
}
