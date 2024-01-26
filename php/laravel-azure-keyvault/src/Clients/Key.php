<?php

namespace Shrd\Laravel\Azure\KeyVault\Clients;

use Illuminate\Http\Client\RequestException;
use Psr\SimpleCache\InvalidArgumentException;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\KeyVault\Data\CertificateData;
use Shrd\Laravel\Azure\KeyVault\Data\KeyData;
use Shrd\Laravel\Azure\KeyVault\Data\SecretData;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;

/**
 * @extends EntityClient<KeyVaultKeyReference>
 * @method KeyData getData()
 */
class Key extends EntityClient
{
    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws InvalidArgumentException
     */
    protected function fetchData(): KeyData|CertificateData|SecretData
    {
        return $this->client->getKeyData($this->reference);
    }

    public function getKeyReference(): KeyVaultKeyReference
    {
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return $this->reference;
    }

    public function getKey(): Key
    {
        return $this;
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function sign(string $alg, string $digest): string
    {
        return $this->client->sign($this->getKeyReference(), $alg, $digest)->value;
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function verify(string $alg, string $digest, string $signature): bool
    {
        return $this->client->verify($this->getKeyReference(), $alg, $digest, $signature);
    }

    protected function getResolvedValue(): mixed
    {
        //TODO: Should be JWK
        return $this->getData();
    }
}
