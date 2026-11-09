<?php

namespace App\Core\Changelog;

use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Copies changelog/releases/*.json into the releases table. Keyed on version
 * and idempotent: a re-run creates nothing new, updates only changed rows and
 * never deletes. A row whose file has gone is reported, not purged, so an
 * accidental file removal cannot empty the hub.
 */
class ReleaseChangelogSync
{
    public function __construct(private readonly ChangelogEntryValidator $validator) {}

    public function directory(): string
    {
        $configured = config('intranet.changelog_path');

        return is_string($configured) && $configured !== '' ? $configured : base_path('changelog/releases');
    }

    /**
     * Validates every file first and writes nothing if any is invalid.
     */
    public function sync(): ChangelogSyncResult
    {
        $directory = $this->directory();
        $errors = $this->validator->validateDirectory($directory);
        if ($errors !== []) {
            throw new RuntimeException("Release notes are invalid, nothing was synced:\n".implode("\n", $errors));
        }

        $payloads = array_map(fn (string $path) => $this->validator->decode($path), $this->validator->entryPaths($directory));

        return DB::transaction(function () use ($payloads) {
            $created = $updated = $unchanged = [];

            foreach ($payloads as $payload) {
                [$major, $minor, $patch] = array_map('intval', explode('.', ltrim($payload['version'], 'v')));
                $release = Release::query()->firstOrNew(['version' => $payload['version']]);
                $release->fill([
                    'major' => $major,
                    'minor' => $minor,
                    'patch' => $patch,
                    'title' => $payload['title'],
                    'released_at' => $payload['released_at'],
                    'summary' => $payload['summary'],
                    'audiences' => $payload['audiences'],
                    'changes' => $payload['changes'],
                    'internal_notes' => $payload['internal_notes'] ?? null,
                    'is_published' => $payload['published'],
                ]);

                $isNew = ! $release->exists;
                $isDirty = $release->isDirty();
                $release->save();

                match (true) {
                    $isNew => $created[] = $release->version,
                    $isDirty => $updated[] = $release->version,
                    default => $unchanged[] = $release->version,
                };
            }

            $orphaned = Release::query()
                ->whereNotIn('version', array_column($payloads, 'version'))
                ->pluck('version')
                ->all();

            return new ChangelogSyncResult($created, $updated, $unchanged, $orphaned);
        });
    }
}
