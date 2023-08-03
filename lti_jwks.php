<?php

# TODO: Consider implementing key rotation

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once('vendor/autoload.php');

use OAT\Library\Lti1p3Core\Security\Jwks\Exporter\Jwk\JwkRS256Exporter;
use OAT\Library\Lti1p3Core\Security\Key\KeyChainFactory;
use OAT\Library\Lti1p3Core\Security\Key\KeyInterface;

$kid = get_config("mod_kialo", "kid");
$privatekey_str = get_config("mod_kialo", "privatekey");

$privatekey = openssl_pkey_get_private($privatekey_str);
$pk = openssl_pkey_get_details($privatekey);
$publickey_str = $pk['key'];

$platformKeyChain = (new KeyChainFactory)->create(
        $kid,                                // [required] identifier (used for JWT kid header)
        'kialo',                        // [required] key set name (for grouping)
        $publickey_str, // [required] public key (file or content)
        $privatekey_str,     // [optional] private key (file or content)
        '',                             // [optional] private key passphrase (if existing)
        KeyInterface::ALG_RS256            // [optional] algorithm (default: RS256)
);

$jwkExport = (new JwkRS256Exporter())->export($platformKeyChain);

header('Content-Type: application/json');
echo json_encode(["keys" => [$jwkExport]]);
