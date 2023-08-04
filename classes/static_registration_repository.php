<?php

namespace mod_kialo;

defined('MOODLE_INTERNAL') || die();

use OAT\Library\Lti1p3Core\Registration\RegistrationInterface;
use OAT\Library\Lti1p3Core\Registration\RegistrationRepositoryInterface;

class static_registration_repository implements RegistrationRepositoryInterface {
    private RegistrationInterface $registration;

    public function __construct(RegistrationInterface $registration) {
        $this->registration = $registration;
    }

    public function find(string $identifier): ?RegistrationInterface {
        if ($this->registration->getIdentifier() !== $identifier) {
            return null;
        }
        return $this->registration;
    }

    public function findAll(): array {
        return [$this->registration];
    }

    public function findByClientId(string $clientId): ?RegistrationInterface {
        if ($this->registration->getClientId() !== $clientId) {
            return null;
        }
        return $this->registration;
    }

    public function findByPlatformIssuer(string $issuer, string $clientId = null): ?RegistrationInterface {
        $platform = $this->registration->getPlatform();
        if ($platform->getAudience() !== $issuer) {
            return null;
        }
        if ($clientId !== null && $clientId !== $this->registration->getClientId()) {
            return null;
        }
        return $this->registration;
    }

    public function findByToolIssuer(string $issuer, string $clientId = null): ?RegistrationInterface {
        $tool = $this->registration->getTool();
        if ($issuer !== $tool->getAudience()) {
            return null;
        }
        if ($clientId !== null && $clientId !== $this->registration->getClientId()) {
            return null;
        }
        return $this->registration;
    }
}