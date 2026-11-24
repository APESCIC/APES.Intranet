<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LegalNoticeDismissal extends Model
{
    protected $fillable = [
        'user_id',
        'legal_page_id',
        'legal_page_version_id',
        'dismissed_at',
    ];

    protected function casts(): array
    {
        return [
            'dismissed_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<LegalPage, $this>
     */
    public function legalPage(): BelongsTo
    {
        return $this->belongsTo(LegalPage::class);
    }

    /**
     * @return BelongsTo<LegalPageVersion, $this>
     */
    public function legalPageVersion(): BelongsTo
    {
        return $this->belongsTo(LegalPageVersion::class);
    }
}
