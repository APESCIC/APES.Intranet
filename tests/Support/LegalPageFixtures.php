<?php

namespace Tests\Support;

use App\Core\Legal\LegalPages as LegalPagesService;
use App\Core\Legal\LegalPageVisibility;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use App\Models\User;
use Database\Seeders\LegalPagesSeeder;
use Illuminate\Support\Carbon;

final class LegalPageFixtures
{
    public static function page(string $slug, string $title, string $visibility = LegalPageVisibility::Everyone): LegalPage
    {
        return LegalPage::query()->create([
            'slug' => $slug,
            'title' => $title,
            'visibility' => $visibility,
            'needs_acceptance' => false,
            'review_interval_months' => 12,
            'locked' => false,
        ]);
    }

    public static function publishVersion(
        LegalPage $page,
        string $body,
        ?Carbon $inEffectAt = null,
        ?User $publisher = null,
        ?string $changeSummary = null,
    ): LegalPageVersion {
        $legalPages = app(LegalPagesService::class);
        $version = $legalPages->createDraft($page, $body, $changeSummary);
        $legalPages->publish($version, $publisher, $inEffectAt);

        return $version->fresh();
    }

    public static function publishPrivacyNotice(): void
    {
        $page = self::page('privacy', 'Privacy notice');
        self::publishVersion($page, LegalPagesSeeder::privacyDraftBody());
    }
}
