<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shift extends Model implements BelongsToTeam
{
    protected $table = 'operations_shifts';

    protected $fillable = ['rota_week_id', 'site_id', 'shift_template_id', 'user_id', 'role', 'starts_at', 'ends_at'];

    /**
     * @return BelongsTo<RotaWeek, $this>
     */
    public function rotaWeek(): BelongsTo
    {
        return $this->belongsTo(RotaWeek::class);
    }

    /**
     * @return BelongsTo<Site, $this>
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * @return BelongsTo<ShiftTemplate, $this>
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(ShiftTemplate::class, 'shift_template_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function isOpen(): bool
    {
        return $this->user_id === null;
    }

    public function isSupervisedBy(User $user): bool
    {
        return $this->site?->isSupervisedBy($user) ?? false;
    }

    public function label(): string
    {
        return $this->role.' · '.$this->starts_at->format('D j M H:i').'–'.$this->ends_at->format('H:i');
    }

    /**
     * @param  Builder<Shift>  $query
     * @return Builder<Shift>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereHas('rotaWeek', fn (Builder $week) => $week->where('status', RotaWeek::STATUS_PUBLISHED));
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_at' => 'immutable_datetime',
            'ends_at' => 'immutable_datetime',
        ];
    }
}
