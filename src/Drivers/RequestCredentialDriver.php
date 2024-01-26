<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialRequestException;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialResponseException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

abstract class RequestCredentialDriver implements TokenCredentialDriver
{
    public function __construct(protected readonly ClientInterface $httpClient)
    {

    }

    protected abstract function getTokenRequest(AzureScope $scope): RequestInterface;

    public function fetchToken(AzureScope $scope): AccessToken
    {
        $request = $this->getTokenRequest($scope);

        try {
            $response = $this->httpClient->sendRequest($this->getTokenRequest($scope));
        } catch (ClientExceptionInterface $exception) {
            throw new AzureCredentialRequestException(
                credentialDriver: $this,
                scope: $scope,
                request: $request,
                previous: $exception
            );
        }

        $statusCode = $response->getStatusCode();
        if($statusCode < 200 || $statusCode >= 400) {
            throw new AzureCredentialResponseException(
                credentialDriver: $this,
                scope: $scope,
                request: $request,
                response: $response,
            );
        }

        return AccessToken::from($response);
    }
}
