<?php

namespace App\Core\System\Notifications;

use App\Core\System\Console\RunSystemChecks;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AddonDown extends Notification
{
    /**
     * @param  list<string>  $addons  Addon labels, never error details.
     */
    public function __construct(public readonly array $addons) {}

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
            ->subject('APES Intranet: '.implode(', ', $this->addons).' down')
            ->line('The intranet\'s health check has failed '.RunSystemChecks::ALERT_AFTER.' times in a row for: '.implode(', ', $this->addons).'.')
            ->line('You will get one email per outage. The page shows when it is back.')
            ->action('Open System status', route('admin.system.index'));
    }
}
