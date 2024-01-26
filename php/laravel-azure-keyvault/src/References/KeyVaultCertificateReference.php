<?php

namespace Shrd\Laravel\Azure\KeyVault\References;

use GuzzleHttp\Psr7\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

readonly class KeyVaultCertificateReference extends KeyVaultReference
{
    public static function fromUri(string|UriInterface $uri, ?UriFactoryInterface $uriFactory = null): self
    {
        if(is_string($uri)) {
            if($uriFactory) $uri = $uriFactory->createUri($uri);
            else $uri = new Uri($uri);
        }
        return new self($uri);
    }

    public static function fromName(string $vaultName,
                                    string $certificateName,
                                    ?string $certificateVersion = null): self
    {
        return self::fromUri("https://$vaultName.vault.azure.net/certificates/$certificateName/$certificateVersion");
    }

    public static function fromProperties(array $properties): self
    {
        if(array_key_exists('CertificateUri', $properties)) {
            return self::fromUri($properties['CertificateUri']);
        }

        return self::fromName(
            vaultName: $properties['VaultName'],
            certificateName: $properties['CertificateName'],
            certificateVersion: $properties['CertificateVersion'] ?? null
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

    public function toReferenceString(): string
    {
        return "@Microsoft.KeyVault(CertificateUri=$this->uri)";
    }
}
