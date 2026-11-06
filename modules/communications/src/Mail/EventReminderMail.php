<?php

namespace Modules\Communications\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Modules\Communications\Models\Event;

class EventReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Event $event) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Event reminder: '.$this->event->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            text: 'communications::mail.event-reminder',
        );
    }
}
