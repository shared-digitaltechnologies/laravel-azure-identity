<?php

namespace Shrd\Laravel\Azure\KeyVault\Data;

use ArrayAccess;
use ArrayIterator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use IteratorAggregate;
use JetBrains\PhpStorm\NoReturn;
use JsonSerializable;
use Safe\Exceptions\JsonException;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasAttributes;
use Shrd\Laravel\Azure\KeyVault\Data\Concerns\HasTags;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultKeyReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultReference;
use Shrd\Laravel\Azure\KeyVault\References\KeyVaultSecretReference;
use Traversable;

/**
 * Wrapper around a KeyVault secret response.
 *
 * @implements ArrayAccess<string, mixed>
 * @implements IteratorAggregate<string, mixed>
 */
class SecretData implements ArrayAccess, IteratorAggregate, Arrayable, JsonSerializable, Jsonable
{
    use HasTags;
    use HasAttributes;

    protected array $arrayValue = [];

    /**
     * @throws JsonException
     */
    public function __construct(public string      $value,
                                public string|null $id = null,
                                public string|null $kid = null,
                                public string|null $contentType = null,
                                public bool        $managed = false,
                                public array       $tags = [],
                                public array       $attributes = [])
    {
        if($this->isJson()) {
            $this->arrayValue = \Safe\json_decode($this->value, true);
        }
    }

    /** @noinspection PhpUnhandledExceptionInspection */
    public static function fromStringValue(string $value): self
    {
        return new self($value);
    }

    /**
     * @throws JsonException
     */
    public static function fromResponseJson(string $json): self
    {
        return self::fromResponseArray(\Safe\json_decode($json, true));
    }

    /**
     * @throws JsonException
     */
    public static function fromResponseArray(array $response): self
    {
        return new self(
            value: $response['value'],
            id: $response['id'] ?? null,
            kid: $response['kid'] ?? null,
            contentType: $response['contentType'] ?? null,
            managed: $response['managed'] ?? false,
            tags: $response['tags'] ?? [],
            attributes: $response['attributes'] ?? []
        );
    }

    public function getReference(): KeyVaultSecretReference
    {
        return KeyVaultSecretReference::fromUri($this->id);
    }

    public function getSecretReference(): ?KeyVaultReference
    {
        return $this->getReference();
    }

    public function getKeyReference(): ?KeyVaultKeyReference
    {
        if(!$this->kid) return null;
        return KeyVaultKeyReference::fromUri($this->kid);
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function isJson(): bool
    {
        return str($this->contentType)->contains('application/json');
    }

    public function isPKCS12(): bool
    {
        return str($this->contentType)->contains('application/x-pkcs12');
    }

    public function isPem(): bool
    {
        return str($this->contentType)->contains('application/x-pem-file');
    }

    public function getStringValue(): string
    {
        return $this->value;
    }

    public function getArrayValue(): array
    {
        return $this->arrayValue;
    }

    // TODO: GetJWK

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->arrayValue[$key] ?? $default;
    }

    public function offsetExists(mixed $offset): bool
    {
        return isset($this->arrayValue[$offset]);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->arrayValue[$offset];
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->arrayValue[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->arrayValue[$offset]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->arrayValue);
    }

    #[NoReturn] public function dd(): void
    {
        dd($this->__debugInfo());
    }

    public function toArray(): array
    {
        return $this->arrayValue;
    }

    public function __debugInfo(): ?array
    {
        return  [
            "value" => $this->value,
            "arrayValue" => $this->arrayValue,
            "id" => $this->id,
            "kid" => $this->kid,
            "contentType" => $this->contentType,
            "managed" => $this->managed,
            "tags" => $this->tags,
            "attributes" => $this->attributes
        ];
    }

    /**
     * @throws JsonException
     */
    public function toJson($options = 0): string
    {
        return \Safe\json_encode($this->jsonSerialize(), $options);
    }

    public function jsonSerialize(): null
    {
        return [
            "value" => $this->value,
            "id" => $this->id,
            "kid" => $this->kid,
            "contentType" => $this->contentType,
            "managed" => $this->managed,
            "tags" => $this->tags,
            "attributes" => $this->attributes,
        ];
    }
}
