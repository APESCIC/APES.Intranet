<?php

namespace Tests\Fakes;

use App\Core\System\Checks\AddonCheck;
use RuntimeException;

final class FakeAddonCheck implements AddonCheck
{
    public bool $up = true;

    public int $probes = 0;

    public function __construct(private readonly string $key) {}

    public function key(): string
    {
        return $this->key;
    }

    public function configured(): bool
    {
        return true;
    }

    public function probe(): void
    {
        $this->probes++;

        if (! $this->up) {
            throw new RuntimeException('down');
        }
    }

    public function details(): array
    {
        return ['host' => null, 'port' => null, 'database' => null];
    }
}
