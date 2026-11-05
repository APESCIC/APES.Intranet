<?php

namespace App\Core\Access\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessReviewDue extends Notification
{
    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('APES Intranet: quarterly access review due')
            ->line('It is time for the quarterly review of intranet groups and access.')
            ->line('Check people with more than one senior role, possible leavers, people in no group, and admin or management views of DBS and health data. Changes are made in Cloudron.')
            ->action('Open the access review', route('admin.access-review.index'));
    }
}
