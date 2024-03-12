<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

interface TokenCredentialFactory
{
    public function extend(string $driver, callable $callback): static;

    public function credential(?string $credential = null): TokenCredential;

    public function createCredential(array $config): TokenCredential;

    public function getDefaultCredential(): string;

    public function driver(?string $credential = null): TokenCredentialDriver;

    public function createDriver(array $config): TokenCredentialDriver;

    public function getDefaultDriver(): string;
}
