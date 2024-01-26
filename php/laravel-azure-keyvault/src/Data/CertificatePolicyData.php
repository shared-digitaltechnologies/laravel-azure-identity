<?php

namespace Shrd\Laravel\Azure\KeyVault\Data;

use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasAttributes;

class CertificatePolicyData
{
    use HasAttributes;

    public function __construct(public string $id,
                                public array $issuer = [],
                                public array $key_props = [],
                                public array $secret_props = [],
                                public array $x509_props = [],
                                public array $lifetime_actions = [],
                                public array $attributes = [])
    {
    }

    public static function fromResponseArray(array $response): self
    {
        return new self(
            id: $response['id'],
            issuer: $response['issuer'] ?? [],
            key_props: $response['key_props'] ?? [],
            secret_props: $response['secret_props'] ?? [],
            x509_props: $response['x509_props'] ?? [],
            lifetime_actions: $response['lifetime_actions'] ?? [],
            attributes: $response['attributes'] ?? []
        );
    }

    /**
     * @throws JsonException
     */
    public static function fromResponseJson(string $json): self
    {
        return self::fromResponseArray(\Safe\json_decode($json, true));
    }

}
