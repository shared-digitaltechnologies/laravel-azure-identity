<?php

namespace Shrd\Laravel\Azure\KeyVault\References;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

readonly class KeyVaultKeyReference extends KeyVaultReference
{
    public static function fromUri(string|UriInterface $uri, ?UriFactoryInterface $uriFactory = null): self
    {
        if(is_string($uri)) {
            if($uriFactory) $uri = $uriFactory->createUri($uri);
            else $uri = new Uri($uri);
        }
        return new self($uri);
    }

    public static function fromName(string $vaultName, string $keyName, ?string $keyVersion = null): self
    {
        return self::fromUri("https://$vaultName.vault.azure.net/keys/$keyName/$keyVersion");
    }

    public static function fromProperties(array $properties): self
    {
        if(array_key_exists('KeyUri', $properties)) {
            return self::fromUri($properties['KeyUri']);
        }

        return self::fromName(
            vaultName: $properties['VaultName'],
            keyName: $properties['KeyName'],
            keyVersion: $properties['KeyVersion'] ?? null
        );
    }

    public static function from(string|UriInterface|array|KeyVaultReference $value): self
    {
        if($value instanceof self) return $value;
        if($value instanceof KeyVaultReference) {
            return self::fromName($value->getVaultName(), $value->getName(), $value->getVersion());
        }
        if($value instanceof UriInterface) return new self($value);
        if(is_array($value)) return self::fromProperties($value);
        return self::fromString($value);
    }

    public static function fromString(string $value): self
    {
        $values = self::parseKeyVaultReferenceString($value);
        if($values === null) return self::fromUri($value);
        return self::fromProperties($values);
    }

    public function getSignUri(): UriInterface
    {
        return $this->uri->withPath($this->uri->getPath().'/sign');
    }

    public function getVerifyUri(): UriInterface
    {
        return $this->uri->withPath($this->uri->getPath().'/verify');
    }

    public function toReferenceString(): string
    {
        return "@Microsoft.KeyVault(KeyUri=$this->uri)";
    }
}
