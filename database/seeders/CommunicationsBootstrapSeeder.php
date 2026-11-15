<?php

namespace Database\Seeders;

use App\Core\Support\HtmlSanitizer;
use Illuminate\Database\Seeder;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\HandbookVersion;

/**
 * Deploy-safe communications defaults (no fake personal data). Runs on every
 * Cloudron deploy after RolesAndPermissionsSeeder (deploy/cloudron/activate-release.sh).
 * Existing chapters are left as admins edited them. Microsoft 365 links are
 * seeded by Microsoft365BootstrapSeeder.
 */
class CommunicationsBootstrapSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(CommunicationsPermissionsSeeder::class);

        $sanitizer = app(HtmlSanitizer::class);

        $this->publishChapter(
            'sign-in-and-2fa',
            'Sign-in methods and 2FA',
            10,
            $sanitizer->clean(<<<'HTML'
<p>Use a passkey or authenticator app as a second sign-in method. You do not need a smartphone: a hardware security key, a password manager with TOTP, or a desktop authenticator app also work.</p>
<p>If you lose your device, contact the IT help desk for a reset. More detail lives on the Security page.</p>
HTML),
        );

        $this->publishChapter(
            'microsoft-365-accounts',
            'Microsoft 365 accounts and licences',
            20,
            $sanitizer->clean(<<<'HTML'
<p>Your Microsoft account is separate from Cloudron sign-in. Most staff have a Microsoft 365 licence; volunteers and students may have a lighter set of apps.</p>
<p>Bookings and Loop are staff only for now. Planner is for team projects and needs a Microsoft 365 licence — do not start a premium trial.</p>
<p>If you land on a Microsoft sign-in or “no licence” page, check this handbook and ask IT for help.</p>
HTML),
        );
    }

    private function publishChapter(string $slug, string $title, int $sortOrder, string $body): void
    {
        $chapter = HandbookChapter::query()->firstOrCreate(
            ['slug' => $slug],
            ['title' => $title, 'sort_order' => $sortOrder, 'published' => true],
        );

        if ($chapter->versions()->count() === 0) {
            HandbookVersion::query()->create([
                'handbook_chapter_id' => $chapter->id,
                'version_number' => 1,
                'body' => $body,
                'published_at' => now(),
            ]);
        }
    }
}
