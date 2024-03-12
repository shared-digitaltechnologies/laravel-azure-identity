<?php

namespace Shrd\Laravel\Azure\Identity\Contracts;

interface ClientCertificateFactory
{
    public function createFromFile(string $file, ?string $password = null): ClientCertificate;

    public function create(array $config): ClientCertificate;
}
