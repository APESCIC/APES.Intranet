<?php

namespace Modules\Communications\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class HandbookChapter extends Model
{
    protected $fillable = ['slug', 'title', 'sort_order', 'published'];

    protected function casts(): array
    {
        return ['published' => 'boolean'];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(HandbookVersion::class);
    }

    public function currentVersion(): HasOne
    {
        return $this->hasOne(HandbookVersion::class)->latestOfMany('version_number');
    }
}
