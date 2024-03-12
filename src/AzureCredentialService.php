<?php

namespace Shrd\Laravel\Azure\Identity;

use Closure;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialFactory;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

class AzureCredentialService implements TokenCredential
{
    public function __construct(protected TokenCredentialFactory $factory)
    {
    }

    public function extend(string $driver, Closure $closure): static
    {
        $this->factory->extend($driver, $closure);
        return $this;
    }

    public function credential(?string $credential = null): TokenCredential
    {
        return $this->factory->credential($credential);
    }

    public function getDefaultCredential(): string
    {
        return $this->factory->getDefaultCredential();
    }

    public function createCredential(array $config): TokenCredential
    {
        return $this->factory->createCredential($config);
    }

    /**
     * Fetches a new access token with the provided scopes.
     *
     * This method wil ALWAYS fetch a new token, even if a similar one that is valid already exists in the cache!
     *
     * @param AzureScope|string|array $scope
     * @param string|null $credential The credential used to fetch the token.
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function refreshToken(AzureScope|string|array $scope, ?string $credential = null): AccessToken
    {
        return $this->credential($credential)->refreshToken($scope);
    }

    /**
     * Gets an access token for the provided scope.
     *
     * This method first tries to find the token in the token cache. If it does not exist, it will fetch a new token.
     *
     * @param AzureScope|string|array $scope
     * @param string|null $credential
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function token(AzureScope|string|array $scope, ?string $credential = null): AccessToken
    {
        return $this->credential($credential)->token($scope);
    }

    /**
     * Gets an access token for Azure KeyVault.
     *
     * @param string|null $credential
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function keyVaultToken(?string $credential = null): AccessToken
    {
        return $this->token(AzureScope::keyVault(), credential: $credential);
    }

    /**
     * Gets an access token for Azure PubSub.
     *
     * @param string|null $credential
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function webPubSubToken(?string $credential = null): AccessToken
    {
        return $this->token(AzureScope::webPubSub(), credential: $credential);
    }

    /**
     * Gets an access token for the microsoft graph API.
     *
     * @throws AzureCredentialException
     */
    public function microsoftGraphToken(?string $credential = null): AccessToken
    {
        return $this->token(AzureScope::microsoftGraph(), credential: $credential);
    }

    /**
     * Gets an access token for the azure accounts api.
     *
     * @throws AzureCredentialException
     */
    public function storageAccountToken(?string $credential = null): AccessToken
    {
        return $this->token(AzureScope::storageAccount(), credential: $credential);
    }

    public function forgetToken(AzureScope|array|string $scope, ?string $credential = null): void
    {
        $this->credential($credential)->forgetToken($scope);
    }

    public function driver(?string $credential = null): TokenCredentialDriver
    {
        return $this->credential($credential)->driver();
    }

    public function getDefaultDriver(): string
    {
        return $this->factory->getDefaultDriver();
    }

    public function createDriver(array $config): TokenCredentialDriver
    {
        return $this->factory->createDriver($config);
    }
}
