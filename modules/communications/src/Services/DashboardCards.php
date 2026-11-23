<?php

namespace Modules\Communications\Services;

use App\Core\Access\AccessMatrix;
use App\Core\Dashboard\DashboardCardContributor;
use App\Core\Modules\BelongsToModule;
use App\Core\Modules\ModuleRegistry;
use App\Models\User;
use Modules\Communications\Models\DashboardDismissal;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Models\UpdateRead;

class DashboardCards
{
    /**
     * @var array<string, string>
     */
    private const SECTION_HEADINGS = [
        'today' => 'Today',
        'updates' => 'Updates',
        'help-desk' => 'My help desk',
        'm365' => 'Microsoft 365 apps',
        'events' => 'Events',
    ];

    /**
     * @return list<array{key: string, heading: string, cards: list<array<string, mixed>>}>
     */
    public function groupedFor(User $user): array
    {
        $buckets = array_fill_keys(array_keys(self::SECTION_HEADINGS), []);

        foreach ($this->for($user) as $card) {
            $buckets[$this->sectionFor($card)][] = $card;
        }

        $sections = [];
        foreach (self::SECTION_HEADINGS as $key => $heading) {
            if ($buckets[$key] === []) {
                continue;
            }

            $sections[] = [
                'key' => $key,
                'heading' => $heading,
                'cards' => $buckets[$key],
            ];
        }

        return $sections;
    }

    /**
     * @param  array{key: string}  $card
     */
    private function sectionFor(array $card): string
    {
        return match ($card['key']) {
            'unread-updates' => 'updates',
            'upcoming-event' => 'events',
            'm365-apps' => 'm365',
            default => str_starts_with($card['key'], 'helpdesk-') ? 'help-desk' : 'today',
        };
    }

    /**
     * @return list<array{key: string, title: string, body: string, href: ?string, dismissible?: bool}>
     */
    public function for(User $user): array
    {
        $cards = [];
        $modules = app(ModuleRegistry::class);

        if ($modules->enabled('communications')) {
            $unread = $this->unreadUpdates($user);
            if ($unread > 0) {
                $cards[] = [
                    'key' => 'unread-updates',
                    'title' => 'Unread updates',
                    'body' => $unread === 1 ? '1 update waiting for you.' : "{$unread} updates waiting for you.",
                    'href' => route('communications.updates.index'),
                ];
            }

            $upcoming = Event::query()->where('starts_at', '>=', now())->orderBy('starts_at')->limit(1)->first();
            if ($upcoming) {
                $cards[] = [
                    'key' => 'upcoming-event',
                    'title' => 'Upcoming event',
                    'body' => $upcoming->title.' · '.$upcoming->starts_at->timezone(config('app.timezone'))->format('D j M g:ia'),
                    'href' => route('communications.events.show', $upcoming),
                ];
            }
        }

        if ($modules->enabled('people') && $user->can(AccessMatrix::STUDENT_AREA)) {
            $cards[] = [
                'key' => 'student-area',
                'title' => 'Student placements',
                'body' => 'Open your placements and hours.',
                'href' => route('people.students.index'),
            ];
        }

        if ($modules->enabled('people') && $this->shouldShowTwoFactorNudge($user)) {
            $cards[] = [
                'key' => 'two-factor-nudge',
                'title' => 'Set up a passkey or authenticator',
                'body' => 'Add a second sign-in method so your APES account stays safer.',
                'href' => route('people.security'),
                'dismissible' => true,
            ];
        }

        foreach (app()->tagged('intranet.dashboard') as $contributor) {
            if (! $contributor instanceof DashboardCardContributor) {
                continue;
            }

            if ($contributor instanceof BelongsToModule && ! $modules->enabled($contributor->moduleSlug())) {
                continue;
            }

            array_push($cards, ...$contributor->cards($user));
        }

        return $cards;
    }

    private function unreadUpdates(User $user): int
    {
        $posts = UpdatePost::query()
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->get()
            ->filter(fn (UpdatePost $post) => $post->isVisibleTo($user));

        if ($posts->isEmpty()) {
            return 0;
        }

        $readIds = UpdateRead::query()
            ->where('user_id', $user->id)
            ->whereIn('update_post_id', $posts->pluck('id'))
            ->pluck('update_post_id');

        return $posts->whereNotIn('id', $readIds)->count();
    }

    private function shouldShowTwoFactorNudge(User $user): bool
    {
        if (! config('communications.two_factor_nudge.enabled')) {
            return false;
        }

        $dismissal = DashboardDismissal::query()
            ->where('user_id', $user->id)
            ->where('card_key', 'two-factor-nudge')
            ->first();

        if ($dismissal && $dismissal->dismissed_until->isFuture()) {
            return false;
        }

        if (array_key_exists('totp_enabled', $user->getAttributes()) && $user->totp_enabled === true) {
            return false;
        }

        return true;
    }
}
