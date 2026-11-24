<?php

namespace App\Core\Legal;

use App\Core\Audit\AuditLogger;
use App\Models\LegalAcceptance;
use App\Models\LegalNoticeDismissal;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use App\Models\User;

final class TermsAcceptance
{
    /** @var list<string> */
    private const UPDATED_NOTICE_SLUGS = ['privacy', 'cookies'];

    public function __construct(
        private readonly LegalPages $legalPages,
        private readonly AuditLogger $audit,
    ) {}

    public function isEnabled(): bool
    {
        return (bool) config('legal.acceptance.enabled', true);
    }

    public function termsPage(): ?LegalPage
    {
        $page = $this->legalPages->findBySlug('terms');

        return $page !== null && $page->needs_acceptance ? $page : null;
    }

    public function requiredVersion(?User $user): ?LegalPageVersion
    {
        if ($user === null || ! $this->isEnabled()) {
            return null;
        }

        $page = $this->termsPage();
        if ($page === null) {
            return null;
        }

        $current = $this->legalPages->currentVersion($page);
        if ($current === null) {
            return null;
        }

        if ($this->hasAccepted($user, $current)) {
            return null;
        }

        $latestAcceptance = LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->whereHas('legalPageVersion', fn ($query) => $query->where('legal_page_id', $page->id))
            ->with('legalPageVersion')
            ->latest('accepted_at')
            ->first();

        if ($latestAcceptance === null) {
            return $current;
        }

        $acceptedVersion = $latestAcceptance->legalPageVersion;
        if ($acceptedVersion === null || $acceptedVersion->number >= $current->number) {
            return null;
        }

        return $current->material_change ? $current : null;
    }

    public function hasAccepted(User $user, LegalPageVersion $version): bool
    {
        return LegalAcceptance::query()
            ->where('user_id', $user->id)
            ->where('legal_page_version_id', $version->id)
            ->exists();
    }

    public function accept(User $user, LegalPageVersion $version): void
    {
        $page = $version->page;
        if ($page === null || ! $page->needs_acceptance) {
            return;
        }

        $acceptance = LegalAcceptance::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'legal_page_version_id' => $version->id,
            ],
            [
                'accepted_at' => now(),
            ],
        );

        if ($acceptance->wasRecentlyCreated) {
            $this->audit->record(LegalAuditEvents::TERMS_ACCEPTED, $version, [
                'legal_page_id' => $page->id,
                'version' => $version->number,
            ], $user);
        }
    }

    /**
     * @return list<array{page: LegalPage, version: LegalPageVersion}>
     */
    public function pendingUpdatedNotices(User $user): array
    {
        $pending = [];
        foreach (self::UPDATED_NOTICE_SLUGS as $slug) {
            $notice = $this->pendingUpdatedNotice($user, $slug);
            if ($notice !== null) {
                $pending[] = $notice;
            }
        }

        return $pending;
    }

    /**
     * @return array{page: LegalPage, version: LegalPageVersion}|null
     */
    public function pendingUpdatedNotice(User $user, string $slug): ?array
    {
        if (! in_array($slug, self::UPDATED_NOTICE_SLUGS, true)) {
            return null;
        }

        $page = $this->legalPages->findBySlug($slug);
        if ($page === null) {
            return null;
        }

        $version = $this->legalPages->currentVersion($page);
        if ($version === null || $version->number <= 1) {
            return null;
        }

        $dismissed = LegalNoticeDismissal::query()
            ->where('user_id', $user->id)
            ->where('legal_page_version_id', $version->id)
            ->exists();

        if ($dismissed) {
            return null;
        }

        return ['page' => $page, 'version' => $version];
    }

    public function dismissUpdatedNotice(User $user, LegalPage $page): void
    {
        $version = $this->legalPages->currentVersion($page);
        if ($version === null) {
            return;
        }

        LegalNoticeDismissal::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'legal_page_version_id' => $version->id,
            ],
            [
                'legal_page_id' => $page->id,
                'dismissed_at' => now(),
            ],
        );
    }
}
