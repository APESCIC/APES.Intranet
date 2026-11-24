<?php

namespace App\Core\Legal;

final class CookieRegister
{
    /**
     * @return list<array{name: string, purpose: string, duration: string, type: string, type_label: string}>
     */
    public function entries(): array
    {
        /** @var list<array<string, mixed>> $configured */
        $configured = config('legal.cookies', []);

        $entries = [];
        foreach ($configured as $entry) {
            $name = $this->resolveName($entry);
            if ($name === null || $name === '') {
                continue;
            }

            $type = (string) ($entry['type'] ?? '');

            $entries[] = [
                'name' => $name,
                'purpose' => (string) ($entry['purpose'] ?? ''),
                'duration' => (string) ($entry['duration'] ?? ''),
                'type' => $type,
                'type_label' => $this->typeLabel($type),
            ];
        }

        return $entries;
    }

    /**
     * @return list<string>
     */
    public function registeredNames(): array
    {
        return array_column($this->entries(), 'name');
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function resolveName(array $entry): ?string
    {
        if (isset($entry['name']) && is_string($entry['name']) && $entry['name'] !== '') {
            return $entry['name'];
        }

        $from = $entry['name_from'] ?? null;
        if (! is_string($from) || $from === '') {
            return null;
        }

        $resolved = config($from);

        return is_string($resolved) && $resolved !== '' ? $resolved : null;
    }

    private function typeLabel(string $type): string
    {
        return match ($type) {
            'strictly_necessary' => 'Strictly necessary',
            default => str_replace('_', ' ', ucfirst($type)),
        };
    }
}
