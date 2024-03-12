<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

class ClientSecretCredentialDriver extends OAuthRequestCredentialDriver
{
    public function __construct(ClientInterface $httpClient,
                                RequestFactoryInterface $requestFactory,
                                StreamFactoryInterface $streamFactory,
                                string $token_endpoint,
                                string $client_id,
                                public readonly string $client_secret)
    {
        parent::__construct(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            token_endpoint: $token_endpoint,
            grant_type: 'client_credentials',
            client_id: $client_id,
        );
    }

    public static function forAzureTenant(ClientInterface $httpClient,
                                          RequestFactoryInterface $requestFactory,
                                          StreamFactoryInterface $streamFactory,
                                          string $tenant_id,
                                          string $client_id,
                                          string $client_secret): self
    {
        return new self(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            token_endpoint: self::microsoftLoginTokenEndpoint($tenant_id),
            client_id: $client_id,
            client_secret: $client_secret
        );
    }

    public function getTokenRequestParameters(AzureScope $scope): array
    {
        return [
            "client_secret" => $this->client_secret
        ];
    }
}
