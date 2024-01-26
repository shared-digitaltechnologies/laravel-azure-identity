<?php

namespace Shrd\Laravel\Azure\KeyVault\References;

use GuzzleHttp\Psr7\Uri;
use InvalidArgumentException;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Shrd\Laravel\Azure\Identity\Scopes\AzureScope;

abstract readonly class KeyVaultReference implements UriInterface
{
    const PATTERN = '/^\\s*@Microsoft.KeyVault\\((.+)\\)\\s*;?\\s*$/';

    protected function __construct(public UriInterface $uri)
    {
    }

    public function getCacheKey(): string
    {
        $path = ltrim($this->getPath(), '/');
        return $this->getHost()."/$path";
    }

    public function getScope(): AzureScope
    {
        return AzureScope::fromUri($this->uri);
    }

    public static function fromUri(string|UriInterface $uri, ?UriFactoryInterface $uriFactory = null): self
    {
        if(is_string($uri)) {
            if($uriFactory) $uri = $uriFactory->createUri($uri);
            else $uri = new Uri($uri);
        }

        $path = $uri->getPath();
        $pathParts = explode('/', $path);
        $firstPart = $pathParts[0] === '' ? ($pathParts[1] ?? 'unknown') : $pathParts[0];

        return match ($firstPart) {
            'keys' => KeyVaultKeyReference::fromUri($uri),
            'secrets' => KeyVaultSecretReference::fromUri($uri),
            'certificates' => KeyVaultCertificateReference::fromUri($uri),
            default => throw new InvalidArgumentException("Unknown KeyVault entity at path '$firstPart'."),
        };
    }

    public static function fromProperties(array $properties): self
    {
        if(array_key_exists('SecretUri', $properties) || array_key_exists('SecretName', $properties)) {
            return KeyVaultSecretReference::fromProperties($properties);
        } elseif (array_key_exists('KeyUri', $properties) || array_key_exists('KeyName', $properties)) {
            return KeyVaultKeyReference::fromProperties($properties);
        } elseif (array_key_exists('CertificateUri', $properties) || array_key_exists('CertificateName', $properties)) {
            return KeyVaultCertificateReference::fromProperties($properties);
        } else {
            $propertyList = "'".implode("', '", array_keys($properties))."'";
            throw new InvalidArgumentException("Could not construct KeyVaultReference from $propertyList");
        }
    }

    public static function fromString(string $value): self
    {
        $values = self::parseKeyVaultReferenceString($value);
        if($values === null) return self::fromUri($value);
        return self::fromProperties($values);
    }

    public static function from(string|UriInterface|array|self $value): self
    {
        if($value instanceof self) return $value;
        if($value instanceof UriInterface) return self::fromUri($value);
        if(is_array($value)) return self::fromProperties($value);
        return self::fromString($value);
    }

    public static function isKeyVaultReferenceString(string $value): bool
    {
        return str($value)->isMatch(self::PATTERN);
    }

    protected static function parseKeyVaultReferenceString(string $value): ?array
    {
        if (preg_match(self::PATTERN, $value, $matches)) {
            $value = $matches[1];
        } else {
            return null;
        }

        $pairs = explode(';', $value);

        $values = [];
        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                [$key, $value] = $parts;
                $values[$key] = $value;
            }
        }

        return $values;
    }

    public function getVaultName(): string
    {
        $host = $this->getHost();
        return str($host)->before('.')->toString();
    }

    protected function getPathPart(int $index): string
    {
        $path = $this->getPath();
        $parts = explode('/', $path);
        if($parts[0] === '') $index = $index + 1;
        return $parts[$index];
    }

    public function getKeyVaultEntityType(): string
    {
        return $this->getPathPart(0);
    }

    public function getName(): string
    {
        return $this->getPathPart(1);
    }

    public function getVersion(): ?string
    {
        return $this->getPathPart(2);
    }

    public abstract function toReferenceString(): string;

    public function getScheme(): string
    {
        return $this->uri->getScheme();
    }

    public function getAuthority(): string
    {
        return $this->uri->getAuthority();
    }

    public function getUserInfo(): string
    {
        return $this->uri->getUserInfo();
    }

    public function getHost(): string
    {
        return $this->uri->getHost();
    }

    public function getPort(): ?int
    {
        return $this->uri->getPort();
    }

    public function getPath(): string
    {
        return $this->uri->getPath();
    }

    public function getQuery(): string
    {
        return $this->uri->getQuery();
    }

    public function getFragment(): string
    {
        return $this->uri->getFragment();
    }

    public function withScheme(string $scheme): UriInterface
    {
        return $this->uri->withScheme($scheme);
    }

    public function withUserInfo(string $user, ?string $password = null): UriInterface
    {
        return $this->uri->withUserInfo($user, $password);
    }

    public function withHost(string $host): UriInterface
    {
        return $this->uri->withHost($host);
    }

    public function withPort(?int $port): UriInterface
    {
        return $this->uri->withPort($port);
    }

    public function withPath(string $path): UriInterface
    {
        return $this->uri->withPath($path);
    }

    public function withQuery(string $query): UriInterface
    {
        return $this->uri->withQuery($query);
    }

    public function withFragment(string $fragment): UriInterface
    {
        return $this->uri->withFragment($fragment);
    }

    public function __toString(): string
    {
        return $this->uri->__toString();
    }

}
