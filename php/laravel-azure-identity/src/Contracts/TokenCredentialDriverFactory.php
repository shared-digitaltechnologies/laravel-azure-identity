<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

interface TokenCredentialDriverFactory
{
    /**
     * Creates a new token credential driver with the specified name.
     *
     * @param string|null $driver
     * @return TokenCredentialDriver
     */
    public function driver(?string $driver = null): TokenCredentialDriver;
}
