<?php

namespace Modules\Hr\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * DBS or right-to-work check metadata. No document scans — SharePoint URL only.
 *
 * @property int $id
 * @property int $user_id
 * @property string $check_type
 * @property string|null $label
 * @property string|null $reference
 * @property Carbon|null $checked_on
 * @property Carbon|null $expires_on
 * @property string|null $document_url
 * @property Carbon|null $expiry_reminded_at
 */
class IdentityCheck extends Model
{
    public const TYPE_DBS = 'dbs';

    public const TYPE_RIGHT_TO_WORK = 'right_to_work';

    protected $table = 'hr_identity_checks';

    protected $fillable = [
        'user_id',
        'check_type',
        'label',
        'reference',
        'checked_on',
        'expires_on',
        'document_url',
        'expiry_reminded_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'checked_on' => 'date',
            'expires_on' => 'date',
            'expiry_reminded_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(?Carbon $on = null): bool
    {
        if ($this->expires_on === null) {
            return false;
        }

        return $this->expires_on->lt(($on ?? now())->startOfDay());
    }

    public function expiresWithinDays(int $days, ?Carbon $on = null): bool
    {
        if ($this->expires_on === null) {
            return false;
        }

        $on = ($on ?? now())->startOfDay();

        return ! $this->isExpired($on)
            && $this->expires_on->lte($on->copy()->addDays($days));
    }

    /**
     * @param  Builder<IdentityCheck>  $query
     * @return Builder<IdentityCheck>
     */
    public function scopeMissingOrExpired(Builder $query, ?Carbon $on = null): Builder
    {
        $on = ($on ?? now())->toDateString();

        return $query->where(function (Builder $inner) use ($on) {
            $inner->whereNull('expires_on')
                ->orWhereDate('expires_on', '<', $on);
        });
    }

    public function typeLabel(): string
    {
        return match ($this->check_type) {
            self::TYPE_DBS => 'DBS',
            self::TYPE_RIGHT_TO_WORK => 'Right to work',
            default => $this->check_type,
        };
    }
}
