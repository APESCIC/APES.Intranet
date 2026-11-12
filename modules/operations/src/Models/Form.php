<?php

namespace Modules\Operations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Form extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $table = 'operations_forms';

    protected $fillable = ['title', 'description', 'status', 'route_to_user_id', 'route_to_role', 'created_by', 'published_at'];

    /**
     * @return HasMany<FormField, $this>
     */
    public function fields(): HasMany
    {
        return $this->hasMany(FormField::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<FormSubmission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(FormSubmission::class)->latest();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function routeToUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'route_to_user_id');
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }

    public function routingLabel(): string
    {
        return $this->routeToUser->name
            ?? ($this->route_to_role ? 'Everyone with the '.$this->route_to_role.' role' : 'Not set');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }
}
