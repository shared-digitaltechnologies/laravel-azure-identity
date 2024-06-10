<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

class ManagedIdentityCredentialDriver extends RequestCredentialDriver
{
    public const APP_SERVICE_MSI_API_VERSION = "2019-08-01";

    public function __construct(ClientInterface $httpClient,
                                public readonly RequestFactoryInterface $requestFactory,
                                public readonly string $endpoint,
                                public readonly string $apiVersion,
                                public readonly array $headers = [],
                                public readonly array $parameters = [])
    {
        parent::__construct($httpClient);
    }

    public static function forAppService(ClientInterface $httpClient,
                                         RequestFactoryInterface $requestFactory,
                                         string|null $identityEndpointValue = null,
                                         string|null $identityHeaderValue = null,
                                         string|null $clientId = null,
                                         string|null $resourceId = null,
                                         string|null $principalId = null): self
    {
        $parameters = [];
        if($clientId) $parameters['client_id'] = $clientId;
        elseif($resourceId) $parameters['mi_res_id'] = $resourceId;
        elseif($principalId) $parameters['principal_id'] = $principalId;

        return new self(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            endpoint: $identityEndpointValue ?? $_ENV['IDENTITY_ENDPOINT'],
            apiVersion: self::APP_SERVICE_MSI_API_VERSION,
            headers: [
                "X-IDENTITY-HEADER" => $identityHeaderValue ?? $_ENV['IDENTITY_HEADER']
            ],
            parameters: $parameters
        );
    }

    public function getTokenUrl(AzureScope $scope): string
    {
        return "$this->endpoint?".http_build_query([
            "resource" => $scope->getResource(),
            "api-version" => $this->apiVersion,
            ...$this->parameters
        ]);
    }

    protected function getTokenRequest(AzureScope $scope): RequestInterface
    {
        $request = $this->requestFactory
            ->createRequest('GET', $this->getTokenUrl($scope))
            ->withHeader('Accept', 'application/json');

        foreach ($this->headers as $header => $value) {
            $request = $request->withHeader($header, $value);
        }

        return $request;
    }
}
