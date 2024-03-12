<?php

namespace Shrd\Laravel\Azure\Identity\Certificates;

use RuntimeException;
use Safe\Exceptions\OpensslException;
use Shrd\Laravel\Azure\Identity\Contracts\ClientCertificate;
use Shrd\Laravel\Azure\Identity\Contracts\ClientCertificateFactory;

class SimpleClientCertificateFactory implements ClientCertificateFactory
{

    /**
     * @throws OpensslException
     */
    public function create(array $config): ClientCertificate
    {
        return new OpenSSLClientCertificate($config['file']);
    }

    /**
     * @throws OpensslException
     */
    public function createFromFile(string $file, string $password = null): ClientCertificate
    {
        if(!empty($password)) throw new RuntimeException("Password protected certificate files are not yet supported");

        return new OpenSSLClientCertificate($file);
    }
}
