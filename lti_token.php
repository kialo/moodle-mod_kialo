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
 * See also https://github.com/oat-sa/lib-lti1p3-core/blob/master/doc/service/service-server.md.
 */

require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');
require_once(__DIR__ . '/constants.php');
require_once('vendor/autoload.php');

use GuzzleHttp\Psr7\Response as ServerResponse;
use GuzzleHttp\Psr7\ServerRequest;
use League\OAuth2\Server\Exception\OAuthServerException;
use mod_kialo\kialo_config;
use mod_kialo\kialo_logger;
use mod_kialo\moodle_cache;
use mod_kialo\static_registration_repository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Entity\Scope;
use OAT\Library\Lti1p3Core\Security\OAuth2\Factory\AuthorizationServerFactory;
use OAT\Library\Lti1p3Core\Security\OAuth2\Generator\AccessTokenResponseGenerator;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\AccessTokenRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ClientRepository;
use OAT\Library\Lti1p3Core\Security\OAuth2\Repository\ScopeRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

ob_start();

$kialoconfig = kialo_config::get_instance();
$registration = $kialoconfig->create_registration();
$registrationrepo = new static_registration_repository($registration);

$factory = new AuthorizationServerFactory(
    new ClientRepository($registrationrepo, null, new kialo_logger("ClientRepository")),
    new AccessTokenRepository(moodle_cache::access_token_cache(), new kialo_logger("AccessTokenRepository")),
    new ScopeRepository(array_map(fn ($scope): Scope => new Scope($scope), MOD_KIALO_LTI_AGS_SCOPES)),
    $kialoconfig->get_platform_keychain()->getPrivateKey()->getContent(),
);

$keychainrepo = new \mod_kialo\static_keychain_repository($kialoconfig->get_platform_keychain());
$generator = new AccessTokenResponseGenerator($keychainrepo, $factory);

/** @var ServerRequestInterface $request */
$request = ServerRequest::fromGlobals();

/** @var ResponseInterface $response */
$response = new ServerResponse();

try {
    // Extract keyChainIdentifier from request uri parameter.
    $keychainidentifier = $kialoconfig->get_platform_keychain()->getIdentifier();

    // Validate assertion, generate and sign access token response, using the key chain private key.
    $response = $generator->generate($request, $response, $keychainidentifier);
} catch (OAuthServerException $exception) {
    $response = $exception->generateHttpResponse($response);
}

$output = ob_get_clean();

file_put_contents(__DIR__ . '/latest_request.json', $output . '\n\n' . $response->getBody());

// Write the response.
$statusline = sprintf('HTTP/%s %s %s', $response->getProtocolVersion(), $response->getStatusCode(), $response->getReasonPhrase());
header($statusline, true); /* The header replaces a previous similar header. */

foreach ($response->getHeaders() as $name => $values) {
    foreach ($values as $value) {
        header(sprintf('%s: %s', $name, $value), false);
    }
}

echo $response->getBody();

exit();
