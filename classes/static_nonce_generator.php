<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;

class static_nonce_generator implements NonceGeneratorInterface {
    private string $nonce_value;

    public function __construct(string $nonce_value) {
        $this->nonce_value = $nonce_value;
    }

    public function generate(int $ttl = null): NonceInterface {
        return new Nonce($this->nonce_value);
    }
}
