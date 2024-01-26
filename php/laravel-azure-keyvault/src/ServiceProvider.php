<?php

namespace Shrd\Laravel\Azure\KeyVault;

use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeyVaultService::class);
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/azure-keyvault.php' => config_path('azure-keyvault.php')
        ]);

        if ($this->app->runningInConsole()) {
            $this->commands([
                Console\Commands\ResolveCommand::class
            ]);
        }
    }
}
