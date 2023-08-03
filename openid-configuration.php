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
 * This file returns the OpenId/LTI Configuration for this site.
 *
 * It is part of the LTI Tool Dynamic Registration, and used by
 * tools to get the site configuration and registration end-point.
 *
 * @package    mod_lti
 * @copyright  2020 Claude Vervoort (Cengage), Carlos Costa, Adrian Hutchinson (Macgraw Hill)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_lti\local\ltiopenid\registration_helper;

define('NO_DEBUG_DISPLAY', true);
define('NO_MOODLE_COOKIES', true);
require_once(__DIR__ . '/../../config.php');

function lti_get_capabilities() {
    $capabilities = array(
            'basic-lti-launch-request' => '',
            'ContentItemSelectionRequest' => '',
            'ToolProxyRegistrationRequest' => '',
            'Context.id' => 'context_id',
            'Context.title' => 'context_title',
            'Context.label' => 'context_label',
            'Context.id.history' => null,
            'Context.sourcedId' => 'lis_course_section_sourcedid',
            'Context.longDescription' => '$COURSE->summary',
            'Context.timeFrame.begin' => '$COURSE->startdate',
            'CourseSection.title' => 'context_title',
            'CourseSection.label' => 'context_label',
            'CourseSection.sourcedId' => 'lis_course_section_sourcedid',
            'CourseSection.longDescription' => '$COURSE->summary',
            'CourseSection.timeFrame.begin' => null,
            'CourseSection.timeFrame.end' => null,
            'ResourceLink.id' => 'resource_link_id',
            'ResourceLink.title' => 'resource_link_title',
            'ResourceLink.description' => 'resource_link_description',
            'User.id' => 'user_id',
            'User.username' => '$USER->username',
            'Person.name.full' => 'lis_person_name_full',
            'Person.name.given' => 'lis_person_name_given',
            'Person.name.family' => 'lis_person_name_family',
            'Person.email.primary' => 'lis_person_contact_email_primary',
            'Person.sourcedId' => 'lis_person_sourcedid',
            'Person.name.middle' => '$USER->middlename',
            'Person.address.street1' => '$USER->address',
            'Person.address.locality' => '$USER->city',
            'Person.address.country' => '$USER->country',
            'Person.address.timezone' => '$USER->timezone',
            'Person.phone.primary' => '$USER->phone1',
            'Person.phone.mobile' => '$USER->phone2',
            'Person.webaddress' => '$USER->url',
            'Membership.role' => 'roles',
            'Result.sourcedId' => 'lis_result_sourcedid',
            'Result.autocreate' => 'lis_outcome_service_url',
            'BasicOutcome.sourcedId' => 'lis_result_sourcedid',
            'BasicOutcome.url' => 'lis_outcome_service_url',
            'Moodle.Person.userGroupIds' => null);

    return $capabilities;
}

$scopes = ['openid'];
$conf = [
        'issuer' => $CFG->wwwroot . '/mod/kialo',
        'token_endpoint' => (new moodle_url('/mod/kialo/lti_token.php'))->out(false),
        'token_endpoint_auth_methods_supported' => ['private_key_jwt'],
        'token_endpoint_auth_signing_alg_values_supported' => ['RS256'],
        'jwks_uri' => (new moodle_url('/mod/kialo/lti_jwks.php'))->out(false),
        'authorization_endpoint' => (new moodle_url('/mod/kialo/lti_auth.php'))->out(false),
        'registration_endpoint' => (new moodle_url('/mod/kialo/openid-registration.php'))->out(false),
        'scopes_supported' => $scopes,
        'response_types_supported' => ['id_token'],
        'subject_types_supported' => ['public', 'pairwise'],
        'id_token_signing_alg_values_supported' => ['RS256'],
        'claims_supported' => ['sub', 'iss', 'name', 'given_name', 'family_name', 'email'],  # TODO: Avatar?
        'https://purl.imsglobal.org/spec/lti-platform-configuration' => [
                'product_family_code' => 'moodle_kialo_plugin',
                'version' => $CFG->release,
                'messages_supported' => [['type' => 'LtiResourceLinkRequest'],
                        ['type' => 'LtiDeepLinkingRequest', 'placements' => ['ContentArea']]],
                'variables' => array_keys(lti_get_capabilities())
        ]
];

@header('Content-Type: application/json; charset=utf-8');

echo json_encode($conf, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
