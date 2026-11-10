<?php

namespace Modules\Operations\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FormSubmission extends Model
{
    protected $table = 'operations_form_submissions';

    protected $fillable = ['form_id', 'user_id', 'routed_to_user_id', 'routed_to_role'];

    /**
     * @return BelongsTo<Form, $this>
     */
    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * @return HasMany<FormAnswer, $this>
     */
    public function answers(): HasMany
    {
        return $this->hasMany(FormAnswer::class, 'submission_id');
    }

    public function isRoutedTo(User $user): bool
    {
        return (int) $this->routed_to_user_id === (int) $user->getKey()
            || ($this->routed_to_role !== null && $user->hasRole($this->routed_to_role));
    }

    /**
     * @param  Builder<FormSubmission>  $query
     * @return Builder<FormSubmission>
     */
    public function scopeRoutedTo(Builder $query, User $user): Builder
    {
        return $query->where(fn (Builder $q) => $q
            ->where('routed_to_user_id', $user->getKey())
            ->orWhereIn('routed_to_role', $user->getRoleNames()->all()));
    }
}
