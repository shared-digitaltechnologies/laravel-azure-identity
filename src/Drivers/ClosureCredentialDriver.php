<?php

namespace Shrd\Laravel\Azure\Identity\Drivers;

use Closure;
use Exception;
use Shrd\Laravel\Azure\Identity\Contracts\TokenCredentialDriver;
use Shrd\Laravel\Azure\Identity\Exceptions\AzureCredentialFailedException;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;
use Shrd\Laravel\Azure\Identity\Tokens\AccessToken;

class ClosureCredentialDriver implements TokenCredentialDriver
{

    /**
     * @var Closure(AzureScope $scope): mixed
     */
    protected Closure $_fetchToken;

    /**
     * @param callable(AzureScope $scope): mixed $fetchToken
     */
    public function __construct(callable $fetchToken)
    {
        $this->_fetchToken = $fetchToken(...);
    }

    /**
     * In the category of TokenCredentialDrivers, this is the terminal Credential Driver!
     *
     * @param mixed $token
     * @return self
     */
    public static function constant(mixed $token): self
    {
        return new self (fn() => $token);
    }

    /**
     * In the category of TokenCredentialDrivers, this is the initial credential driver.
     *
     * @return self
     */
    public static function empty(): self
    {
        return new self(fn() => throw new Exception('Empty Credential Driver'));
    }

    public static function fromArray(array $tokens): self
    {
        return new self(fn(AzureScope $scope) => $tokens[$scope->getCacheKey()]);
    }

    function fetchToken(AzureScope $scope): AccessToken
    {
        try {
            $result = ($this->_fetchToken)($scope);
        } catch (Exception $exception) {
            throw new AzureCredentialFailedException($this, $exception->getMessage(), $exception->getCode(), $exception);
        }
        if($result === null || $result === false) {
            throw new AzureCredentialFailedException(
                $this,
                'closure responded '.get_debug_type($result).' for scope '.$scope->getCacheKey()
            );
        }
        return AccessToken::from(($this->_fetchToken)($scope));
    }
}
