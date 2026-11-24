<?php

namespace App\Models;

use App\Core\Legal\LegalPageVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class LegalPage extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'visibility',
        'needs_acceptance',
        'review_interval_months',
        'locked',
    ];

    protected function casts(): array
    {
        return [
            'needs_acceptance' => 'boolean',
            'locked' => 'boolean',
        ];
    }

    /**
     * @return HasMany<LegalPageVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(LegalPageVersion::class);
    }

    public function currentVersion(?Carbon $at = null): ?LegalPageVersion
    {
        $at ??= now();

        return $this->versions()
            ->where('status', LegalPageVersionStatus::Published)
            ->where(function ($query) use ($at) {
                $query->whereNull('in_effect_at')->orWhere('in_effect_at', '<=', $at);
            })
            ->orderByDesc('number')
            ->first();
    }

    public function showRouteName(): string
    {
        return $this->slug === 'privacy' ? 'privacy' : 'legal.'.$this->slug;
    }
}
