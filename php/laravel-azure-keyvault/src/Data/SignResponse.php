<?php

namespace Shrd\Laravel\Azure\KeyVault\Data;

use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;

readonly class SignResponse
{
    public function __construct(public KeyVaultKeyReference $keyReference,
                                public string $kid,
                                public string $value)
    {
    }

    public static function fromJsonResponse(KeyVaultKeyReference $keyReference, array $json): self
    {
        return new self(
            keyReference: $keyReference,
            kid: $json['kid'],
            value: $json['value']
        );
    }
}
