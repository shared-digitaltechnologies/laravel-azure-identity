<?php

namespace Shrd\Laravel\Azure\KeyVault\Data\Concerns;

use Carbon\Carbon;

trait HasAttributes
{
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    public function getAttribute(string $name, mixed $default = null): mixed
    {
        $attributes = $this->getAttributes();
        if(array_key_exists($name, $attributes)) return $attributes[$name];
        return value($default);
    }

    public function hasAttribute(string $name): bool
    {
        return array_key_exists($name, $this->getAttributes());
    }

    public function getEnabled(): bool
    {
        return $this->getAttribute('enabled') ?? false;
    }

    public function getCreatedAt(): ?Carbon
    {
        $created = $this->getAttribute('created');
        if($created === null) return null;
        return Carbon::createFromTimestamp($created);
    }

    public function getNotBefore(): ?Carbon
    {
        $notBefore = $this->getAttribute('nbf');
        if($notBefore === null) return null;
        return Carbon::createFromTimestamp($notBefore);
    }

    public function getExpiresAt(): ?Carbon
    {
        $expiresAt = $this->getAttribute('exp');
        if($expiresAt === null) return null;
        return Carbon::createFromTimestamp($expiresAt);
    }

    public function isExpired($at = null): ?bool
    {
        $expiresAt = $this->getExpiresAt();
        if($expiresAt === null) return null;
        return Carbon::make($at)->isAfter($expiresAt);
    }

    public function isActive($at = null): ?bool
    {
        $expired = $this->isExpired($at);
        if($expired) return false;
        $notBefore = $this->getNotBefore();
        if($notBefore === null) {
            if($expired === null) return null;
            return true;
        }
        return Carbon::make($at)->isAfter($notBefore);
    }

    public function getUpdatedAt(): ?Carbon
    {
        $created = $this->getAttribute('updated');
        if($created === null) return null;
        return Carbon::createFromTimestamp($created);
    }
}
