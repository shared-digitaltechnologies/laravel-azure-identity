<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Carbon\CarbonInterval;
use Carbon\FactoryImmutable;
use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Psr\Clock\ClockInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shrd\Laravel\Azure\Identity\Certificates\SimpleClientCertificateFactory;
use Shrd\Laravel\Azure\Identity\Contracts\ClientCertificateFactory;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;

class TokenCredentialDriverFactory
{
    public function __construct(protected ClientInterface $httpClient,
                                protected RequestFactoryInterface $requestFactory,
                                protected StreamFactoryInterface $streamFactory,
                                protected ClockInterface $clock,
                                protected ClientCertificateFactory $clientCertificateFactory)
    {
    }

    private static ?self $instance = null;

    public static function instance(): self
    {
        if(!self::$instance) {
            self::$instance = new self(
                httpClient: Psr18ClientDiscovery::find(),
                requestFactory: Psr17FactoryDiscovery::findRequestFactory(),
                streamFactory: Psr17FactoryDiscovery::findStreamFactory(),
                clock: new FactoryImmutable,
                clientCertificateFactory: new SimpleClientCertificateFactory,
            );
        }
        return self::$instance;
    }

    public function createDriver(array $config): TokenCredentialDriver
    {
        $driver = $config['driver'] ?? 'default';

        return match ($driver) {
            "password"         => $this->createPasswordDriver($config),
            "certificate"      => $this->createCertificateDriver($config),
            "secret"           => $this->createSecretDriver($config),
            "managed_identity" => $this->createManagedIdentityDriver($config),
            "cli"              => $this->createCliDriver($config),
            "empty"            => $this->createEmptyDriver(),
            "constant"         => $this->createConstantDriver($config),
            default            => $this->createDefaultDriver($config)
        };
    }

    protected static array $envMap = [
        "AZURE_CREDENTIAL_DRIVER" => "driver",
        "MSI_ENDPOINT" => "identity_endpoint",
        "IDENTITY_ENDPOINT" => "identity_endpoint",
        "AZURE_RESOURCE_ID" => "resource_id",
        "AZURE_TENANT_ID" => "tenant_id",
        "AZURE_ADDITIONALLY_ALLOWED_TENANTS" => "additionally_allowed_tenants",
        "AZURE_SUBSCRIPTION_ID" => "subscription_id",
        "AZURE_CLIENT_ID" => "client_id",
        "AZURE_CLIENT_SECRET" => "client_secret",
        "AZURE_USERNAME" => "username",
        "AZURE_PASSWORD" => "password",
        "AZURE_CLIENT_CERTIFICATE_PATH" => "client_certificate_path",
        "AZURE_CLIENT_CERTIFICATE_PASSWORD" => "client_certificate_password",
        "AZURE_CLIENT_SEND_CERTIFICATE_CHAIN" => "client_send_certificate_chain",
        "AZ_PATH" => "az_path",
    ];

    public function fromEnv(?array $envVariables = null): TokenCredentialDriver
    {
        $envVariables ??= $_ENV;

        $config = [];
        foreach (static::$envMap as $envKey => $configKey) {
            if(!isset($envVariables[$envKey])) continue;
            $value = trim($envVariables[$envKey]);
            if($value === '') continue;
            $config[$configKey] = $value;
        }

        return $this->createDriver($config);
    }

    /**
     * @noinspection PhpUnused
     */
    public function createDefaultDriver(array $config): TokenCredentialDriver
    {
        if(isset($config['identity_endpoint']) && isset($config['identity_header'])) {
            return $this->createManagedIdentityDriver($config);
        }

        if((isset($config['tenant_id']) || isset($config['token_endpoint'])) && isset($config['client_id'])) {

            if(isset($config['client_secret'])) return $this->createSecretDriver($config);

            if(isset($config['username']) && isset($config['password'])) return $this->createPasswordDriver($config);

            if(isset($config['client_certificate']) || isset($config['client_certificate_file']))
                return $this->createCertificateDriver($config);
        }

        return $this->createCliDriver($config);
    }

    public function createConstantDriver(array $config): ClosureCredentialDriver
    {
        return ClosureCredentialDriver::constant($config);
    }

    public function createEmptyDriver(): ClosureCredentialDriver
    {
        return ClosureCredentialDriver::empty();
    }

    public function createPasswordDriver(array $config): PasswordCredentialDriver
    {
        if(isset($config['token_endpoint'])) {
            return new PasswordCredentialDriver(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                token_endpoint: $config['token_endpoint'],
                client_id: $config['client_id'],
                username: $config['username'],
                password: $config['password'],
                client_secret: $config['client_secret'] ?? null
            );
        } else {
            return PasswordCredentialDriver::forAzureTenant(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                tenant_id: $config['tenant_id'],
                client_id: $config['client_id'],
                username: $config['username'],
                password: $config['password'],
                client_secret: $config['client_secret'] ?? null
            );
        }
    }

    public function createCertificateDriver(array $config): ClientCertificateCredentialDriver
    {
        $assertionLifetime = $config['client_assertion_ttl'] ?? '5 minutes';
        if(is_numeric($assertionLifetime)) $assertionLifetime = CarbonInterval::seconds($assertionLifetime);
        if(is_string($assertionLifetime)) $assertionLifetime = CarbonInterval::make($assertionLifetime);

        if(!empty($config['client_certificate_file'])) {
            $certificate = $this->clientCertificateFactory->createFromFile(
                $config['client_certificate_file'],
                $config['client_certificate_password'] ?? null
            );
        } else {
            $certificate = $this->clientCertificateFactory->create($config['client_certificate'] ?? []);
        }

        if(isset($config['token_endpoint'])) {
            return new ClientCertificateCredentialDriver(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                certificate: $certificate,
                token_endpoint: $config['token_endpoint'],
                client_id: $config['client_id'],
                clock: $this->clock,
                assertionLifetime: $assertionLifetime
            );
        } else {
            return ClientCertificateCredentialDriver::forAzureTenant(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                certificate: $certificate,
                tenant_id: $config['tenant_id'],
                client_id: $config['client_id'],
                clock: $this->clock,
                assertionLifetime: $assertionLifetime
            );
        }


    }

    public function createSecretDriver(array $config): ClientSecretCredentialDriver
    {

        if(isset($config['token_endpoint'])) {
            return new ClientSecretCredentialDriver(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                token_endpoint: $config['token_endpoint'],
                client_id: $config['client_id'],
                client_secret: $config['client_secret']
            );
        } else {
            return ClientSecretCredentialDriver::forAzureTenant(
                httpClient: $this->httpClient,
                requestFactory: $this->requestFactory,
                streamFactory: $this->streamFactory,
                tenant_id: $config['tenant_id'],
                client_id: $config['client_id'],
                client_secret: $config['client_secret']
            );
        }
    }

    public function createManagedIdentityDriver(array $config): ManagedIdentityCredentialDriver
    {
        return ManagedIdentityCredentialDriver::forAppService(
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            identityEndpointValue: $config['identity_endpoint'],
            identityHeaderValue: $config['identity_header'],
            clientId: $config['client_id'] ?? null,
            resourceId: $config['resource_id'] ?? null
        );
    }

    public function createCliDriver(array $config): AzureCliCredentialDriver
    {
        return new AzureCliCredentialDriver(
            azPath: $config['az_path'] ?? 'az',
            subscriptionId: $config['subscription_id'] ?? null,
            tenantId: $config['tenant_id'] ?? null,
        );
    }
}
