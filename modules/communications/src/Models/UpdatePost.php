<?php

namespace Modules\Communications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UpdatePost extends Model
{
    protected $fillable = [
        'author_id', 'title', 'body', 'audiences', 'pinned',
        'comments_enabled', 'email_alert', 'published_at',
    ];

    protected function casts(): array
    {
        return [
            'audiences' => 'array',
            'pinned' => 'boolean',
            'comments_enabled' => 'boolean',
            'email_alert' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function reads(): HasMany
    {
        return $this->hasMany(UpdateRead::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(UpdateComment::class);
    }

    public function isVisibleTo(User $user): bool
    {
        $audiences = $this->audiences ?? [];
        if ($audiences === []) {
            return true;
        }

        foreach ($audiences as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }
}
