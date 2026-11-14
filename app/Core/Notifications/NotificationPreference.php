<?php

namespace App\Core\Notifications;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property array<string, array<string, bool>> $channels
 */
class NotificationPreference extends Model
{
    protected $fillable = [
        'user_id',
        'channels',
        'quiet_hours_start',
        'quiet_hours_end',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'channels' => 'array',
            'quiet_hours_start' => 'datetime:H:i',
            'quiet_hours_end' => 'datetime:H:i',
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
     * Defaults: push off, email and in-app on (email matches today's behaviour).
     *
     * @return array<string, array<string, bool>>
     */
    public static function defaultChannels(): array
    {
        $channels = [];
        foreach (NotificationCategory::ordered() as $category) {
            $channels[$category->value] = [
                NotificationChannel::Push->value => false,
                NotificationChannel::Email->value => true,
                NotificationChannel::InApp->value => true,
            ];
        }

        return $channels;
    }

    public static function forUser(User $user): self
    {
        return $user->notificationPreference()->firstOrCreate([], [
            'channels' => self::defaultChannels(),
        ]);
    }

    public function allows(NotificationCategory $category, NotificationChannel $channel): bool
    {
        return (bool) data_get($this->channels, $category->value.'.'.$channel->value, false);
    }

    /**
     * Quiet hours use Europe/London. Account and security can still notify.
     */
    public function isInQuietHours(?\DateTimeInterface $now = null): bool
    {
        if ($this->quiet_hours_start === null || $this->quiet_hours_end === null) {
            return false;
        }

        $now = Carbon::parse($now)->timezone('Europe/London');
        $start = $now->copy()->setTimeFromTimeString($this->quiet_hours_start->format('H:i'));
        $end = $now->copy()->setTimeFromTimeString($this->quiet_hours_end->format('H:i'));

        if ($start->equalTo($end)) {
            return false;
        }

        if ($start->lessThan($end)) {
            return $now->betweenIncluded($start, $end);
        }

        return $now->greaterThanOrEqualTo($start) || $now->lessThanOrEqualTo($end);
    }

    public function shouldSend(
        NotificationCategory $category,
        NotificationChannel $channel,
        ?\DateTimeInterface $now = null,
    ): bool {
        if (! $this->allows($category, $channel)) {
            return false;
        }

        if ($channel === NotificationChannel::Push
            && $category !== NotificationCategory::AccountSecurity
            && $this->isInQuietHours($now)) {
            return false;
        }

        return true;
    }
}
