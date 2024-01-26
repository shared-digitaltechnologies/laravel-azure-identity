<?php

namespace Shrd\Laravel\Azure\Identity;

use Closure;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Credentials\TokenCredentialManager;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

class AzureCredentialService implements TokenCredential
{
    public function __construct(protected TokenCredentialManager $manager)
    {
    }

    public function extend(string $driver, Closure $closure): static
    {
        $this->manager->extend($driver, $closure);
        return $this;
    }

    public function credential(?string $driver = null): TokenCredential
    {
        return $this->manager->credential($driver);
    }

    public function getDefaultDriver(): string
    {
        return $this->manager->getDefaultDriver();
    }

    /**
     * Fetches a new access token with the provided scopes.
     *
     * This method wil ALWAYS fetch a new token, even if a similar one that is valid already exists in the cache!
     *
     * @param AzureScope|string|array $scope
     * @param string|null $driver The driver used to fetch the token.
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function refreshToken(AzureScope|string|array $scope, ?string $driver = null): AccessToken
    {
        return $this->manager->credential($driver)->refreshToken($scope);
    }

    /**
     * Gets an access token for the provided scope.
     *
     * This method first tries to find the token in the token cache. If it does not exist, it will fetch a new token.
     *
     * @param AzureScope|string|array $scope
     * @param string|null $driver
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function token(AzureScope|string|array $scope, ?string $driver = null): AccessToken
    {
        return $this->manager->credential($driver)->token($scope);
    }

    /**
     * Gets an access token for Azure KeyVault.
     *
     * @param string|null $driver
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function keyVaultToken(?string $driver = null): AccessToken
    {
        return $this->token(AzureScope::keyVault(), driver: $driver);
    }

    /**
     * Gets an access token for Azure PubSub.
     *
     * @param string|null $driver
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function webPubSubToken(?string $driver = null): AccessToken
    {
        return $this->token(AzureScope::webPubSub(), driver: $driver);
    }

    /**
     * Gets an access token for the microsoft graph API.
     *
     * @throws AzureCredentialException
     */
    public function microsoftGraphToken(?string $driver = null): AccessToken
    {
        return $this->token(AzureScope::microsoftGraph(), driver: $driver);
    }

    /**
     * Gets an access token for the azure accounts api.
     *
     * @throws AzureCredentialException
     */
    public function storageAccountToken(?string $driver = null): AccessToken
    {
        return $this->token(AzureScope::storageAccount(), driver: $driver);
    }

    public function forgetToken(AzureScope|array|string $scope, ?string $driver = null): void
    {
        $this->manager->credential($driver)->forgetToken($scope);
    }

    public function driver(?string $driver = null): TokenCredentialDriver
    {
        return $this->manager->credential($driver)->driver();
    }
}
