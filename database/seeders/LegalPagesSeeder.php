<?php

namespace Database\Seeders;

use App\Core\Legal\LegalPages;
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
            ['slug' => 'terms', 'title' => 'Terms of use', 'visibility' => LegalPageVisibility::Everyone, 'needs_acceptance' => true],
            ['slug' => 'accessibility', 'title' => 'Accessibility statement', 'visibility' => LegalPageVisibility::Everyone],
            ['slug' => 'hosting-and-security', 'title' => 'Hosting and security', 'visibility' => LegalPageVisibility::SignedIn],
        ];

        foreach ($pages as $definition) {
            LegalPage::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'title' => $definition['title'],
                    'visibility' => $definition['visibility'],
                    'needs_acceptance' => $definition['needs_acceptance'] ?? false,
                    'review_interval_months' => 12,
                    'locked' => false,
                ],
            );
        }

        $this->seedDraftVersion(
            'privacy',
            self::privacyDraftBody(),
            'Placeholder draft migrated from the static privacy view.',
        );
        $this->seedDraftVersion(
            'cookies',
            self::cookiesDraftBody(),
            'Headings-only draft for the owner to complete from the cookie register.',
        );
        $this->seedDraftVersion(
            'terms',
            self::termsDraftBody(),
            'Headings-only draft for the owner to complete, including acceptable use.',
        );
        $this->seedDraftVersion(
            'accessibility',
            self::accessibilityDraftBody(),
            'Headings-only draft for the owner to complete.',
        );

        $this->seedHostingAndSecurityPage();

        $this->call(FooterLinksSeeder::class);
    }

    private function seedDraftVersion(string $slug, string $body, string $changeSummary): void
    {
        $page = LegalPage::query()->where('slug', $slug)->firstOrFail();

        if ($page->versions()->exists()) {
            return;
        }

        LegalPageVersion::query()->create([
            'legal_page_id' => $page->id,
            'number' => 1,
            'body' => $body,
            'change_summary' => $changeSummary,
            'status' => LegalPageVersionStatus::Draft,
            'material_change' => false,
        ]);
    }

    private function seedHostingAndSecurityPage(): void
    {
        $page = LegalPage::query()->where('slug', 'hosting-and-security')->firstOrFail();

        if ($page->versions()->where('status', LegalPageVersionStatus::Published)->exists()) {
            return;
        }

        if ($page->versions()->exists()) {
            return;
        }

        $legalPages = app(LegalPages::class);
        $version = $legalPages->createDraft(
            $page,
            self::hostingAndSecurityBody(),
            changeSummary: 'Initial published wording for signed-in staff.',
        );
        $legalPages->publish($version);
    }

    public static function hostingAndSecurityBody(): string
    {
        return <<<'HTML'
<h2 class="font-serif text-xl font-semibold">Where APES apps run</h2>
<p>The APES Intranet and related Cloudron applications run on <strong>Akamai Connected Cloud (Linode)</strong> in the <strong>London</strong> region.</p>
<h2 class="font-serif text-xl font-semibold">Provider certifications</h2>
<p>Akamai publishes certifications and assurance reports that cover Linode compute. We do not reproduce those documents here; read them on Akamai's official sites:</p>
<ul class="list-disc space-y-1 pl-6">
<li><a href="https://trust.akamai.com/">Akamai Trust Centre</a> (<a href="https://trust.akamai.com/">trust.akamai.com</a>)</li>
<li><a href="https://www.akamai.com/legal/compliance">Akamai legal compliance</a> (<a href="https://www.akamai.com/legal/compliance">www.akamai.com/legal/compliance</a>)</li>
</ul>
<h2 class="font-serif text-xl font-semibold">Disk encryption on this server</h2>
<p>Disk encryption of this Linode was confirmed by Bambie on 12 November 2026 (Europe/London).</p>
<h2 class="font-serif text-xl font-semibold">What we do not claim</h2>
<ul class="list-disc space-y-1 pl-6">
<li>We do <strong>not</strong> claim SOC 2 Type II, SOC 1, SOC 3, CSA STAR or HDS for this hosting.</li>
<li>Cyber Essentials covers Akamai's UK offices and staff, not the servers that run APES applications.</li>
<li>This page does not list server identifiers, account numbers or internal review findings.</li>
</ul>
HTML;
    }

    public static function cookiesDraftBody(): string
    {
        return <<<'HTML'
<h2 class="font-serif text-xl font-semibold">What cookies we use</h2>
<h2 class="font-serif text-xl font-semibold">Strictly necessary cookies</h2>
<h2 class="font-serif text-xl font-semibold">How long they last</h2>
<h2 class="font-serif text-xl font-semibold">How to contact us</h2>
HTML;
    }

    public static function termsDraftBody(): string
    {
        return <<<'HTML'
<h2 class="font-serif text-xl font-semibold">Who we are</h2>
<h2 class="font-serif text-xl font-semibold">Acceptable use</h2>
<h2 class="font-serif text-xl font-semibold">Your account</h2>
<h2 class="font-serif text-xl font-semibold">Changes</h2>
<h2 class="font-serif text-xl font-semibold">Contact</h2>
HTML;
    }

    public static function accessibilityDraftBody(): string
    {
        return <<<'HTML'
<h2 class="font-serif text-xl font-semibold">WCAG 2.2 AA status</h2>
<h2 class="font-serif text-xl font-semibold">Known issues</h2>
<h2 class="font-serif text-xl font-semibold">How to report a problem</h2>
<h2 class="font-serif text-xl font-semibold">Contact</h2>
HTML;
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
