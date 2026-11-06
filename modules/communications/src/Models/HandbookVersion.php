<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HandbookVersion extends Model
{
    protected $fillable = [
        'handbook_chapter_id', 'version_number', 'body', 'published_by', 'published_at',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(HandbookChapter::class, 'handbook_chapter_id');
    }

    public function acknowledgements(): HasMany
    {
        return $this->hasMany(HandbookAcknowledgement::class);
    }
}
