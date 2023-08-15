<?php

namespace mod_kialo;

use OAT\Library\Lti1p3Core\Message\Payload\Claim\MessagePayloadClaimInterface;

/**
 * @see https://www.imsglobal.org/spec/lti/v1p3#custom-properties-and-variable-substitution
 */
class custom_claim implements MessagePayloadClaimInterface {

    /**
     * @var array any list of custom claims
     */
    private $custom;

    public static function getClaimName(): string {
        return "https://purl.imsglobal.org/spec/lti/claim/custom";
    }

    public function __construct(array $customdata) {
        $this->custom = $customdata;
    }

    public function normalize(): array
    {
        return array_filter($this->custom);
    }

    public static function denormalize(array $claimData): custom_claim
    {
        return new self($claimData);
    }
}
