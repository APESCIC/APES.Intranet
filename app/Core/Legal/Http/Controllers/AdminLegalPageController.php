<?php

namespace App\Core\Legal\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Legal\CookieRegister;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPages;
use App\Core\Legal\LegalPageVersionStatus;
use App\Core\Legal\LegalPageVisibility;
use App\Models\LegalPage;
use App\Models\LegalPageVersion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminLegalPageController
{
    public function __construct(
        private readonly LegalPages $legalPages,
        private readonly CookieRegister $cookieRegister,
    ) {}

    public function index(): View
    {
        $pages = LegalPage::query()->orderBy('title')->get();
        $rows = $pages->map(fn (LegalPage $page) => $this->indexRow($page));

        return view('core.legal.admin.pages.index', [
            'rows' => $rows,
        ]);
    }

    public function edit(LegalPage $legalPage): View
    {
        $draft = $this->legalPages->openDraft($legalPage);
        $published = $this->legalPages->currentVersion($legalPage);

        return view('core.legal.admin.pages.edit', [
            'page' => $legalPage,
            'draft' => $draft,
            'published' => $published,
            'activeTab' => 'write',
        ]);
    }

    public function saveDraft(Request $request, LegalPage $legalPage, AuditLogger $audit): RedirectResponse
    {
        $draft = $this->legalPages->draftFor($legalPage) ?? $this->legalPages->openDraft($legalPage);

        $rules = [
            'body' => ['required', 'string'],
            'change_summary' => ['nullable', 'string', 'max:500'],
            'in_effect_at' => ['nullable', 'date'],
            'visibility' => ['required', Rule::in(LegalPageVisibility::all())],
        ];

        if ($legalPage->needs_acceptance) {
            $rules['material_change'] = ['boolean'];
        }

        $data = $request->validate($rules);

        $inEffectAt = isset($data['in_effect_at']) && $data['in_effect_at'] !== ''
            ? Carbon::parse($data['in_effect_at'])
            : null;

        $materialChange = $legalPage->needs_acceptance && $request->boolean('material_change');

        $legalPage->update([
            'visibility' => $data['visibility'],
        ]);

        $this->legalPages->updateDraft(
            $draft,
            $data['body'],
            $data['change_summary'] ?? null,
            $materialChange,
            $inEffectAt,
        );

        $audit->record(LegalAuditEvents::PAGE_DRAFT_SAVED, $request->user(), [
            'legal_page_id' => $legalPage->id,
            'legal_page_version_id' => $draft->id,
            'slug' => $legalPage->slug,
        ]);

        return redirect()
            ->route('admin.legal.pages.edit', $legalPage)
            ->with('status', 'Draft saved.');
    }

    public function preview(LegalPage $legalPage): View
    {
        $draft = $this->legalPages->draftFor($legalPage);
        abort_if($draft === null, 404);

        $body = $this->legalPages->bodyWithHeadingIds($draft->body);

        $cookieRegister = $legalPage->slug === 'cookies' ? $this->cookieRegister->entries() : null;

        return view('core.legal.admin.pages.preview', [
            'page' => $legalPage,
            'version' => $draft,
            'body' => $body,
            'contents' => $body !== '' ? $this->legalPages->contentsNavigation($body) : [],
            'cookieRegister' => $cookieRegister,
        ]);
    }

    public function publish(Request $request, LegalPage $legalPage, AuditLogger $audit): RedirectResponse
    {
        $draft = $this->legalPages->draftFor($legalPage);
        abort_if($draft === null, 404);

        $data = $request->validate([
            'in_effect_at' => ['nullable', 'date'],
            'mode' => ['required', Rule::in(['now', 'schedule'])],
        ]);

        $inEffectAt = null;
        if ($data['mode'] === 'schedule' && isset($data['in_effect_at']) && $data['in_effect_at'] !== '') {
            $inEffectAt = Carbon::parse($data['in_effect_at'], config('app.timezone'))->startOfDay();
        }

        $this->legalPages->publish($draft, $request->user(), $inEffectAt);

        $draft->refresh();

        $audit->record(LegalAuditEvents::PAGE_PUBLISHED, $legalPage, [
            'legal_page_id' => $legalPage->id,
            'legal_page_version_id' => $draft->id,
            'slug' => $legalPage->slug,
            'version_number' => $draft->number,
            'in_effect_at' => $inEffectAt?->toIso8601String(),
        ], $request->user());

        $message = $inEffectAt !== null && $inEffectAt->isFuture()
            ? 'Version '.$draft->number.' is scheduled.'
            : 'Version '.$draft->number.' is now published.';

        return redirect()
            ->route('admin.legal.pages.index')
            ->with('status', $message);
    }

    public function history(LegalPage $legalPage): View
    {
        return view('core.legal.admin.pages.history', [
            'page' => $legalPage,
            'versions' => $this->legalPages->allVersions($legalPage),
            'draft' => $this->legalPages->draftFor($legalPage),
        ]);
    }

    public function compare(Request $request, LegalPage $legalPage): View
    {
        $data = $request->validate([
            'from' => ['required', 'integer'],
            'to' => ['required', 'integer'],
        ]);

        $from = LegalPageVersion::query()
            ->where('legal_page_id', $legalPage->id)
            ->whereKey($data['from'])
            ->firstOrFail();

        $to = LegalPageVersion::query()
            ->where('legal_page_id', $legalPage->id)
            ->whereKey($data['to'])
            ->firstOrFail();

        return view('core.legal.admin.pages.compare', [
            'page' => $legalPage,
            'fromVersion' => $from,
            'toVersion' => $to,
            'diff' => $this->legalPages->compare($from, $to),
        ]);
    }

    public function restore(Request $request, LegalPage $legalPage, LegalPageVersion $legalPageVersion, AuditLogger $audit): RedirectResponse
    {
        abort_if($legalPageVersion->legal_page_id !== $legalPage->id, 404);

        $draft = $this->legalPages->restore($legalPage, $legalPageVersion);

        $audit->record(LegalAuditEvents::PAGE_RESTORED, $legalPage, [
            'legal_page_id' => $legalPage->id,
            'legal_page_version_id' => $draft->id,
            'restored_from_version_id' => $legalPageVersion->id,
            'restored_from_number' => $legalPageVersion->number,
            'slug' => $legalPage->slug,
        ], $request->user());

        return redirect()
            ->route('admin.legal.pages.edit', $legalPage)
            ->with('status', 'Draft updated from version '.$legalPageVersion->number.'.');
    }

    /**
     * @return array{
     *     page: LegalPage,
     *     status: string,
     *     version_label: string,
     *     in_effect: ?string,
     *     visibility_label: string,
     *     acceptance: string,
     *     last_edited: ?string,
     *     review_due: ?string,
     *     has_draft: bool,
     * }
     */
    private function indexRow(LegalPage $page): array
    {
        $draft = $this->legalPages->draftFor($page);
        $published = $this->legalPages->currentVersion($page);
        $scheduled = $page->versions()
            ->where('status', LegalPageVersionStatus::Published)
            ->whereNotNull('in_effect_at')
            ->where('in_effect_at', '>', now())
            ->orderByDesc('number')
            ->first();

        $status = match (true) {
            $draft !== null => 'Draft',
            $published !== null => 'Published',
            $scheduled !== null => 'Scheduled',
            default => 'No live version',
        };

        $versionForLabel = $draft ?? $published ?? $scheduled;
        $versionLabel = $versionForLabel !== null ? (string) $versionForLabel->number : '—';

        $inEffect = null;
        if ($published?->in_effect_at) {
            $inEffect = $published->in_effect_at->timezone(config('app.timezone'))->format('j M Y');
        } elseif ($published !== null) {
            $inEffect = 'Now';
        }

        $visibilityLabel = $page->visibility === LegalPageVisibility::Everyone
            ? 'Everyone'
            : 'Signed in';

        $lastEdited = $this->lastEditedAt($draft, $published);

        $reviewDue = null;
        if ($published?->published_at && $page->review_interval_months > 0) {
            $reviewDue = $published->published_at
                ->addMonths($page->review_interval_months)
                ->timezone(config('app.timezone'))
                ->format('j M Y');
        }

        return [
            'page' => $page,
            'status' => $status,
            'version_label' => $versionLabel,
            'in_effect' => $inEffect,
            'visibility_label' => $visibilityLabel,
            'acceptance' => $page->needs_acceptance ? 'Required' : '—',
            'last_edited' => $lastEdited,
            'review_due' => $reviewDue,
            'has_draft' => $draft !== null,
        ];
    }

    private function lastEditedAt(?LegalPageVersion $draft, ?LegalPageVersion $published): ?string
    {
        $candidates = array_filter([$draft?->updated_at, $published?->updated_at]);
        if ($candidates === []) {
            return null;
        }

        $latest = collect($candidates)->max();

        return $latest->timezone(config('app.timezone'))->format('j M Y H:i');
    }
}
