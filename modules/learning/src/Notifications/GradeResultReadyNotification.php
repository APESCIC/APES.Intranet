<?php

namespace Modules\Learning\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Modules\Learning\Models\GradeItem;

/**
 * Tells a learner a result is ready without including any grade value.
 */
class GradeResultReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly GradeItem $gradeItem,
        public readonly string $courseTitle,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'A learning result is ready',
            'body' => 'A result for '.$this->gradeItem->title.' on '.$this->courseTitle.' is ready to view.',
            'grade_item_id' => $this->gradeItem->id,
            // Never include raw_score, percent, pass/fail or marks.
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A learning result is ready')
            ->line('A result for '.$this->gradeItem->title.' on '.$this->courseTitle.' is ready to view.')
            ->line('Sign in to the intranet to open your grades. This message does not include the grade itself.');
    }
}
