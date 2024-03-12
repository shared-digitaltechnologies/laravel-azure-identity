<?php

namespace Shrd\Laravel\Azure\Identity\Credentials;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\Container;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialFactory;
use Shrd\Laravel\Azure\Identity\Drivers\TokenCredentialDriverFactory as BaseDriverFactory;

/**
 * Creates new token drivers and manages the created credentials.
 */
class TokenCredentialManager implements TokenCredentialFactory
{
    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $config;

    /**
     * @var CacheFactory
     */
    protected CacheFactory $cacheFactory;

    protected BaseDriverFactory $baseDriverFactory;

    /**
     * @var array<string, Closure(Container $container, array $config): TokenCredentialDriver>
     */
    protected array $customDrivers = [];

    protected string $defaultDriver;
    protected string $defaultCredential;

    protected array $credentialConfigs;

    protected array $defaultCacheConfig;

    /**
     * The created token credential instances, keyed by the driver keys.
     *
     * @var array<string, TokenCredential>
     */
    protected array $credentials = [];

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function __construct(protected Container         $container,
                                ?ConfigRepository           $config = null,
                                ?CacheFactory               $cacheFactory = null)
    {
        $this->baseDriverFactory = $this->container->make(BaseDriverFactory::class);

        /** @var ConfigRepository $config */
        $config ??= $this->container->get('config');

        $this->defaultDriver = $config->get('azure-identity.driver', 'default');

        $this->defaultCredential = $config->get('azure-identity.credential', 'default');
        $this->credentialConfigs = $config->get('azure-identity.credentials', fn() => [
            "default" => [
                "driver" => env('AZURE_CREDENTIAL_DRIVER'),
                "identity_endpoint" => env('IDENTITY_ENDPOINT', env('MSI_ENDPOINT')),
                "resource_id" => env('AZURE_RESOURCE_ID'),
                "token_endpoint" => env('AZURE_TOKEN_ENDPOINT'),
                "tenant_id" => env('AZURE_TENANT_ID'),
                "additionally_allowed_tenants" => env('AZURE_ADDITIONALLY_ALLOWED_TENANTS'),
                "subscription_id" => env('AZURE_SUBSCRIPTION_ID'),
                "client_id" => env('AZURE_CLIENT_ID'),
                "client_secret" => env('AZURE_CLIENT_SECRET'),
                "username" => env('AZURE_USERNAME'),
                "password" => env('AZURE_PASSWORD'),
                "client_certificate_path" => env('AZURE_CLIENT_CERTIFICATE_PATH'),
                "client_certificate_password" => env('AZURE_CLIENT_CERTIFICATE_PASSWORD'),
                "send_certificate_chain" => env('AZURE_CLIENT_SEND_CERTIFICATE_CHAIN'),
                "az_path" => env('AZ_PATH')
            ],
        ]);

        $this->defaultCacheConfig = $config->get('azure-identity.cache', fn() => [
            "prefix" => "azure_credentials:token:",
            "ttl" => "1 hour",
            "ttl_leeway" => "1 minute"
        ]);

        $this->cacheFactory = $cacheFactory ?? $this->container->get('cache');
    }

    protected function getCredentialConfig(?string $credential = null): array
    {
        $credential ??= $this->defaultCredential;
        return $this->credentialConfigs[$credential] ?? [];
    }


    public function driver(?string $credential = null): TokenCredentialDriver
    {
        return $this->credential($credential)->driver();
    }

    /**
     * Gets the token credential based on the provided driver.
     *
     * @param string|null $credential
     * @return TokenCredential
     */
    public function credential(?string $credential = null): TokenCredential
    {
        $credential ??= $this->defaultCredential;

        if (!isset($this->credentials[$credential])) {
            $this->credentials[$credential] = $this->createCredential($credential);
        }

        return $this->credentials[$credential];
    }

    /**
     * Gets the name of the default cache store.
     *
     * @return string|null
     */
    public function getDefaultCacheStore(): ?string
    {
        return $this->defaultCacheConfig['store'] ?? null;
    }

    /**
     * Gets the default cache ttl.
     *
     * @return CarbonInterval
     */
    public function getCacheTtlLeeway(): CarbonInterval
    {
        return CarbonInterval::fromString($this->defaultCacheConfig['ttl_leeway'] ?? '1 minute');
    }

    /**
     * Gets the default ttl in the long term cache for access tokens.
     *
     * @return CarbonInterval|null
     */
    public function getDefaultCacheTtl(): ?CarbonInterval
    {
        return CarbonInterval::make($this->defaultCacheConfig['ttl'] ?? '1 hour');
    }

    /**
     * Creates a new token credential.
     *
     * @param string $credential
     * @return TokenCredential
     */
    public function createCredential(string $credential): TokenCredential
    {
        $config = $this->getCredentialConfig($credential);

        $cacheConfig = $config['cache'];

        $cacheEnabled = $cacheConfig['enabled'] ?? $this->defaultCacheConfig['enabled'] ?? false;

        if($cacheEnabled) {
            $cacheStore = empty($cacheConfig['store']) ? $this->getDefaultCacheStore() : $cacheConfig['store'];

            $cachePrefix = empty($cacheConfig['prefix'])
                ? ($this->defaultCacheConfig['prefix'] ?? '') . $credential . ':'
                : $cacheConfig['prefix'];

            $cacheTtlLeeway = empty($cacheConfig['ttl_leeway'])
                ? $this->getCacheTtlLeeway()
                : CarbonInterval::make($cacheConfig['ttl_leeway']);

            $cacheTtl = empty($cacheConfig['ttl'])
                ? $this->getDefaultCacheTtl()
                : CarbonInterval::make($cacheConfig['ttl']);

            return new CacheTokenCredential(
                driver: $this->createDriver($config),
                cache: $this->cacheFactory->store($cacheStore),
                cachePrefix: $cachePrefix,
                cacheTtlLeeway: $cacheTtlLeeway,
                defaultCacheTtl: $cacheTtl,
            );
        } else {
            return new SimpleTokenCredential(
                driver: $this->createDriver($config)
            );
        }
    }

    /**
     * Gives the name of the default driver.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->defaultDriver;
    }

    public function getDefaultCredential(): string
    {
        return $this->defaultCredential;
    }

    protected function callCustomCreator($driver, array $config): TokenCredentialDriver
    {
        return $this->customDrivers[$driver]($this->container, $config);
    }

    /**
     * Extends the TokenCredentialManager by adding some additional drivers.
     *
     * @param string $driver The name of the driver.
     * @param callable(Container $app, array $config): TokenCredentialDriver $callback The constructor of the driver.
     * @return $this
     */
    public function extend(string $driver, callable $callback): static
    {
        $this->customDrivers[$driver] = $callback(...);
        return $this;
    }

    /**
     * Creates a new token credential driver instance
     *
     * @param array $config
     * @return TokenCredentialDriver
     */
    public function createDriver(array $config): TokenCredentialDriver
    {
        $driver = $config['driver'] ?? $this->getDefaultDriver();

        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver, $config);
        }

        $config['driver'] = $driver;
        return $this->baseDriverFactory->createDriver($config);
    }
}
