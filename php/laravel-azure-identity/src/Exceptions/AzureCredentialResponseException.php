<?php

namespace Shrd\Laravel\Azure\Identity\Exceptions;

use Exception;
use Psr\Http\Client\RequestExceptionInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Throwable;

class AzureCredentialResponseException extends Exception implements RequestExceptionInterface, AzureCredentialException
{

    protected ?array $contents = null;

    private static function parseResponseContents(ResponseInterface $response): ?array
    {
        $contents = $response->getBody()->getContents();

        try {
            $parsedContents = \Safe\json_decode($contents, true);
            if(!is_array($parsedContents)) return null;
            return $parsedContents;
        } catch (JsonException) {
            return null;
        }
    }

    public function __construct(public readonly TokenCredentialDriver $credentialDriver,
                                public readonly AzureScope $scope,
                                public readonly RequestInterface $request,
                                public readonly ResponseInterface $response,
                                ?string $message = null,
                                int $code = 0,
                                ?Throwable $previous = null)
    {
        $this->contents = self::parseResponseContents($this->response);

        if(is_array($this->contents)) {
            $reason = $this->contents['error_description']
                ?? $this->contents['error']
                ?? $this->response->getReasonPhrase();
        } else {
            $reason = $this->response->getReasonPhrase();
        }

        $message ??= $this->response->getStatusCode()." RESPONSE: $reason";

        parent::__construct($message, $code, $previous);
    }

    public function getTokenCredentialDriver(): TokenCredentialDriver
    {
        return $this->credentialDriver;
    }

    public function getContents(): ?array
    {
        return $this->contents;
    }

    public function getContent(string $key): mixed
    {
        if($this->contents) {
            return $this->contents[$key] ?? null;
        } else {
            return null;
        }
    }

    public function getErrorType(): ?string
    {
        return $this->getContent('error');
    }

    public function getErrorDescription(): ?string
    {
        return $this->getContent('error_description');
    }

    public function getErrorCodes(): mixed
    {
        return $this->getContent('error_codes');
    }

    public function getErrorUri(): mixed
    {
        return $this->getContent('error_uri');
    }

    public function getTimestamp(): mixed
    {
        return $this->getContent('timestamp');
    }

    public function getTraceId(): mixed
    {
        return $this->getContent('trace_id');
    }

    public function getCorrelationId(): mixed
    {
        return $this->getContent('correlation_id');
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
