<?php

namespace Shrd\Laravel\Azure\Identity\Credentials;

use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

/**
 * A very simple token credential that stores it access tokens using only an in-memory array as its cache. It has
 * no dependencies on the laravel dependency injection system so that it can be used before the laravel config is
 * bootstrapped.
 *
 * This token credential is also preferable over the CacheTokenCredential if you are using it in a long term process
 * (like a worker instance of Horizon).
 */
class SimpleTokenCredential implements TokenCredential
{
    protected array $inMemoryCache = [];

    public function __construct(protected TokenCredentialDriver $driver)
    {
    }

    /**
     * @inheritDoc
     * @param AzureScope|array|string $scope
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function token(AzureScope|array|string $scope): AccessToken
    {
        $scope = AzureScope::from($scope);

        // Check in the in-memory cache if there are tokens that could be re-used.
        if(isset($this->inMemoryCache[$scope->getCacheKey()])) {
            $inMemoryToken = $this->inMemoryCache[$scope->getCacheKey()];
            $isValid = $inMemoryToken->expiresOn?->isFuture();
            if($isValid !== null) return $inMemoryToken;
        }

        // Fetch a new token.
        return $this->refreshToken($scope);
    }

    /**
     * @inheritDoc
     * @param AzureScope|array|string $scope
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function refreshToken(AzureScope|array|string $scope): AccessToken
    {
        $scope = AzureScope::from($scope);

        // Fetch the token using the driver.
        $token = $this->driver->fetchToken($scope);
        $this->setToken($scope, $token);
        return $token;
    }

    /**
     * Stores the provided access token in the session cache and optionally the long-term cache.
     *
     * @param AzureScope $scope The scope used to retrieve the token.
     * @param AccessToken $token The access token to store.
     * @return void
     */
    public function setToken(AzureScope $scope, AccessToken $token): void
    {
        $this->inMemoryCache[$scope->getCacheKey()] = $token;
    }

    /**
     * @inheritDoc
     * @param AzureScope|array|string $scope
     * @return void
     */
    public function forgetToken(AzureScope|array|string $scope): void
    {
        $scope = AzureScope::from($scope);

        $this->inMemoryCache[$scope->getCacheKey()] = $scope;
    }

    /**
     * @inheritDoc
     * @return TokenCredentialDriver
     */
    public function driver(): TokenCredentialDriver
    {
        return $this->driver;
    }
}
