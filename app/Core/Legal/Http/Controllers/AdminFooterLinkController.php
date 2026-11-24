<?php

namespace App\Core\Legal\Http\Controllers;

use App\Core\Audit\AuditLogger;
use App\Core\Legal\FooterLinkGroup;
use App\Core\Legal\FooterLinkTargetType;
use App\Core\Legal\LegalAuditEvents;
use App\Core\Legal\LegalPageVisibility;
use App\Models\FooterLink;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

final class AdminFooterLinkController
{
    public function index(): View
    {
        $links = FooterLink::query()
            ->orderBy('group')
            ->orderBy('sort_order')
            ->get()
            ->groupBy('group');

        return view('core.legal.admin.footer-links.index', [
            'grouped' => $links,
            'groups' => FooterLinkGroup::all(),
        ]);
    }

    public function store(Request $request, AuditLogger $audit): RedirectResponse
    {
        $data = $this->validateLink($request);

        $sortOrder = (int) (FooterLink::query()
            ->where('group', $data['group'])
            ->max('sort_order') ?? 0) + 10;

        $link = FooterLink::query()->create([
            ...$data,
            'sort_order' => $sortOrder,
            'locked' => false,
        ]);

        $audit->record(LegalAuditEvents::FOOTER_UPDATED, $request->user(), [
            'action' => 'created',
            'footer_link_id' => $link->id,
        ]);

        return redirect()
            ->route('admin.legal.footer-links.index')
            ->with('status', 'Footer link added.');
    }

    public function update(Request $request, FooterLink $footerLink, AuditLogger $audit): RedirectResponse
    {
        if ($footerLink->locked) {
            $this->assertLockedUpdateAllowed($request, $footerLink);
        }

        $data = $this->validateLink($request);

        $footerLink->update($data);

        $audit->record(LegalAuditEvents::FOOTER_UPDATED, $request->user(), [
            'action' => 'updated',
            'footer_link_id' => $footerLink->id,
        ]);

        return redirect()
            ->route('admin.legal.footer-links.index')
            ->with('status', 'Footer link updated.');
    }

    public function destroy(Request $request, FooterLink $footerLink, AuditLogger $audit): RedirectResponse
    {
        if ($footerLink->locked) {
            abort(403);
        }

        $id = $footerLink->id;
        $footerLink->delete();

        $audit->record(LegalAuditEvents::FOOTER_UPDATED, $request->user(), [
            'action' => 'deleted',
            'footer_link_id' => $id,
        ]);

        return redirect()
            ->route('admin.legal.footer-links.index')
            ->with('status', 'Footer link removed.');
    }

    public function reorder(Request $request, FooterLink $footerLink): RedirectResponse
    {
        $data = $request->validate([
            'direction' => ['required', Rule::in(['up', 'down'])],
        ]);

        $siblings = FooterLink::query()
            ->where('group', $footerLink->group)
            ->orderBy('sort_order')
            ->get();

        $index = $siblings->search(fn (FooterLink $link) => $link->id === $footerLink->id);
        if ($index === false) {
            return redirect()->route('admin.legal.footer-links.index');
        }

        $swap = $data['direction'] === 'up' ? $index - 1 : $index + 1;
        if ($swap < 0 || $swap >= $siblings->count()) {
            return redirect()->route('admin.legal.footer-links.index');
        }

        $other = $siblings[$swap];
        $currentOrder = $footerLink->sort_order;
        $footerLink->update(['sort_order' => $other->sort_order]);
        $other->update(['sort_order' => $currentOrder]);

        return redirect()->route('admin.legal.footer-links.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateLink(Request $request, ?FooterLink $existing = null): array
    {
        return $request->validate([
            'group' => ['required', Rule::in(FooterLinkGroup::all())],
            'label' => ['required', 'string', 'max:120'],
            'icon' => ['nullable', 'string', 'max:80'],
            'target_type' => ['required', Rule::in(FooterLinkTargetType::all())],
            'target_value' => ['required', 'string', 'max:500'],
            'visibility' => ['required', Rule::in(LegalPageVisibility::all())],
            'module_slug' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function assertLockedUpdateAllowed(Request $request, FooterLink $link): void
    {
        $visibility = $request->input('visibility');
        if ($visibility !== null && $visibility !== $link->visibility) {
            abort(422, 'This link is locked and cannot be hidden or have its visibility changed.');
        }

        if ($request->boolean('remove') || $request->boolean('hidden')) {
            abort(422, 'This link is locked and cannot be hidden.');
        }
    }
}
