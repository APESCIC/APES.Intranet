<?php

namespace Modules\Hr\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppraisalCycle extends Model
{
    protected $table = 'hr_appraisal_cycles';

    protected $fillable = ['name', 'year', 'starts_on', 'ends_on', 'open'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'starts_on' => 'date',
            'ends_on' => 'date',
            'open' => 'boolean',
            'year' => 'integer',
        ];
    }

    /**
     * @return HasMany<Appraisal, $this>
     */
    public function appraisals(): HasMany
    {
        return $this->hasMany(Appraisal::class, 'cycle_id');
    }
}
