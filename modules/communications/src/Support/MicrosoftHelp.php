<?php

namespace Modules\Communications\Support;

use Illuminate\Support\Facades\Route;

/**
 * The help note shown with the Microsoft 365 links, for members who land on
 * a Microsoft sign-in or "no licence" page. Every app stays visible to
 * everyone (decided 4 November 2026); the note explains what to do instead.
 */
final class MicrosoftHelp
{
    public const HANDBOOK_CHAPTER = 'microsoft-365-accounts';

    public const HELPDESK_TOPIC = 'microsoft-365';

    public static function note(): string
    {
        return 'Seeing a Microsoft sign-in or "no licence" page? Most staff have a Microsoft 365 licence; volunteers and students may have a lighter set of apps.';
    }

    /**
     * The help desk link is left out if the help desk module is not installed.
     *
     * @return array<string, string>
     */
    public static function links(): array
    {
        $links = ['Read the handbook' => route('communications.handbook.show', self::HANDBOOK_CHAPTER)];

        if (Route::has('helpdesk.tickets.create')) {
            $links['Ask IT'] = route('helpdesk.tickets.create', ['category' => self::HELPDESK_TOPIC]);
        }

        return $links;
    }
}
