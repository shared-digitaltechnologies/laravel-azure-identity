<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

class PasswordCredentialDriverDriver extends OAuthRequestCredentialDriver
{
    public function __construct(ClientInterface $httpClient,
                                RequestFactoryInterface $requestFactory,
                                StreamFactoryInterface $streamFactory,
                                string $token_endpoint,
                                string $client_id,
                                public readonly string $username,
                                public readonly string $password,
                                public readonly string|null $client_secret = null)
    {
        parent::__construct(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            token_endpoint: $token_endpoint,
            grant_type: 'password',
            client_id: $client_id
        );
    }

    public static function forAzureTenant(ClientInterface $httpClient,
                                          RequestFactoryInterface $requestFactory,
                                          StreamFactoryInterface $streamFactory,
                                          string $tenant_id,
                                          string $client_id,
                                          string $username,
                                          string $password,
                                          string|null $client_secret = null): self
    {
        return new self(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            token_endpoint: self::microsoftLoginTokenEndpoint($tenant_id),
            client_id: $client_id,
            username: $username,
            password: $password,
            client_secret: $client_secret
        );
    }


    public function getTokenRequestParameters(AzureScope $scope): array
    {
        $parameters = [
            "username" => $this->username,
            "password" => $this->password,
        ];

        if($this->client_secret) $parameters['client_secret'] = $this->client_secret;

        return $parameters;
    }
}
