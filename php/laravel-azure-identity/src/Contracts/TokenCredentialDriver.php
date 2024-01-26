<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

interface TokenCredentialDriver
{
    /**
     * Fetches a new token from Azure.
     *
     * This method should not implement any caching behaviour by itself. The caching behaviour should be left to the
     * user of this method.
     *
     * @param AzureScope $scope
     * @return AccessToken
     * @throws AzureCredentialException
     */
    function fetchToken(AzureScope $scope): AccessToken;
}
