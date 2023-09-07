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
 * Endpoint that returns the JWKS for the platform key.
 *
 * Called by Kialo's backend during the LTI 1.3 flow.
 *
 * @package    mod_kialo
 * @copyright  2023 onwards, Kialo GmbH <support@kialo-edu.com>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// phpcs:disable moodle.Files.RequireLogin.Missing -- This is a public endpoint that doesn't require a login.

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once('vendor/autoload.php');

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;

$kid = get_config("mod_kialo", "kid");
$privatekeystr = get_config("mod_kialo", "privatekey");

$privatekey = openssl_pkey_get_private($privatekeystr);
$pk = openssl_pkey_get_details($privatekey);
$publickeystr = $pk['key'];

$platformkeychain = (new KeyChainFactory())->create(
    $kid,                       // Identifier (used for JWT kid header).
    'kialo',                    // Key set name (for grouping).
    $publickeystr,              // Public key (file or content).
    $privatekeystr,             // Private key (file or content).
);

$jwkexport = (new JwkRS256Exporter())->export($platformkeychain);

header('Content-Type: application/json');
echo json_encode(["keys" => [$jwkexport]]);
