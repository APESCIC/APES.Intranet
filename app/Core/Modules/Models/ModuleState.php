<?php

namespace App\Core\Modules\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property string $slug
 * @property bool $enabled
 * @property string $reason
 * @property int|null $changed_by
 * @property Carbon $changed_at
 */
class ModuleState extends Model
{
    public $timestamps = false;

    protected $guarded = ['id'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'changed_at' => 'datetime',
        ];
    }
}
