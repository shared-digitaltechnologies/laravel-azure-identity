<?php

namespace Shrd\Laravel\Azure\KeyVault\Data\Concerns;

trait HasTags
{
    public function getTags(): array
    {
        return $this->tags;
    }

    public function getTag(string $name, mixed $default = null): mixed
    {
        $tags = $this->getTags();
        if(array_key_exists($name, $tags)) return $tags[$name];
        return value($default);
    }

    public function hasTag(string $name): bool
    {
        return array_key_exists($name, $this->getTags());
    }
}
