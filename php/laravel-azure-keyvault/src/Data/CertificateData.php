<?php

namespace Shrd\Laravel\Azure\KeyVault\Data;

use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasAttributes;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasTags;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultCertificateReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;

class CertificateData
{
    use HasTags;
    use HasAttributes;

    public function __construct(public string|null $id = null,
                                public string|null $kid = null,
                                public string|null $sid = null,
                                public string|null $x5t = null,
                                public string|null $cer = null,
                                public CertificatePolicyData|null $policy = null,
                                public array $attributes = [],
                                public array $tags = [])
    {
    }

    /**
     * @throws JsonException
     */
    public static function fromResponseJson(string $json): self
    {
        return self::fromResponseArray(\Safe\json_decode($json, true));
    }

    public static function fromResponseArray(array $response): self
    {
        return new self(
            id: $response['id'] ?? null,
            kid: $response['kid'] ?? null,
            sid: $response['sid'] ?? null,
            x5t: $response['x5t'] ?? null,
            cer: $response['cer'] ?? null,
            policy: $response['policy'] ? CertificatePolicyData::fromResponseArray($response['policy']) : null,
            attributes: $response['attributes'] ?? [],
            tags: $response['tags'] ?? []
        );
    }

    public function getReference(): KeyVaultCertificateReference
    {
        return KeyVaultCertificateReference::fromUri($this->id);
    }

    public function getCertificateReference(): KeyVaultCertificateReference
    {
        return $this->getReference();
    }

    public function getKeyReference(): KeyVaultKeyReference
    {
        return KeyVaultKeyReference::fromUri($this->kid);
    }

    public function getSecretReference(): KeyVaultSecretReference
    {
        return KeyVaultSecretReference::fromUri($this->sid);
    }

    // TODO: Add getJWK
    // TODO: Add getJWKs
}
