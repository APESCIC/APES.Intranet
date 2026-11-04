<?php

namespace App\Core\Navigation;

final readonly class NavItem
{
    private function __construct(
        public string $key,
        public string $label,
        public string $icon,
        public string $permission,
        public ?string $routeName = null,
        public ?string $activePattern = null,
        public ?string $externalUrl = null,
    ) {}

    public static function route(string $key, string $label, string $icon, string $permission, string $routeName, ?string $activePattern = null): self
    {
        return new self($key, $label, $icon, $permission, routeName: $routeName, activePattern: $activePattern ?? $routeName);
    }

    public static function external(string $key, string $label, string $icon, string $permission, string $url): self
    {
        return new self($key, $label, $icon, $permission, externalUrl: $url);
    }

    /**
     * A menu entry for a feature planned in a later milestone.
     */
    public static function soon(string $key, string $label, string $icon, string $permission): self
    {
        return new self($key, $label, $icon, $permission);
    }

    public function isSoon(): bool
    {
        return $this->routeName === null && $this->externalUrl === null;
    }

    public function isExternal(): bool
    {
        return $this->externalUrl !== null;
    }

    public function url(): ?string
    {
        return $this->externalUrl ?? ($this->routeName !== null ? route($this->routeName) : null);
    }

    public function isActive(): bool
    {
        return $this->activePattern !== null && request()->routeIs($this->activePattern);
    }
}
