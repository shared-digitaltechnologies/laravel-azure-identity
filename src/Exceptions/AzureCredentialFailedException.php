<?php

namespace Shrd\Laravel\Azure\Identity\Exceptions;

use Exception;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Throwable;

class AzureCredentialFailedException extends Exception implements AzureCredentialException
{
    public function __construct(public readonly TokenCredentialDriver $credentialDriver,
                                ?string $message = null,
                                int $code = 0,
                                ?Throwable $previous = null)
    {
        $message ??= get_class($this->credentialDriver). " failed.";

        parent::__construct($message, $code, $previous);
    }

    public function getTokenCredentialDriver(): TokenCredentialDriver
    {
        return $this->credentialDriver;
    }
}
