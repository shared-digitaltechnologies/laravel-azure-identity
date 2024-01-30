<?php

namespace Shrd\Laravel\Azure\Identity\Tests\Feature\Credentials;

use Carbon\CarbonInterval;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Psr\SimpleCache\InvalidArgumentException;
use Shrd\Laravel\Azure\Identity\Credentials\CacheTokenCredential;
use Shrd\Laravel\Azure\Identity\Drivers\ClosureCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tests\TestCase;

class CacheTokenCredentialTest extends TestCase
{
    /**
     * @throws AzureCredentialException
     * @throws InvalidArgumentException
     */
    public function test_token_cached()
    {
        $testToken = 'AAAA.AAAA.AAAA';
        $testScope = AzureScope::fromUri('https://example.com');

        $constDriver = ClosureCredentialDriver::constant($testToken);
        $emptyDriver = ClosureCredentialDriver::empty();


        /** @var CacheFactory $cacheFactory */
        $cacheFactory = $this->app->make(CacheFactory::class);
        $cache = $cacheFactory->store();

        $constCred = new CacheTokenCredential(
            driver: $constDriver,
            cache: $cache,
            defaultCacheTtl: CarbonInterval::day()
        );

        $emptyCred = new CacheTokenCredential(
            driver: $emptyDriver,
            cache: $cache
        );

        $tokenA = $constCred->token($testScope);
        $tokenB = $emptyCred->token($testScope);

        $this->assertEquals($testToken, $tokenA->accessToken);
        $this->assertEquals($testToken, $tokenB->accessToken);
    }
}
