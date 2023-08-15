<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Security\Nonce\Nonce;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceGeneratorInterface;
use OAT\Library\Lti1p3Core\Security\Nonce\NonceInterface;

class static_nonce_generator implements NonceGeneratorInterface {

    /**
     * @var string
     */
    private string $nonce;

    public function __construct(string $nonce) {
        $this->nonce = $nonce;
    }

    public function generate(int $ttl = null): NonceInterface {
        return new Nonce($this->nonce);
    }
}
