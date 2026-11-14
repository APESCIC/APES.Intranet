<?php

namespace Modules\Hr\Services;

use App\Core\Communications\Contracts\PublishIntranetUpdate;
use App\Models\User;
use Modules\Hr\Models\Recognition;
use RuntimeException;

final class RecognitionService
{
    public function __construct(private readonly PublishIntranetUpdate $updates) {}

    public function give(User $giver, User $recipient, string $message, bool $postToFeed): Recognition
    {
        if ((int) $giver->id === (int) $recipient->id) {
            throw new RuntimeException('You cannot give recognition to yourself.');
        }

        $recognition = Recognition::query()->create([
            'giver_id' => $giver->id,
            'recipient_id' => $recipient->id,
            'message' => $message,
            'posted_to_feed' => false,
        ]);

        if ($postToFeed) {
            $postId = $this->updates->publish(
                $giver,
                'Recognition for '.$recipient->name,
                '<p>'.e($giver->name).' recognised <strong>'.e($recipient->name).'</strong>: '.e($message).'</p>',
            );
            $recognition->forceFill([
                'posted_to_feed' => true,
                'update_post_id' => $postId,
            ])->save();
        }

        return $recognition->refresh();
    }
}
