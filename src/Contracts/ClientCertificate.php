<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

interface ClientCertificate
{

    public function algorithmId(): string;

    public function getX509Thumbprint(): string;

    /**
     * @param string $payload
     * @return string
     */
    public function sign(string $payload): string;
}
