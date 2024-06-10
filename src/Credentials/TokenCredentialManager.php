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
                "driver" => getenv('AZURE_CREDENTIAL_DRIVER') ?: null,
                "identity_endpoint" => getenv('IDENTITY_ENDPOINT') ?: getenv('MSI_ENDPOINT') ?: null,
                "resource_id" => getenv('AZURE_RESOURCE_ID') ?: null,
                "token_endpoint" => getenv('AZURE_TOKEN_ENDPOINT') ?: null,
                "tenant_id" => getenv('AZURE_TENANT_ID') ?: null,
                "additionally_allowed_tenants" => getenv('AZURE_ADDITIONALLY_ALLOWED_TENANTS') ?: null,
                "subscription_id" => getenv('AZURE_SUBSCRIPTION_ID') ?: null,
                "client_id" => getenv('AZURE_CLIENT_ID') ?: null,
                "client_secret" => getenv('AZURE_CLIENT_SECRET') ?: null,
                "principal_id" => getenv('AZURE_PRINCIPAL_ID') ?: null,
                "mi_res_id" => getenv('AZURE_MI_RES_ID') ?: null,
                "username" => getenv('AZURE_USERNAME') ?: null,
                "password" => getenv('AZURE_PASSWORD') ?: null,
                "client_certificate_path" => getenv('AZURE_CLIENT_CERTIFICATE_PATH') ?: null,
                "client_certificate_password" => getenv('AZURE_CLIENT_CERTIFICATE_PASSWORD') ?: null,
                "send_certificate_chain" => getenv('AZURE_CLIENT_SEND_CERTIFICATE_CHAIN') ?: null,
                "az_path" => getenv('AZ_PATH') ?: null,
            ],
        ]);

        $this->defaultCacheConfig = $config->get('azure-identity.cache', fn() => [
            "prefix" => "azure_credentials:token:",
            "ttl" => "1 hour",
            "ttl_leeway" => "1 minute"
        ]);

        $this->cacheFactory = $cacheFactory ?? $this->container->make('cache');
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
            $config = $this->getCredentialConfig($credential);
            $this->credentials[$credential] = $this->createCredential($config);
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
     * @param array $config
     * @return TokenCredential
     */
    public function createCredential(array $config): TokenCredential
    {
        $cacheConfig = $config['cache'] ?? [];

        $cacheEnabled = $cacheConfig['enabled'] ?? $this->defaultCacheConfig['enabled'] ?? false;

        if($cacheEnabled) {
            $cacheStore = empty($cacheConfig['store']) ? $this->getDefaultCacheStore() : $cacheConfig['store'];

            $cachePrefix = empty($cacheConfig['prefix'])
                ? ($this->defaultCacheConfig['prefix'] ?? '') . ':'
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
