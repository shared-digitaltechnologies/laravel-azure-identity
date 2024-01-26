<?php

namespace Shrd\Laravel\Azure\Identity\Exceptions;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Throwable;

class AzureCredentialRequestException extends Exception implements RequestExceptionInterface, AzureCredentialException
{

    public function __construct(public readonly TokenCredentialDriver $credentialDriver,
                                public readonly AzureScope $scope,
                                public readonly RequestInterface $request,
                                ?string $message = null,
                                int $code = 0,
                                ?Throwable $previous = null)
    {
        $message ??= "Token request with ".get_class($this->credentialDriver)." to get scope ".$this->scope->toString()." failed";

        parent::__construct($message, $code, $previous);
    }

    public function getTokenCredentialDriver(): TokenCredentialDriver
    {
        return $this->credentialDriver;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
