<?php

namespace Shrd\Laravel\Azure\Identity;

use Illuminate\Contracts\Container\Container;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredential;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriverFactory;
use Shrd\Laravel\Azure\Identity\Credentials\TokenCredentialManager;

class ServiceProvider extends BaseServiceProvider {

    public function register(): void
    {
        $this->app->singleton(TokenCredentialManager::class);
        $this->app->bind(TokenCredentialDriverFactory::class, TokenCredentialManager::class);

        $this->app->singleton(AzureCredentialService::class);

        $this->app->bind(TokenCredentialDriver::class, function (Container $app) {
            return $app[AzureCredentialService::class]->driver();
        });

        $this->app->bind(TokenCredential::class, function (Container $app) {
            return $app[AzureCredentialService::class]->credential();
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/azure-identity.php' => config_path('azure-identity.php')
        ]);

        if($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\TokenCommand::class
            ]);
        }
    }
}
