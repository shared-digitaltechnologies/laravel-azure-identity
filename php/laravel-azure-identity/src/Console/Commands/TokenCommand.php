<?php

namespace Shrd\Laravel\Azure\Identity\Console\Commands;

use Illuminate\Console\Command;
use Shrd\Laravel\Azure\Identity\AzureCredentialService;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialException;

class TokenCommand extends Command
{
    protected $signature = <<<SIGNATURE
        azure:token {scope* : The scope(s) for which you want to fetch a token. }
                    {--d|driver= : Specify the driver used to fetch the token. }
                    {--C|no-cache : Do not use the currently cached tokens for the provided scopes. }
        SIGNATURE;

    protected $description = "Fetches a token using the Azure Credentials of this application.";


    /**
     * @throws AzureCredentialException
     */
    public function handle(AzureCredentialService $credential): int
    {
        /**
         * @var bool $noCache
         * @var ?string $driver
         * @var array $scopes
         */
        $scopes  = $this->argument('scope');
        $driver  = $this->option('driver');
        $noCache = $this->option('no-cache');

        $token = $noCache ? $credential->refreshToken($scopes, $driver, false)
                          : $credential->token($scopes, $driver);

        $this->line($token->toJson(JSON_PRETTY_PRINT));

        return 0;
    }
}
