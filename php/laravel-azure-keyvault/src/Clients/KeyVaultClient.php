<?php

namespace Shrd\Laravel\Azure\KeyVault\Clients;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use ParagonIE\ConstantTime\Base64UrlSafe;
use Psr\Http\Message\UriInterface;
use Psr\SimpleCache\InvalidArgumentException;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;
use Shrd\Laravel\Azure\KeyVault\Data\CertificateData;
use Shrd\Laravel\Azure\KeyVault\Data\KeyData;
use Shrd\Laravel\Azure\KeyVault\Data\SecretData;
use Shrd\Laravel\Azure\KeyVault\Data\SignResponse;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultCertificateReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;

class KeyVaultClient
{
    const API_VERSION = '7.4';

    /**
     * In memory key data cache.
     *
     * @var array<string, KeyData>
     */
    private array $keys = [];

    /**
     * In memory secret data cache.
     *
     * @var array<string, SecretData>
     */
    private array $secrets = [];

    /**
     * In memory certificate data cache
     *
     * @var array<string, CertificateData>
     */
    private array $certificates = [];

    protected CarbonInterval $cacheTTL;

    public function __construct(protected TokenCredential $credential,
                                protected ?CacheRepository $cache = null,
                                mixed $cacheTTL = null,
                                protected string $cachePrefix = 'azure-keyvault:')
    {
        $this->cacheTTL = CarbonInterval::make($cacheTTL) ?? CarbonInterval::minutes(10);
    }

    public function getCacheTTL(): CarbonInterval
    {
        return $this->cacheTTL;
    }

    public function setCacheTTL($ttl): static
    {
        $ttl = CarbonInterval::make($ttl);
        if($ttl !== null) {
            $this->cacheTTL = $ttl;
        }
        return $this;
    }

    /**
     * @throws AzureCredentialException
     */
    private function getToken(): AccessToken
    {
        return $this->credential->token(AzureScope::keyVault());
    }

    /**
     * @throws AzureCredentialException
     */
    public function request(): PendingRequest
    {
        $token = $this->getToken();
        return Http::withToken($token->accessToken, $token->tokenType ?? 'Bearer')
            ->withQueryParameters([
                "api-version" => self::API_VERSION,
            ]);
    }

    protected function getQualifiedSecretCacheKey(string $cacheKey): string
    {
        return "{$this->cachePrefix}secrets:$cacheKey:data";
    }

    protected function getQualifiedKeyCacheKey(string $cacheKey): string
    {
        return "{$this->cachePrefix}keys:$cacheKey:data";
    }

    protected function getQualifiedCertificateCacheKey(string $cacheKey): string
    {
        return "{$this->cachePrefix}certificates:$cacheKey:data";
    }

    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function fetchSecretData(string|UriInterface|array|KeyVaultSecretReference $reference): SecretData
    {
        $reference = KeyVaultSecretReference::from($reference);

        $response = $this->request()->get($reference->uri)->throw();
        $data = SecretData::fromResponseArray($response->json());

        // Store data in caches
        $cacheKey = $reference->getCacheKey();
        $this->secrets[$cacheKey] = $data;
        $this->cache?->set($this->getQualifiedSecretCacheKey($cacheKey), $data, $this->getCacheTTL());

        return $data;
    }

    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws JsonException
     * @throws InvalidArgumentException
     */
    public function getSecretData(string|UriInterface|array|KeyVaultSecretReference $reference): SecretData
    {
        $reference = KeyVaultSecretReference::from($reference);
        $cacheKey = $reference->getCacheKey();

        if(array_key_exists($cacheKey, $this->secrets)) return $this->secrets[$cacheKey];

        $cacheData = $this->cache?->get($this->getQualifiedSecretCacheKey($cacheKey));
        if($cacheData !== null) {
            $this->secrets[$cacheKey] = $cacheData;
            return $cacheData;
        }

        return $this->fetchSecretData($reference);
    }


    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws InvalidArgumentException
     */
    public function fetchKeyData(string|UriInterface|array|KeyVaultKeyReference $reference): KeyData
    {
        $reference = KeyVaultKeyReference::from($reference);
        $response = $this->request()->get($reference->uri)->throw();
        $data = KeyData::fromResponseArray($response->json());

        // Store data in caches
        $cacheKey = $reference->getCacheKey();
        $this->keys[$cacheKey] = $data;
        $this->cache?->set($this->getQualifiedKeyCacheKey($cacheKey), $data, $this->getCacheTTL());

        return $data;
    }


    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws InvalidArgumentException
     */
    public function getKeyData(string|UriInterface|array|KeyVaultKeyReference $reference): KeyData
    {
        $reference = KeyVaultKeyReference::from($reference);
        $cacheKey = $reference->getCacheKey();

        // Check the in-memory cache.
        if(array_key_exists($cacheKey, $this->keys)) return $this->keys[$cacheKey];

        // Check the long-lived cache.
        $cacheData = $this->cache?->get($this->getQualifiedCertificateCacheKey($cacheKey));
        if($cacheData !== null) {
            $this->keys[$cacheKey] = $cacheData;
            return $cacheData;
        }

        return $this->fetchKeyData($reference);
    }

    /**
     * @throws AzureCredentialException
     * @throws RequestException
     * @throws InvalidArgumentException
     */
    public function fetchCertificateData(string|UriInterface|array|KeyVaultCertificateReference $reference): CertificateData
    {
        $reference = KeyVaultCertificateReference::from($reference);
        $response = $this->request()->get($reference->uri)->throw();
        $data = CertificateData::fromResponseArray($response->json());

        // Store data in caches.
        $cacheKey = $reference->getCacheKey();
        $this->certificates[$cacheKey] = $data;
        $this->cache?->set($this->getQualifiedCertificateCacheKey($cacheKey), $data, $this->getCacheTTL());

        return $data;
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     * @throws InvalidArgumentException
     */
    public function getCertificateData(string|UriInterface|array|KeyVaultCertificateReference $reference): CertificateData
    {
        $reference = KeyVaultCertificateReference::from($reference);
        $cacheKey = $reference->getCacheKey();

        if(array_key_exists($cacheKey, $this->certificates)) return $this->certificates[$cacheKey];
        $cacheData = $this->cache?->get($this->getQualifiedCertificateCacheKey($cacheKey));
        if($cacheData !== null) {
            $this->certificates[$cacheKey] = $cacheData;
            return $cacheData;
        }

        return $this->fetchCertificateData($reference);
    }

    /**
     * @throws AzureCredentialException
     * @throws RequestException
     */
    public function sign(KeyVaultKeyReference $reference, string $alg, string $digest): SignResponse
    {
        $value = Base64UrlSafe::encodeUnpadded(hash('sha256', $digest, true));
        $response = $this->request()->post($reference->getSignUri(), [
            "alg" => $alg,
            "value" => $value,
        ]);

        $response->throw();
        return SignResponse::fromJsonResponse($reference, $response->json());
    }

    /**
     * @throws RequestException
     * @throws AzureCredentialException
     */
    public function verify(KeyVaultKeyReference $reference,
                           string               $alg,
                           string               $digest,
                           string               $signature): bool
    {
        $digest = Base64UrlSafe::encodeUnpadded(hash('sha256', $digest, true));

        $response = $this->request()->post($reference->getVerifyUri(), [
            "alg" => $alg,
            "digest" => $digest,
            "signature" => $signature
        ])->throw();

        return boolval($response->json('value'));
    }
}
