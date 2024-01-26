<?php

namespace Shrd\Laravel\Azure\MicrosoftGraphMailer;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Shrd\Laravel\Azure\Credentials\AzureCredentialService;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        Mail::extend('microsoft-graph', function (array $config) {
            return new MicrosoftGraphTransport(
                credential: $this->app->get(AzureCredentialService::class)->credential($config['credential']),
                user: $config['user'] ?? null,
                saveToSentItems: boolval($config['save_to_sent_items'] ?? false),
            );
        });
    }
}
