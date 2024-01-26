<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

interface TokenCredential
{
    /**
     * Gets the access token of the provided token.
     *
     * This method can fetch a new token or re-use a previously cached token.
     *
     * @param AzureScope|string|array $scope
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function token(AzureScope|string|array $scope): AccessToken;

    /**
     * Fetches a fresh token using the underlying token driver.
     *
     * This method may cache this new token in its underlying cache.
     *
     * @param AzureScope|string|array $scope
     * @return AccessToken
     * @throws AzureCredentialException
     */
    public function refreshToken(AzureScope|string|array $scope): AccessToken;

    /**
     * Forgets the token with the provided scope. Ensures that `token($scope)` will always fetch a new token the
     * next time it is called.
     *
     * @param AzureScope|string|array $scope
     * @return void
     */
    public function forgetToken(AzureScope|string|array $scope): void;

    /**
     * Returns the underlying driver of this token credential used to fetch new tokens.
     *
     * @return TokenCredentialDriver
     */
    public function driver(): TokenCredentialDriver;
}
