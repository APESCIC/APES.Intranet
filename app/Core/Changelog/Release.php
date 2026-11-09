<?php

namespace App\Core\Changelog;

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
 * @property array<string, list<string>> $changes
 * @property list<string>|null $internal_notes
 * @property bool $is_published
 */
class Release extends Model
{
    public const AUDIENCES = ['staff', 'volunteers', 'students'];

    /** In the order the hub shows them. */
    public const CHANGE_TYPES = ['added', 'changed', 'fixed', 'removed', 'security', 'accessibility'];

    protected $fillable = [
        'version', 'major', 'minor', 'patch', 'title', 'released_at', 'summary',
        'audiences', 'changes', 'internal_notes', 'is_published',
    ];

    protected function casts(): array
    {
        return [
            'major' => 'integer',
            'minor' => 'integer',
            'patch' => 'integer',
            'released_at' => 'date',
            'audiences' => 'array',
            'changes' => 'array',
            'internal_notes' => 'array',
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

    /**
     * @return list<string>
     */
    public function changeTypes(): array
    {
        return array_values(array_filter(
            self::CHANGE_TYPES,
            fn (string $type) => ($this->changes[$type] ?? []) !== [],
        ));
    }
}
