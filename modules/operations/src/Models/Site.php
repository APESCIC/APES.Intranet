<?php

namespace Modules\Operations\Models;

use App\Core\Access\Contracts\BelongsToTeam;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Site extends Model implements BelongsToTeam
{
    protected $table = 'operations_sites';

    protected $fillable = ['name', 'team', 'latitude', 'longitude', 'radius_metres', 'active'];

    public function isSupervisedBy(User $user): bool
    {
        return filled($this->team) && $this->team === $user->team;
    }

    public function hasGeofenceArea(): bool
    {
        return $this->latitude !== null && $this->longitude !== null;
    }

    /**
     * @param  Builder<Site>  $query
     * @return Builder<Site>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'radius_metres' => 'integer',
            'active' => 'boolean',
        ];
    }
}
