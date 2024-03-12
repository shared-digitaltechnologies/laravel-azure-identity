<?php

namespace Shrd\Laravel\Azure\Identity\Certificates;

use OpenSSLCertificate;
use Safe\Exceptions\OpensslException;
use Shrd\Laravel\Azure\Identity\Contracts\ClientCertificate;

class OpenSSLClientCertificate implements ClientCertificate
{
    protected OpenSSLCertificate $certificate;

    /**
     * @throws OpensslException
     */
    public function __construct(mixed $certificate)
    {
        $this->certificate = \Safe\openssl_x509_read($certificate);
    }

    public function algorithmId(): string
    {
        return 'RS256';
    }

    public function getX509Thumbprint(): string
    {
        return openssl_x509_fingerprint($this->certificate, 'sha1', true);
    }

    public function sign(string $payload): string
    {
        openssl_sign($payload, $signature, $this->certificate, OPENSSL_ALGO_SHA256);
        return $signature;
    }
}
