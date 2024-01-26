<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialFailedException;
use Shrd\Laravel\Azure\Identity\Exceptions\InvalidAccessTokenJsonException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

class AzureCliCredentialDriver implements TokenCredentialDriver
{
    public function __construct(public string $azPath = 'az',
                                public ?string $subscriptionId = null,
                                public ?string $tenantId = null,
    )
    {}

    /**
     * @param AzureScope $scope
     * @param string|null $tenantId
     * @return AccessToken
     * @throws AzureCredentialException
     * @throws InvalidAccessTokenJsonException
     */
    function fetchToken(AzureScope $scope, string|null $tenantId = null): AccessToken
    {
        $resource = AzureScope::from($scope)->getResource();
        $tenantId ??= $this->tenantId;
        $tenantSection = $tenantId ? "--tenant $tenantId" : '';

        $subscriptionSection = $this->subscriptionId && !$tenantId ? "--subscription $this->subscriptionId" : '';

        $token = `$this->azPath account get-access-token --output json --resource $resource $tenantSection $subscriptionSection`;
        if(!$token) throw new AzureCredentialFailedException(
            credentialDriver: $this,
            message: 'az-cli failed'
        );

        return AccessToken::fromJsonString($token);
    }
}
