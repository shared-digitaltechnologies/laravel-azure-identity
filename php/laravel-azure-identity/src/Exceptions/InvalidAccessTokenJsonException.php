<?php

namespace Shrd\Laravel\Azure\Identity\Exceptions;

use Exception;
use JsonException;

class InvalidAccessTokenJsonException extends Exception implements AzureCredentialException
{
    public function __construct(JsonException $previous, public readonly ?string $json)
    {
        parent::__construct(
            "invalid-access-token-json-exception: ".$previous->getMessage(),
            $previous->getCode(),
            $previous
        );
    }
}
