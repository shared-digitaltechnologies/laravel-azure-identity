<?php

namespace Shrd\Laravel\Azure\Identity\Credentials;

use Carbon\CarbonInterval;
use Closure;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriverFactory;
use Shrd\Laravel\Azure\Identity\Drivers\AzureCliCredentialDriver;
use Shrd\Laravel\Azure\Identity\Drivers\ClientSecretCredentialDriverDriver;
use Shrd\Laravel\Azure\Identity\Drivers\ManagedIdentityCredentialDriver;
use Shrd\Laravel\Azure\Identity\Drivers\PasswordCredentialDriverDriver;

/**
 * Creates new token drivers and manages the created credentials.
 */
class TokenCredentialManager implements TokenCredentialDriverFactory
{
    /**
     * @var ConfigRepository
     */
    protected ConfigRepository $config;

    /**
     * @var CacheFactory
     */
    protected CacheFactory $cacheFactory;

    /**
     * @var array<string, Closure(Container $container): TokenCredentialDriver>
     */
    protected array $customCreators = [];

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
    public function __construct(protected Container $container,
                                ?ConfigRepository $config = null,
                                ?CacheFactory $cacheFactory = null)
    {
        $this->config = $config ?? $this->container->get('config');
        $this->cacheFactory = $cacheFactory ?? $this->container->get('cache');
    }


    /**
     * @inheritDoc
     */
    public function driver(?string $driver = null): TokenCredentialDriver
    {
        return $this->createDriver($driver ?? $this->getDefaultDriver());
    }

    /**
     * Gets the token credential based on the provided driver.
     *
     * @param string|null $driver
     * @return TokenCredential
     */
    public function credential(?string $driver = null): TokenCredential
    {
        $driver ??= $this->getDefaultDriver();

        if (!isset($this->credentials[$driver])) {
            $this->credentials[$driver] = $this->createCredential($driver);
        }

        return  $this->credentials[$driver];
    }

    /**
     * Gets the name of the default cache store.
     *
     * @return string|null
     */
    public function getDefaultCacheStore(): ?string
    {
        return $this->config->get('azure-credentials.cache.store');
    }

    /**
     * Gets the default cache ttl.
     *
     * @return CarbonInterval
     */
    public function getCacheTtlLeeway(): CarbonInterval
    {
        return CarbonInterval::fromString(
            $this->config->get('azure-credentials.cache.ttl_leeway', '1 minute')
        );
    }

    /**
     * Gets the default ttl in the long term cache for access tokens.
     *
     * @return CarbonInterval|null
     */
    public function getDefaultCacheTtl(): ?CarbonInterval
    {
        return CarbonInterval::make($this->config->get('azure-credentials.cache.ttl', '1 hour'));
    }

    /**
     * Creates a new token credential.
     *
     * @param string|null $driver
     * @param string|null $store
     * @return TokenCredential
     */
    public function createCredential(?string $driver = null, ?string $store = null): TokenCredential
    {
        return new CacheTokenCredential(
            driver: $this->driver($driver),
            cache: $this->cacheFactory->store($store ?? $this->getDefaultCacheStore()),
            cachePrefix: $this->config->get('azure-credentials.cache.prefix'),
            cacheTtlLeeway: $this->getCacheTtlLeeway(),
            defaultCacheTtl: $this->getDefaultCacheTtl(),
        );
    }

    /**
     * Gives the name of the default driver.
     *
     * @return string
     */
    public function getDefaultDriver(): string
    {
        return $this->config->get('azure-credentials.driver', 'default');
    }

    protected function callCustomCreator($driver)
    {
        return $this->customCreators[$driver]($this->container);
    }

    /**
     * Extends the TokenCredentialManager by adding some additional drivers.
     *
     * @param string $driver The name of the driver.
     * @param Closure(Container $app): TokenCredentialDriver $callback The constructor of the driver.
     * @return $this
     */
    public function extend(string $driver, Closure $callback): static
    {
        $this->customCreators[$driver] = $callback;
        return $this;
    }

    /**
     * Creates a new token credential driver instance
     *
     * @param string $driver
     * @return TokenCredentialDriver
     */
    protected function createDriver(string $driver): TokenCredentialDriver
    {
        // First, we will determine if a custom driver creator exists for the given driver and
        // if it does not we will check for a creator method for the driver. Custom creator
        // callbacks allow developers to build their own "drivers" easily using Closures.
        if (isset($this->customCreators[$driver])) {
            return $this->callCustomCreator($driver);
        }

        $method = 'create'.Str::studly($driver).'Driver';

        if (method_exists($this, $method)) {
            return $this->$method();
        }

        throw new InvalidArgumentException("Driver [$driver] not supported.");
    }

    /**
     * @throws BindingResolutionException
     * @noinspection PhpUnused
     */
    protected function createDefaultDriver(): TokenCredentialDriver
    {
        $config = $this->config->get('azure-credentials', []);

        if(isset($config['identity_endpoint']) && isset($config['identity_header'])) {
            return $this->createManagedIdentityDriver();
        }

        if(isset($config['tenant_id']) && isset($config['client_id'])) {

            // TODO: Client Certificate Driver

            if(isset($config['client_secret'])) return $this->createSecretDriver();

            if(isset($config['username']) && isset($config['password'])) return $this->createPasswordDriver();

        }

        return $this->createCliDriver();
    }

    protected function createCliDriver(): AzureCliCredentialDriver
    {
        return new AzureCliCredentialDriver(
            azPath: $this->config->get('azure-credentials.az_path', 'az'),
            subscriptionId: $this->config->get('azure-credentials.subscription_id'),
            tenantId: $this->config->get('azure-credentials.tenant_id'),
        );
    }

    /**
     * @throws BindingResolutionException
     */
    protected function createManagedIdentityDriver(): ManagedIdentityCredentialDriver
    {
        return ManagedIdentityCredentialDriver::forAppService(
            httpClient: $this->container->make(ClientInterface::class),
            requestFactory: $this->container->make(RequestFactoryInterface::class),
            identityEndpointValue: $this->config->get('azure-credentials.identity_endpoint'),
            identityHeaderValue: $this->config->get('azure-credentials.identity_header'),
            clientId: $this->config->get('azure-credentials.client_id'),
            resourceId: $this->config->get('azure-credentials.resource_id')
        );
    }

    /**
     * @throws BindingResolutionException
     */
    protected function createSecretDriver(): ClientSecretCredentialDriverDriver
    {
        $config = $this->config->get('azure-credentials', []);

        return ClientSecretCredentialDriverDriver::forAzureTenant(
            httpClient: $this->container->make(ClientInterface::class),
            requestFactory: $this->container->make(RequestFactoryInterface::class),
            streamFactory: $this->container->make(StreamFactoryInterface::class),
            tenant_id: $config['tenant_id'],
            client_id: $config['client_id'],
            client_secret: $config['client_secret']
        );
    }

    /**
     * @throws BindingResolutionException
     */
    protected function createPasswordDriver(): PasswordCredentialDriverDriver
    {
        $config = $this->config->get('azure-credentials', []);

        return PasswordCredentialDriverDriver::forAzureTenant(
            httpClient: $this->container->make(ClientInterface::class),
            requestFactory: $this->container->make(RequestFactoryInterface::class),
            streamFactory: $this->container->make(StreamFactoryInterface::class),
            tenant_id: $config['tenant_id'],
            client_id: $config['client_id'],
            username: $config['username'],
            password: $config['password'],
            client_secret: $config['client_secret'] ?? null
        );
    }
}
