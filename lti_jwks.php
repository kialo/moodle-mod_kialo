<?php

// TODO: Consider implementing key rotation

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once('vendor/autoload.php');

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$kid = get_config("mod_kialo", "kid");
$privatekeystr = get_config("mod_kialo", "privatekey");

$privatekey = openssl_pkey_get_private($privatekeystr);
$pk = openssl_pkey_get_details($privatekey);
$publickeystr = $pk['key'];

$platformkeychain = (new KeyChainFactory)->create(
        $kid,                       // Identifier (used for JWT kid header).
        'kialo',                    // Key set name (for grouping).
        $publickeystr,              // Public key (file or content).
        $privatekeystr,             // Private key (file or content).
);

$jwkexport = (new JwkRS256Exporter())->export($platformkeychain);

header('Content-Type: application/json');
echo json_encode(["keys" => [$jwkexport]]);
