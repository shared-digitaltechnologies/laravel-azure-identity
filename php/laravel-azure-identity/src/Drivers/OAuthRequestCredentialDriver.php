<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

abstract class OAuthRequestCredentialDriver extends RequestCredentialDriver
{
    public function __construct(ClientInterface $httpClient,
                                protected readonly RequestFactoryInterface $requestFactory,
                                protected readonly StreamFactoryInterface $streamFactory,
                                protected readonly string $token_endpoint,
                                protected readonly string $grant_type,
                                protected readonly string $client_id)
    {
        parent::__construct($httpClient);
    }

    public function getTokenEndpoint(): string
    {
        return $this->token_endpoint;
    }

    public static function microsoftLoginTokenEndpoint(string $tenant_id): string
    {
        return "https://login.microsoftonline.com/$tenant_id/oauth2/v2.0/token";
    }

    public abstract function getTokenRequestParameters(AzureScope $scope): array;

    public function getTokenRequestBody(AzureScope $scope): StreamInterface
    {
        $parameters = [
            "client_id" => $this->client_id,
            "grant_type" => $this->grant_type,
            "scope" => $scope->toString(),
            ...$this->getTokenRequestParameters($scope)
        ];

        $pairs = [];
        foreach ($parameters as $parameter => $value) {
            if($value === null) continue;
            $pairs[] = "$parameter=".urlencode($value);
        }
        $body = implode('&', $pairs);

        return $this->streamFactory->createStream($body);
    }

    public function getTokenRequest(AzureScope $scope): RequestInterface
    {
        return $this->requestFactory
            ->createRequest('POST', $this->getTokenEndpoint())
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withBody($this->getTokenRequestBody($scope));
    }
}
