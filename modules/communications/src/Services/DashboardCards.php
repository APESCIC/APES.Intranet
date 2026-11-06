<?php

namespace Modules\Communications\Services;

use App\Core\Access\AccessMatrix;
use App\Core\Navigation\Menu;
use App\Models\User;
use Modules\Communications\Models\DashboardDismissal;
use Modules\Communications\Models\Event;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Models\UpdateRead;

class DashboardCards
{
    public function __construct(private readonly MicrosoftLinkRegistry $links) {}

    /**
     * @return list<array{key: string, title: string, body: string, href: ?string, dismissible?: bool}>
     */
    public function for(User $user): array
    {
        $cards = [];

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

        // Modules not yet built: hide empty cards (rota/tasks/tickets).
        if ($user->can(AccessMatrix::STUDENT_AREA)) {
            $cards[] = [
                'key' => 'student-area',
                'title' => 'Student placements',
                'body' => 'Open your placements and hours.',
                'href' => route('people.students.index'),
            ];
        }

        if ($this->shouldShowTwoFactorNudge($user)) {
            $cards[] = [
                'key' => 'two-factor-nudge',
                'title' => 'Set up a passkey or authenticator',
                'body' => 'Add a second sign-in method so your APES account stays safer.',
                'href' => route('people.security'),
                'dismissible' => true,
            ];
        }

        $apps = $this->links->forUser($user);
        if ($apps->isNotEmpty()) {
            $cards[] = [
                'key' => 'm365-apps',
                'title' => 'Microsoft 365 apps',
                'body' => 'Open the apps you use at APES.',
                'href' => null,
                'apps' => $apps,
            ];
        }

        $cards[] = [
            'key' => 'teams',
            'title' => 'Chat in Teams',
            'body' => 'Chat stays in Microsoft Teams.',
            'href' => Menu::TEAMS_URL,
            'external' => true,
        ];

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

        // Prefer showing everyone; if totp_enabled is known false/null still nudge.
        if (array_key_exists('totp_enabled', $user->getAttributes()) && $user->totp_enabled === true) {
            return false;
        }

        return true;
    }
}
