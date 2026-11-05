<?php

namespace App\Core\System\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TestEmail extends Notification
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
            ->subject('APES Intranet: test email')
            ->line('This is a test email from Admin > System status. If you can read it, the intranet can send email.')
            ->line('You asked for it at '.now()->timezone('Europe/London')->format('j M Y, H:i').'.');
    }
}
