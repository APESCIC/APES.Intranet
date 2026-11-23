<?php

namespace App\Core\Changelog;

use App\Core\Modules\ModuleRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * One release note, synced from changelog/releases/vX.Y.Z.json. The files are
 * the source of truth; rows are never edited in the browser.
 *
 * @property string $version
 * @property int $major
 * @property int $minor
 * @property int $patch
 * @property string $title
 * @property Carbon $released_at
 * @property string $summary
 * @property list<string> $audiences
 * @property array<string, list<string>> $changes_by_type Named so it cannot clash with Eloquent's own $changes.
 * @property list<string>|null $internal_notes
 * @property array<string, string>|null $module_versions
 * @property bool $is_published
 */
class Release extends Model
{
    public const AUDIENCES = ['staff', 'volunteers', 'students'];

    /** In the order the hub shows them. */
    public const CHANGE_TYPES = ['added', 'changed', 'fixed', 'removed', 'security', 'accessibility'];

    protected $fillable = [
        'version', 'major', 'minor', 'patch', 'title', 'released_at', 'summary',
        'audiences', 'changes_by_type', 'internal_notes', 'module_versions', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'major' => 'integer',
            'minor' => 'integer',
            'patch' => 'integer',
            'released_at' => 'date',
            'audiences' => 'array',
            'changes_by_type' => 'array',
            'internal_notes' => 'array',
            'module_versions' => 'array',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopeNewestFirst(Builder $query): Builder
    {
        return $query->orderByDesc('major')->orderByDesc('minor')->orderByDesc('patch');
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public static function typeLabel(string $type): string
    {
        return match ($type) {
            'added' => 'Added',
            'changed' => 'Changed',
            'fixed' => 'Fixed',
            'removed' => 'Removed',
            'security' => 'Security',
            'accessibility' => 'Accessibility',
            default => ucfirst($type),
        };
    }

    /**
     * @return list<string>
     */
    public function changeTypes(): array
    {
        $changes = $this->changes_by_type;

        return array_values(array_filter(
            self::CHANGE_TYPES,
            fn (string $type) => ! empty($changes[$type]),
        ));
    }

    /**
     * @return list<string>
     */
    public function changesOfType(string $type): array
    {
        return $this->changes_by_type[$type] ?? [];
    }

    public function previousRelease(): ?self
    {
        return self::query()
            ->olderThan($this)
            ->newestFirst()
            ->first();
    }

    /**
     * @return list<array{slug: string, label: string, version: string, changed: bool}>
     */
    public function moduleVersionRows(): array
    {
        $versions = $this->module_versions;
        if (! is_array($versions) || $versions === []) {
            return [];
        }

        $previous = $this->previousRelease()?->module_versions ?? [];
        $registry = app(ModuleRegistry::class);
        $rows = [];

        foreach ($this->orderedModuleVersions($versions) as $slug => $version) {
            $label = $slug === 'core'
                ? 'Core'
                : ($registry->manifest($slug)?->name ?? $slug);
            $rows[] = [
                'slug' => $slug,
                'label' => $label,
                'version' => $version,
                'changed' => ($previous[$slug] ?? null) !== $version,
            ];
        }

        return $rows;
    }

    /**
     * @param  Builder<Release>  $query
     * @return Builder<Release>
     */
    public function scopeOlderThan(Builder $query, Release $release): Builder
    {
        return $query->where(function (Builder $query) use ($release) {
            $query->where('major', '<', $release->major)
                ->orWhere(function (Builder $query) use ($release) {
                    $query->where('major', $release->major)
                        ->where('minor', '<', $release->minor);
                })
                ->orWhere(function (Builder $query) use ($release) {
                    $query->where('major', $release->major)
                        ->where('minor', $release->minor)
                        ->where('patch', '<', $release->patch);
                });
        });
    }

    /**
     * @param  array<string, string>  $versions
     * @return array<string, string>
     */
    private function orderedModuleVersions(array $versions): array
    {
        $core = $versions['core'] ?? null;
        unset($versions['core']);
        ksort($versions, SORT_STRING);
        if (is_string($core)) {
            return ['core' => $core] + $versions;
        }

        return $versions;
    }
}
