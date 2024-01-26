<?php

namespace Shrd\Laravel\Azure\Identity\Facades;

use Closure;
use Illuminate\Support\Facades\Facade;
use Shrd\Laravel\Azure\Identity\AzureCredentialService;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

/**
 * Facade for the AzureCredentialService
 *
 * @see AzureCredentialService
 * @method static AccessToken token(AzureScope|string|string[] $scope, ?string $driver = null)
 * @method static AccessToken refreshToken(AzureScope|string|string[] $scope, ?string $driver = null)
 * @method static void forgetToken(AzureScope|string|string[] $scope);
 * @method static AccessToken keyVaultToken(?string $driver = null)
 * @method static AccessToken webPubSubToken(?string $driver = null)
 * @method static AccessToken microsoftGraphToken(?string $driver = null)
 * @method static AccessToken storageAccountToken(?string $driver = null)
 * @method static AzureCredentialService extend(string $driver, Closure $callback);
 * @method static TokenCredentialDriver driver(?string $driver = null)
 * @method static TokenCredential credential(?string $driver = null)
 * @method static string getDefaultDriver()
 */
class AzureCredential extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AzureCredentialService::class;
    }
}
