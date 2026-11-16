<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lesson extends Model
{
    protected $table = 'learning_lessons';

    protected $fillable = [
        'section_id',
        'title',
        'draft_body',
        'sort_order',
    ];

    /**
     * @return BelongsTo<Section, $this>
     */
    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    /**
     * @return HasMany<LessonVersion, $this>
     */
    public function versions(): HasMany
    {
        return $this->hasMany(LessonVersion::class);
    }

    /**
     * @return HasOne<LessonVersion, $this>
     */
    public function currentVersion(): HasOne
    {
        return $this->hasOne(LessonVersion::class)->latestOfMany('version_number');
    }
}
