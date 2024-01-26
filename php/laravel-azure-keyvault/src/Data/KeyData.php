<?php

namespace Shrd\Laravel\Azure\KeyVault\Data;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JsonSerializable;
use Nette\NotImplementedException;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasAttributes;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasTags;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Traversable;

/**
 * A wrapper around a KeyVault keys.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class KeyData implements ArrayAccess, Arrayable, JsonSerializable, Jsonable, IteratorAggregate
{
    use HasTags;
    use HasAttributes;

    public function __construct(public array $key,
                                public array $attributes = [],
                                public array $tags = [])
    {
    }

    /**
     * @throws JsonException
     */
    public static function fromResponseString(string $json): self
    {
        return self::fromResponseArray(\Safe\json_decode($json, true));
    }

    public static function fromResponseArray(array $response): self
    {
        return new self(
            key: $response['key'],
            attributes: $response['attributes'],
            tags: $response['tags']
        );
    }

    public function getReference(): KeyVaultKeyReference
    {
        return  KeyVaultKeyReference::fromUri($this->key['kid']);
    }

    public function getKeyReference(): KeyVaultKeyReference
    {
        return $this->getReference();
    }

    // TODO: Add getJWK

    // TODO: Add getJWKS

    public function get(string $key, mixed $default = null): mixed
    {
        if(array_key_exists($key, $this->key)) return $this->key[$key];
        return value($default);
    }

    public function has(string $key): bool
    {
        return isset($this->key[$key]);
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->key[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->key[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new NotImplementedException("mutate KeyVault key");
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new NotImplementedException("mutate KeyVault key");
    }

    public function toArray(): array
    {
        return $this->key;
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->key);
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return \Safe\json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): array
    {
        return [
            "key" => $this->key,
            "attributes" => $this->attributes,
            "tags" => $this->tags,
        ];
    }
}
