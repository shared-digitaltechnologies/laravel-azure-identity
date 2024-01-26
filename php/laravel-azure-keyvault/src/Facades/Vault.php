<?php

namespace Shrd\Laravel\Azure\KeyVault\Facades;

use Illuminate\Support\Facades\Facade;
use Shrd\Laravel\Azure\KeyVault\Clients\Key;
use Shrd\Laravel\Azure\KeyVault\Clients\Certificate;
use Shrd\Laravel\Azure\KeyVault\Clients\Secret;
use Shrd\Laravel\Azure\KeyVault\Clients\KeyVaultClient;
use Shrd\Laravel\Azure\KeyVault\KeyVaultService;

/**
 * Access the Azure KeyVault.
 *
 * @method static KeyVaultClient client()
 * @method static mixed resolve($value)
 * @method static array resolveKeys(array &$values, array $keys)
 * @method static Key|Certificate|Secret get($reference)
 * @method static Key key($reference)
 * @method static Certificate certificate($reference)
 * @method static Secret secret($reference)
 */
class Vault extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return KeyVaultService::class;
    }
}
