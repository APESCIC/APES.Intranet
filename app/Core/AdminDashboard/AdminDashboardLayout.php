<?php

namespace App\Core\AdminDashboard;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property list<string> $ordered_keys
 * @property list<string> $hidden_keys
 */
class AdminDashboardLayout extends Model
{
    protected $table = 'admin_dashboard_layouts';

    protected $fillable = ['user_id', 'ordered_keys', 'hidden_keys'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'ordered_keys' => 'array',
            'hidden_keys' => 'array',
        ];
    }

    public function isSharedDefault(): bool
    {
        return $this->user_id === null;
    }
}
