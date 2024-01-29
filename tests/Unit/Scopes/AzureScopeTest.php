<?php

namespace Shrd\Laravel\Azure\Identity\Tests\Unit\Scopes;


use PHPUnit\Framework\TestCase;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

class AzureScopeTest extends TestCase
{
    public function test_getCacheKey_one_scope() {
        $scope = AzureScope::from('https://keyvault.azure.net/.default');

        $this->assertEquals('keyvault.azure.net', $scope->getCacheKey());
    }

    public function test_getCacheKey_two_scopes() {
        $scope = AzureScope::from(['https://webpubsub.azure.net/.default', 'https://keyvault.azure.net/.default']);

        $this->assertEquals('keyvault.azure.net+webpubsub.azure.net', $scope->getCacheKey());
    }
}
