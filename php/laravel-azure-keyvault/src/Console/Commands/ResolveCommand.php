<?php

namespace Shrd\Laravel\Azure\KeyVault\Console\Commands;

use Illuminate\Console\Command;
use Shrd\Laravel\Azure\KeyVault\KeyVaultService;
use Symfony\Component\VarDumper\VarDumper;

class ResolveCommand extends Command
{
    protected $signature = <<<'SIGNATURE'
        keyvault:resolve {value : The string value you want to resolve}
        SIGNATURE;

    protected $description = "Resolves an Azure KeyVault Reference using the KeyVault provider of this app.";


    public function handle(KeyVaultService $vault): int
    {
        $value = $this->argument('value');
        $resolvedValue = $vault->resolve($value);

        VarDumper::dump($resolvedValue);

        return 0;
    }
}
