<?php

namespace Modules\Helpdesk\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'helpdesk_categories';

    protected $fillable = ['slug', 'name', 'queue', 'default_priority', 'active', 'sort_order'];

    public function queueLabel(): string
    {
        return Ticket::QUEUES[$this->queue] ?? $this->queue;
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * @param  Builder<Category>  $query
     * @return Builder<Category>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('queue')->orderBy('sort_order')->orderBy('name');
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }
}
