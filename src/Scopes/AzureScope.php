<?php

namespace Shrd\Laravel\Azure\Identity\Scopes;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use JsonSerializable;
use Psr\Http\Message\UriInterface;
use Safe\Exceptions\JsonException;

readonly class AzureScope implements JsonSerializable
{
    protected array $scopes;

    public function __construct(array $scopes)
    {
        $scopes = array_map(fn($scope) => trim($scope), $scopes);
        sort($scopes);
        $this->scopes = $scopes;
    }

    public static function keyVault(): self
    {
        return new self(['https://vault.azure.net/.default']);
    }

    public static function storageAccount(): self
    {
        return new self(['https://storage.azure.com/.default']);
    }

    public static function webPubSub(): self
    {
        return new self(['https://webpubsub.azure.com/.default']);
    }

    public static function microsoftGraph(): self
    {
        return new self(['https://graph.microsoft.com/.default']);
    }

    public static function fromUri(string|UriInterface $uri): self
    {
        if(is_string($uri)) $uri = new Uri($uri);
        return new self([$uri->withPath('/.default')]);
    }

    public static function from($value): self
    {
        if($value instanceof AzureScope) return $value;
        if($value instanceof UriInterface) return self::fromUri($value);
        if(is_string($value)) return new self([$value]);
        if(is_array($value)) return new self($value);

        throw new InvalidArgumentException("Cannot convert " . get_debug_type($value). " to an AzureScope.");
    }

    public function getResource(): string
    {
        return str_replace('/.default', '', $this->scopes[0]);
    }

    private function prepareForCacheKey(string $scope): string
    {
        if(str_starts_with($scope, 'https://')) {
            $pos = strpos($scope, '/', 8);
            if($pos === false) $pos = null;
            $scope = substr($scope, 8, $pos - 8);
        }

        return str_replace(['+', ':', '/'], '_', $scope);
    }

    public function getCacheKey(): string
    {
        $parts = array_map($this->prepareForCacheKey(...), $this->scopes);
        return implode('+', $parts);
    }

    public function toString(): string
    {
        return implode(' ', $this->scopes);
    }

    public function __toString(): string
    {
        return $this->toString();
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return \Safe\json_encode($this->toString(), $options);
    }

    public function jsonSerialize(): string
    {
        return $this->toString();
    }
}
