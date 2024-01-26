<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;

class TokenCredentialDriverFactory
{
    public function __construct(protected ClientInterface $httpClient,
                                protected RequestFactoryInterface $requestFactory,
                                protected StreamFactoryInterface $streamFactory)
    {
    }

    private static ?self $instance = null;

    public static function instance(): self
    {
        if(!self::$instance) {
            $httpFactory = new HttpFactory();
            self::$instance = new self(
                httpClient: new Client(),
                requestFactory: $httpFactory,
                streamFactory: $httpFactory
            );
        }
        return self::$instance;
    }


    public function createDriver(array $config): TokenCredentialDriver
    {
        $driver = $config['driver'] ?? 'default';

        return match ($driver) {
            "password" => $this->createPasswordDriver($config),
            "secret" => $this->createSecretDriver($config),
            "managed_identity" => $this->createManagedIdentityDriver($config),
            "cli" => $this->createCliDriver($config),
            default => $this->createDefaultDriver($config)
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
    protected function createDefaultDriver(array $config): TokenCredentialDriver
    {
        if(isset($config['identity_endpoint']) && isset($config['identity_header'])) {
            return $this->createManagedIdentityDriver($config);
        }

        if(isset($config['tenant_id']) && isset($config['client_id'])) {

            // TODO: Client Certificate Driver

            if(isset($config['client_secret'])) return $this->createSecretDriver($config);

            if(isset($config['username']) && isset($config['password'])) return $this->createPasswordDriver($config);

        }

        return $this->createCliDriver($config);
    }

    protected function createPasswordDriver(array $config): PasswordCredentialDriverDriver
    {
        return PasswordCredentialDriverDriver::forAzureTenant(
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

    protected function createSecretDriver(array $config): ClientSecretCredentialDriverDriver
    {
        return ClientSecretCredentialDriverDriver::forAzureTenant(
            httpClient: $this->httpClient,
            requestFactory: $this->requestFactory,
            streamFactory: $this->streamFactory,
            tenant_id: $config['tenant_id'],
            client_id: $config['client_id'],
            client_secret: $config['client_secret']
        );
    }

    protected function createManagedIdentityDriver(array $config): ManagedIdentityCredentialDriver
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

    protected function createCliDriver(array $config): AzureCliCredentialDriver
    {
        return new AzureCliCredentialDriver(
            azPath: $config['az_path'] ?? 'az',
            subscriptionId: $config['subscription_id'] ?? null,
            tenantId: $config['tenant_id'] ?? null,
        );
    }
}
