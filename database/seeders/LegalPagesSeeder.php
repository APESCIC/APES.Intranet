<?php

namespace Database\Seeders;

use App\Core\Legal\LegalPageVersionStatus;
use App\Core\Legal\LegalPageVisibility;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use Illuminate\Database\Seeder;

class LegalPagesSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            ['slug' => 'privacy', 'title' => 'Privacy notice', 'visibility' => LegalPageVisibility::Everyone],
            ['slug' => 'cookies', 'title' => 'Cookies', 'visibility' => LegalPageVisibility::Everyone],
            ['slug' => 'terms', 'title' => 'Terms of use', 'visibility' => LegalPageVisibility::Everyone],
            ['slug' => 'accessibility', 'title' => 'Accessibility statement', 'visibility' => LegalPageVisibility::Everyone],
            ['slug' => 'hosting-and-security', 'title' => 'Hosting and security', 'visibility' => LegalPageVisibility::SignedIn],
        ];

        foreach ($pages as $definition) {
            LegalPage::query()->firstOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $definition['title'],
                    'visibility' => $definition['visibility'],
                    'needs_acceptance' => false,
                    'review_interval_months' => 12,
                    'locked' => false,
                ],
            );
        }

        $privacy = LegalPage::query()->where('slug', 'privacy')->firstOrFail();

        if ($privacy->versions()->doesntExist()) {
            LegalPageVersion::query()->create([
                'legal_page_id' => $privacy->id,
                'number' => 1,
                'body' => self::privacyDraftBody(),
                'change_summary' => 'Placeholder draft migrated from the static privacy view.',
                'status' => LegalPageVersionStatus::Draft,
                'material_change' => false,
            ]);
        }
    }

    public static function privacyDraftBody(): string
    {
        $auditDays = (int) config('intranet.retention.audit_log_days');

        return <<<HTML
<h2 class="font-serif text-xl font-semibold">Who we are</h2>
<p>The APES Intranet is run by the Association of Protecting Exotic Species CIC (APES CIC) for its staff, volunteers and students.</p>
<h2 class="font-serif text-xl font-semibold">What we record</h2>
<ul class="list-disc space-y-1 pl-6">
<li>Your name, email address and intranet roles, taken from your APES Cloudron account when you sign in.</li>
<li>Profile details you choose to share (team, skills, contact details, photo) and, for HR and management, emergency contact details and employee number.</li>
<li>A yes/no flag for whether Cloudron reports that you have authenticator-app (TOTP) two-factor authentication turned on. Passkeys and secrets are never stored here.</li>
<li>Operations records: your rota shifts, times you say you cannot work, claims and swaps, clock-in, break and clock-out times, timesheets, tasks, form submissions and checklists, including any photos or signatures you add to them.</li>
<li>Only if APES CIC has approved and switched on the clock-in location check for a site: whether your device was near that site when you tapped Clock in (near or not near). Your location itself is never saved, and nothing is checked at any other time.</li>
<li>An audit log of sign-ins, sign-outs, role changes and admin actions, with the time, your account, your IP address and your browser.</li>
<li>If you opt in to browser notifications: a push subscription endpoint and encryption keys for each device you allow. Those endpoints go to the browser's push service (for example Google, Mozilla or Apple). Lock-screen wording stays generic and never includes ticket or message content. We delete a subscription when you remove the device, when the push service says it has expired, when the account is deactivated, or after twelve months with no successful use.</li>
</ul>
<h2 class="font-serif text-xl font-semibold">Why</h2>
<p>To let you use the intranet, to keep it secure, and to show who did what if something goes wrong.</p>
<h2 class="font-serif text-xl font-semibold">How long we keep it</h2>
<p>Audit log entries are deleted automatically after {$auditDays} days.</p>
<h2 class="font-serif text-xl font-semibold">Questions</h2>
<p>Contact the APES CIC Director or the APES IT Administrator.</p>
HTML;
    }
}
