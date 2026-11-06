<?php

namespace Modules\Communications\Models;

use App\Core\Access\RoleName;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class MicrosoftLink extends Model
{
    protected $fillable = [
        'key', 'name', 'url', 'icon', 'audience', 'opens_in_new_tab', 'sort_order', 'active',
    ];

    protected function casts(): array
    {
        return [
            'opens_in_new_tab' => 'boolean',
            'active' => 'boolean',
        ];
    }

    public function isVisibleTo(User $user): bool
    {
        if (! $this->active) {
            return false;
        }

        if ($this->audience === 'all') {
            return true;
        }

        return $user->hasRole(RoleName::Staff->value)
            || $user->hasRole(RoleName::Admin->value)
            || $user->hasRole(RoleName::Management->value)
            || $user->hasRole(RoleName::Hr->value)
            || $user->hasRole(RoleName::Legal->value)
            || $user->hasRole(RoleName::Supervisor->value);
    }
}
