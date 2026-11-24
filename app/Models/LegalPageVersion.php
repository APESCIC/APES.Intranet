<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalPageVersion extends Model
{
    protected $fillable = [
        'legal_page_id',
        'number',
        'body',
        'change_summary',
        'in_effect_at',
        'published_at',
        'published_by',
        'status',
        'material_change',
    ];

    protected function casts(): array
    {
        return [
            'in_effect_at' => 'datetime',
            'published_at' => 'datetime',
            'material_change' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<LegalPage, $this>
     */
    public function page(): BelongsTo
    {
        return $this->belongsTo(LegalPage::class, 'legal_page_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function publishedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }
}
