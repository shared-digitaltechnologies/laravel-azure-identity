<?php

namespace Shrd\Laravel\Azure\MicrosoftGraphMailer\Tests;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $loadEnvironmentVariables = false;

    /**
     * @param Application $app
     * @return array<int, ServiceProvider>
     */
    protected function getPackageProviders($app): array
    {
        return [
            'Shrd\Laravel\Azure\Credentials\ServiceProvider',
            'Shrd\Laravel\Azure\MicrosoftGraphMailer\ServiceProvider',
        ];
    }

    /**
     * @param Application $app
     * @return void
     */
    protected function defineEnvironment($app): void
    {
       tap($app['config'], function (ConfigRepository $config) {

       });
    }
}
