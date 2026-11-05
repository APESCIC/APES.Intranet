<?php

namespace App\Models;

use App\Core\Access\AccessMatrix;
use App\Core\Access\RoleName;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

#[Fillable([
    'name',
    'email',
    'password',
    'job_title',
    'team',
    'phone',
    'mobile',
    'location',
    'skills',
    'bio',
    'photo_path',
    'emergency_contact_name',
    'emergency_contact_phone',
    'employee_number',
    'manager_id',
    'is_active',
])]
#[Hidden(['password', 'remember_token', 'oidc_sub'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, Notifiable;

    /**
     * Whether this person oversees a team: the supervisor, management or
     * admin role.
     */
    public function isSupervisor(): bool
    {
        return $this->can(AccessMatrix::TEAM_SUPERVISE);
    }

    public function primaryRoleLabel(): ?string
    {
        $names = $this->getRoleNames();

        foreach (RoleName::byPrecedence() as $role) {
            if ($names->contains($role->value)) {
                return $role->label();
            }
        }

        return null;
    }

    public function accountType(): ?RoleName
    {
        $names = $this->getRoleNames();

        foreach (RoleName::accountTypes() as $role) {
            if ($names->contains($role->value)) {
                return $role;
            }
        }

        return null;
    }

    public function accountTypeLabel(): ?string
    {
        return $this->accountType()?->label();
    }

    public function initials(): string
    {
        return collect(preg_split('/\s+/', trim($this->name)) ?: [])
            ->filter()
            ->take(2)
            ->map(fn (string $part) => mb_strtoupper(mb_substr($part, 0, 1)))
            ->implode('');
    }

    public function photoUrl(): ?string
    {
        if ($this->photo_path === null || $this->photo_path === '') {
            return null;
        }

        return Storage::disk('public')->url($this->photo_path);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(self::class, 'manager_id');
    }

    /**
     * @return HasMany<User, $this>
     */
    public function directReports(): HasMany
    {
        return $this->hasMany(self::class, 'manager_id');
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeDirectoryVisible(Builder $query): Builder
    {
        return $query->active();
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'deactivated_at' => 'datetime',
            'skills' => 'array',
            'totp_enabled' => 'boolean',
            'totp_checked_at' => 'datetime',
        ];
    }
}
