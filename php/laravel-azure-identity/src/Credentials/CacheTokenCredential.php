<?php

namespace Shrd\Laravel\Azure\Identity\Credentials;

use Carbon\CarbonInterval;
use DateInterval;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Psr\SimpleCache\InvalidArgumentException;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

/**
 * A token credential that can cache tokens in both an in-memory array and a long term cache.
 */
class CacheTokenCredential implements TokenCredential
{
    /**
     * An array of already fetched tokens. Useful for token re-use within one session.
     *
     * @var array<string, AccessToken>
     */
    protected array $inMemoryCache = [];

    /**
     * Gives the string prefixed to every cached token.
     *
     * @var string
     */
    public readonly string $cachePrefix;

    /**
     * ome extra leeway between the access token being invalidated in the cache and the time that the token
     * actually expires.
     *
     * @var DateInterval
     */
    public readonly DateInterval $cacheTtlLeeway;

    /**
     * The default time interval that an access token is allowed to live in the cache.
     *
     * This value is a fallback and should only be used if it could not be derived in any other way from the
     * token response.
     *
     * When `null`, it means that tokens that where expiresOn could not be derived will not be cached.
     *
     * @var DateInterval|null
     */
    public readonly ?DateInterval $defaultCacheTtl;

    /**
     * @param TokenCredentialDriver $driver
     * @param ?CacheRepository $cache
     * @param ?string $cachePrefix
     * @param DateInterval|null $cacheTtlLeeway
     * @param DateInterval|null $defaultCacheTtl
     */
    public function __construct(protected TokenCredentialDriver $driver,
                                protected ?CacheRepository $cache = null,
                                ?string $cachePrefix = null,
                                ?DateInterval $cacheTtlLeeway = null,
                                ?DateInterval $defaultCacheTtl = null)
    {
        $this->cachePrefix     = $cachePrefix ?? 'azure_credentials:token:';
        $this->cacheTtlLeeway  = $cacheTtlLeeway ?? CarbonInterval::minute();
        $this->defaultCacheTtl = $defaultCacheTtl;
    }

    /**
     * @inheritDoc
     * @throws InvalidArgumentException
     * @throws AzureCredentialException
     */
    public function token(AzureScope|array|string $scope): AccessToken
    {
        $scope = AzureScope::from($scope);

        // Check in the in-memory cache if there are tokens that could be re-used.
        if(isset($this->inMemoryCache[$scope->getCacheKey()])) {
            $inMemoryToken = $this->inMemoryCache[$scope->getCacheKey()];
            $isValid = $inMemoryToken->expiresOn?->sub($this->cacheTtlLeeway)->isFuture();
            if($isValid) return $inMemoryToken;
        }

        // Check in the cache if the token was already retrieved.
        $cachedToken = $this->cache->get($this->getCacheKey($scope));
        if($cachedToken !== null) return $cachedToken;

        // Fetch a new token.
        return $this->refreshToken($scope);
    }

    /**
     * @inheritDoc
     * @param AzureScope|array|string $scope The scope of the token to fetch
     * @param bool $cache Whether to store the token in the long-term cache.
     * @return AccessToken
     * @throws AzureCredentialException
     * @throws InvalidArgumentException
     */
    public function refreshToken(AzureScope|array|string $scope, ?bool $cache = true): AccessToken
    {
        $scope = AzureScope::from($scope);

        // Fetch the token using the driver.
        $token = $this->driver->fetchToken($scope);

        // Store the token in the cache.
        $this->setToken($scope, $token, $cache);

        return $token;
    }

    /**
     * Stores the provided access token in the session cache and optionally the long-term cache.
     *
     * @param AzureScope $scope The scope used to retrieve the token.
     * @param AccessToken $token The access token to store.
     * @param bool $cache Whether to store the token in the long-term cache.
     * @return void
     * @throws InvalidArgumentException
     */
    public function setToken(AzureScope $scope, AccessToken $token, ?bool $cache = false): void
    {
        $this->inMemoryCache[$scope->getCacheKey()] = $token;

        if(!$cache) return;

        $ttl = $token->expiresIn?->sub($this->cacheTtlLeeway) ?? $this->defaultCacheTtl;
        if(!$ttl) return;

        $this->cache?->set($this->getCacheKey($scope), serialize($scope), $ttl);
    }

    /**
     * @inheritDoc
     * @param AzureScope|string|array $scope The scope of the token to forget.
     * @param bool|null $cache Whether the token should also be removed from the long term cache.
     * @return void
     */
    public function forgetToken(AzureScope|string|array $scope, ?bool $cache = true): void
    {
        unset($this->inMemoryCache[$scope->getCacheKey()]);
        if($cache) $this->cache?->forget($this->getCacheKey($scope));
    }

    /**
     * Gives the underlying cache repository used to cache.
     *
     * @return ?CacheRepository
     */
    public function getCache(): ?CacheRepository
    {
        return $this->cache;
    }

    /**
     * Whether this credential has a long term cache.
     *
     * @return bool
     */
    public function hasCache(): bool
    {
        return $this->cache !== null;
    }

    /**
     * Returns the cache key under which a cached token with the given scope is cached.
     *
     * @param AzureScope $scope
     * @return string
     */
    public function getCacheKey(AzureScope $scope): string
    {
        return $this->cachePrefix.$scope->getCacheKey();
    }

    public function driver(): TokenCredentialDriver
    {
        return $this->driver;
    }
}
