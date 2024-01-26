<?php

namespace Shrd\Laravel\Azure\KeyVault;

use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Arr;
use InvalidArgumentException;
use Shrd\Laravel\Azure\Identity\AzureCredentialService;
use Shrd\Laravel\Azure\KeyVault\Clients\Certificate;
use Shrd\Laravel\Azure\KeyVault\Clients\Key;
use Shrd\Laravel\Azure\KeyVault\Clients\KeyVaultClient;
use Shrd\Laravel\Azure\KeyVault\Clients\Secret;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultCertificateReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;

class KeyVaultService
{
    protected ?KeyVaultClient $client = null;

    public function __construct(protected AzureCredentialService $credential,
                                protected ?ConfigRepository $config = null,
                                protected ?CacheFactory $cacheFactory = null)
    {
    }

    public function client(): KeyVaultClient
    {
        if(!$this->client) {
            $this->client = new KeyVaultClient(
                credential: $this->credential->credential($this->config?->get('azure-keyvault.credential_driver')),
                cache: $this->cacheFactory?->store($this->config?->get('azure-keyvault.cache.store')),
                cacheTTL: $this->config?->get('azure-keyvault.cache.ttl') ?? '1 hour',
                cachePrefix: $this->config?->get('azure-keyvault.cache.prefix') ?? 'azure_keyvault:',
            );
        }
        return $this->client;
    }

    public function resolve($value): mixed
    {
        if(KeyVaultReference::isKeyVaultReferenceString($value)) {
            $value = KeyVaultReference::from($value);
        }

        if($value instanceof KeyVaultReference) return $this->get($value)->getResolvedValue();
        return $value;
    }

    public function resolveKeys(array &$values, array $keys): array
    {
        foreach ($keys as $key) {
            if(Arr::has($values, $key)) {
                Arr::set($values, $key, $this->resolve(Arr::get($values, $key)));
            }
        }
        return $values;
    }

    public function get($reference): Certificate|Secret|Key
    {
        $reference = KeyVaultReference::from($reference);
        if($reference instanceof KeyVaultSecretReference) {
            return new Secret($this->client, $reference);
        } elseif ($reference instanceof KeyVaultKeyReference) {
            return new Key($this->client, $reference);
        } elseif ($reference instanceof KeyVaultCertificateReference) {
            return new Certificate($this->client, $reference);
        } else {
            throw new InvalidArgumentException(self::class."::get(".get_debug_type($reference).") not implemented.");
        }
    }

    public function certificate($reference): Certificate
    {
        $reference = KeyVaultCertificateReference::from($reference);
        return new Certificate($this->client, $reference);
    }

    public function secret($reference): Secret
    {
        $reference = KeyVaultReference::from($reference);
        if($reference instanceof KeyVaultSecretReference) {
            return new Secret($this->client, $reference);
        } elseif ($reference instanceof KeyVaultCertificateReference) {
            return $this->certificate($reference)->getSecret();
        } else {
            throw new InvalidArgumentException("Could not create a Secret-client from ".$reference->toReferenceString());
        }
    }

    public function key($reference): Key
    {
        $reference = KeyVaultReference::from($reference);
        if($reference instanceof KeyVaultKeyReference) {
            return new Key($this->client, $reference);
        } elseif ($reference instanceof KeyVaultSecretReference) {
            return $this->secret($reference)->getKey();
        } elseif ($reference instanceof KeyVaultCertificateReference) {
            return $this->certificate($reference)->getKey();
        } else {
            throw new InvalidArgumentException("Could not create a Key-client from ". $reference->toReferenceString());
        }
    }
}
