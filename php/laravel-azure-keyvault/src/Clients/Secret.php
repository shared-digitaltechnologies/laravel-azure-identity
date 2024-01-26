<?php

namespace Shrd\Laravel\Azure\KeyVault\Clients;

use Illuminate\Http\Client\RequestException;
use Psr\SimpleCache\InvalidArgumentException;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\KeyVault\Data\SecretData;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;

/**
 * @extends EntityClient<KeyVaultSecretReference>
 * @method SecretData getData()
 */
class Secret extends EntityClient
{
    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws InvalidArgumentException
     * @throws JsonException
     */
    protected function fetchData(): SecretData
    {
        return $this->client->getSecretData($this->reference);
    }

    public function toString(): string
    {
        return $this->getData()->getStringValue();
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    public function getKeyReference(): ?KeyVaultKeyReference
    {
        return $this->getData()->getKeyReference();
    }

    public function getResolvedValue(): string|array
    {
        $data = $this->getData();
        if($data->isJson()) return $data->getArrayValue();
        // TODO: Add JWK case
        return $data->getStringValue();
    }

    public function getKey(): ?Key
    {
        $keyReference = $this->getKeyReference();
        if($keyReference === null) return null;
        return new Key($this->client, $keyReference);
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function sign(string $alg, string $digest): ?string
    {
        return $this->getKey()?->sign($alg, $digest);
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function verify(string $alg, string $digest, string $signature): ?bool
    {
        return $this->getKey()?->verify($alg, $digest, $signature);
    }
}
