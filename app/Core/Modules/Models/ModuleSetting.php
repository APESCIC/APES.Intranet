<?php

namespace App\Core\Modules\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $slug
 * @property string $key
 * @property mixed $value
 */
class ModuleSetting extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }
}
