<?php

namespace Shrd\Laravel\Azure\Mailer;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Symfony\Component\Mailer\Bridge\Azure\Transport\AzureTransportFactory;
use Symfony\Component\Mailer\Transport\Dsn;

class ServiceProvider extends BaseServiceProvider
{
    public function boot(): void
    {
        Mail::extend('azure', function (array $config) {
            return (new AzureTransportFactory)->create(
                new Dsn(
                    scheme: 'azure+api',
                    host: 'default',
                    user: $config['resource_name'],
                    password: $config['access_key'],
                    port: null,
                    options: [
                        "api_version" => $config['api_version'] ?? '2023-03-31',
                        "disable_tracking" => $config['disable_user_tracking'] ?? false
                    ]
                )
            );
        });
    }
}
