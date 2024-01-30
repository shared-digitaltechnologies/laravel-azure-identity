<?php

namespace Shrd\Laravel\Azure\Identity\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            'Shrd\Laravel\Azure\Identity\ServiceProvider'
        ];
    }
}
