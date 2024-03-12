<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Carbon\FactoryImmutable;
use DateInterval;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Ramsey\Uuid\Uuid;
use Safe\Exceptions\JsonException;
use Shrd\EncodingCombinators\Strings\ConstantTime\Base64Url;
use Shrd\Laravel\Azure\Identity\Contracts\ClientCertificate;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

class ClientCertificateCredentialDriver extends OAuthRequestCredentialDriver
{

    protected ClockInterface $clock;
    protected DateInterval $assertionLifetime;

    public function __construct(ClientInterface $httpClient,
                                RequestFactoryInterface $requestFactory,
                                StreamFactoryInterface $streamFactory,
                                protected ClientCertificate $certificate,
                                string $token_endpoint,
                                string $client_id,
                                ?ClockInterface $clock = null,
                                ?DateInterval $assertionLifetime = null)
    {
        $this->clock = $clock ?? new FactoryImmutable;
        $this->assertionLifetime = $assertionLifetime ?? new DateInterval('PT5M');

        parent::__construct(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            token_endpoint: $token_endpoint,
            grant_type: 'client_credentials',
            client_id: $client_id
        );
    }

    public static function forAzureTenant(ClientInterface $httpClient,
                                          RequestFactoryInterface $requestFactory,
                                          StreamFactoryInterface $streamFactory,
                                          ClientCertificate $certificate,
                                          string $tenant_id,
                                          string $client_id,
                                          ?ClockInterface $clock = null,
                                          ?DateInterval $assertionLifetime = null): self
    {
        return new self(
            httpClient: $httpClient,
            requestFactory: $requestFactory,
            streamFactory: $streamFactory,
            certificate: $certificate,
            token_endpoint: self::microsoftLoginTokenEndpoint($tenant_id),
            client_id: $client_id,
            clock: $clock,
            assertionLifetime: $assertionLifetime,
        );
    }

    /**
     * @throws JsonException
     */
    protected function createClientAssertion(): string
    {
        $thumbprint = $this->certificate->getX509Thumbprint();
        Base64Url::encodeNoPadding($thumbprint);

        $header = [
            "alg" => $this->certificate->algorithmId(),
            "typ" => "JWT",
            "x5t" => Base64Url::encodeNoPadding($thumbprint),
        ];
        $encodedHeader = Base64Url::encodeNoPadding(\Safe\json_encode($header));

        $now = $this->clock->now();
        $body = [
            "aud" => $this->token_endpoint,
            "exp" => $now->add($this->assertionLifetime)->getTimestamp(),
            "iat" => $now->getTimestamp(),
            "nbf" => $now->getTimestamp(),
            "jti" => Uuid::uuid4()->toString(),
            "iss" => $this->client_id,
            "sub" => $this->client_id
        ];
        $encodedBody = Base64Url::encodeNoPadding(\Safe\json_encode($body));

        $payload = "$encodedHeader.$encodedBody";

        $signature = $this->certificate->sign($payload);
        $encodedSignature = Base64Url::encodeNoPadding($signature);

        return "$payload.$encodedSignature";
    }

    /**
     * @throws JsonException
     */
    public function getTokenRequestParameters(AzureScope $scope): array
    {
        return [
            "client_assertion_type" => "urn:ietf:params:oauth:client-assertion-type:jwt-bearer",
            "client_assertion" => $this->createClientAssertion(),
        ];
    }
}
