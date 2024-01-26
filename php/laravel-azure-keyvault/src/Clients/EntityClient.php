<?php

namespace Shrd\Laravel\Azure\KeyVault\Clients;

use Illuminate\Http\Client\PendingRequest;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\KeyVault\Data\CertificateData;
use Shrd\Laravel\Azure\KeyVault\Data\KeyData;
use Shrd\Laravel\Azure\KeyVault\Data\SecretData;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultReference;

/**
 * @template TReference of KeyVaultReference
 */
abstract class EntityClient
{
    protected KeyData|CertificateData|SecretData|null $data = null;

    /**
     * @param KeyVaultClient $client
     * @param KeyVaultReference&TReference $reference
     */
    public function __construct(protected KeyVaultClient $client,
                                protected KeyVaultReference $reference)
    {
    }

    /**
     * @throws AzureCredentialException
     */
    public function request(): PendingRequest
    {
        return $this->client->request();
    }

    public final function getClient(): KeyVaultClient
    {
        return $this->client;
    }

    /**
     * @return KeyVaultReference&TReference
     */
    public final function getReference(): KeyVaultReference
    {
        return $this->reference;
    }

    protected abstract function fetchData(): KeyData|CertificateData|SecretData;

    protected abstract function getKeyReference(): ?KeyVaultKeyReference;

    protected abstract function getKey(): ?Key;

    protected abstract function getResolvedValue(): mixed;

    // TODO: createJWK
    // TODO: getJWK
    // TODO: getJWKs

    public final function getData(): KeyData|CertificateData|SecretData
    {
        if($this->data === null) $this->data = $this->fetchData();
        return $this->data;
    }
}
