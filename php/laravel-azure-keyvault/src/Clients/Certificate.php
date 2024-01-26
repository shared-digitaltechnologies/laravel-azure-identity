<?php

namespace Shrd\Laravel\Azure\KeyVault\Clients;

use Illuminate\Http\Client\RequestException;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\KeyVault\Data\CertificateData;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultCertificateReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;

/**
 * @extends EntityClient<KeyVaultCertificateReference>
 * @method CertificateData getData()
 */
class Certificate extends EntityClient
{
    protected function fetchData(): CertificateData
    {
        return $this->client->getCertificateData($this->reference);
    }

    public function getSecretReference(): ?KeyVaultSecretReference
    {
        return $this->getData()->getSecretReference();
    }

    public function getSecret(): ?Secret
    {
        $secretReference = $this->getSecretReference();
        if(!$secretReference) return null;
        return new Secret($this->client, $secretReference);
    }

    public function getKeyReference(): ?KeyVaultKeyReference
    {
        return $this->getData()->getKeyReference();
    }

    public function getKey(): ?Key
    {
        $keyReference = $this->getKeyReference();
        if($keyReference === null) return null;
        return new Key($this->client, $keyReference);
    }

    // TODO: CertificateTokenBuilder

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function sign(string $alg, string $digest): ?string
    {
       return $this->getKey()?->sign($alg, $digest);
    }

    /**
     * @throws AzureCredentialException
     * @throws RequestException
     */
    public function verify(string $alg, string $digest, string $signature): ?bool
    {
        return $this->getKey()?->verify($alg, $digest, $signature);
    }

    protected function getResolvedValue(): CertificateData
    {
        // TODO: Should be a JWK
        return $this->getData();
    }
}
