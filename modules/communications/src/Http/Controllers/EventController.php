<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Mail\EventReminderMail;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\EventRsvp;

class EventController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $events = Event::query()->orderBy('starts_at')->get();

        return view('communications::events.index', [
            'events' => $events,
            'canPublish' => $request->user()->can(CommunicationsPermissions::PUBLISH),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        return view('communications::events.create');
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['nullable', 'string'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'location' => ['nullable', 'string', 'max:200'],
            'reminder_at' => ['nullable', 'date'],
        ]);

        $event = Event::query()->create([
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $sanitizer->clean($data['body'] ?? ''),
            'starts_at' => $data['starts_at'],
            'ends_at' => $data['ends_at'] ?? null,
            'location' => $data['location'] ?? null,
            'reminder_at' => $data['reminder_at'] ?? null,
        ]);

        return redirect()->route('communications.events.show', $event)
            ->with('status', 'Event published.');
    }

    public function show(Request $request, Event $event): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $rsvp = $event->rsvps()->where('user_id', $request->user()->id)->first();

        return view('communications::events.show', [
            'event' => $event,
            'rsvp' => $rsvp,
        ]);
    }

    public function rsvp(Request $request, Event $event): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $data = $request->validate([
            'status' => ['required', 'in:going,maybe,declined'],
        ]);

        EventRsvp::query()->updateOrCreate(
            ['event_id' => $event->id, 'user_id' => $request->user()->id],
            ['status' => $data['status']],
        );

        return back()->with('status', 'RSVP saved.');
    }

    public function ical(Request $request, Event $event): Response
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $uid = 'event-'.$event->id.'@intranet.apes.org.uk';
        $dtStart = $event->starts_at->utc()->format('Ymd\THis\Z');
        $dtEnd = ($event->ends_at ?? $event->starts_at->copy()->addHour())->utc()->format('Ymd\THis\Z');
        $summary = str_replace([',', "\n", "\r"], ['\,', ' ', ''], $event->title);
        $location = str_replace([',', "\n", "\r"], ['\,', ' ', ''], (string) $event->location);

        $ics = implode("\r\n", [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//APES CIC//Intranet//EN',
            'BEGIN:VEVENT',
            'UID:'.$uid,
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$dtStart,
            'DTEND:'.$dtEnd,
            'SUMMARY:'.$summary,
            'LOCATION:'.$location,
            'END:VEVENT',
            'END:VCALENDAR',
            '',
        ]);

        return response($ics, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="event-'.$event->id.'.ics"',
        ]);
    }

    public function sendDueReminders(): int
    {
        $due = Event::query()
            ->whereNotNull('reminder_at')
            ->whereNull('reminder_sent_at')
            ->where('reminder_at', '<=', now())
            ->get();

        foreach ($due as $event) {
            foreach ($event->rsvps()->where('status', 'going')->get() as $rsvp) {
                $user = User::query()->find($rsvp->user_id);
                if ($user?->email) {
                    Mail::to($user->email)->send(new EventReminderMail($event));
                }
            }

            $event->forceFill(['reminder_sent_at' => now()])->save();
        }

        return $due->count();
    }
}
