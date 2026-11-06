<?php

namespace Modules\Communications\Support;

/**
 * Build Teams deep links without storing tokens.
 */
final class TeamsDeepLink
{
    public static function chatWithEmail(string $email): string
    {
        $encoded = rawurlencode($email);

        return "https://teams.microsoft.com/l/chat/0/0?users={$encoded}";
    }

    public static function channel(string $teamId, string $channelId): string
    {
        return 'https://teams.microsoft.com/l/channel/'.rawurlencode($channelId).'/'.$teamId;
    }
}
