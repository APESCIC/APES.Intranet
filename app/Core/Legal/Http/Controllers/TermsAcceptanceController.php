<?php

namespace App\Core\Legal\Http\Controllers;

use App\Core\Legal\TermsAcceptance;
use App\Models\LegalPage;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TermsAcceptanceController
{
    public function __construct(private readonly TermsAcceptance $termsAcceptance) {}

    public function show(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $version = $this->termsAcceptance->requiredVersion($user);
        if ($version === null) {
            return redirect()->intended(route('dashboard'));
        }

        $page = $this->termsAcceptance->termsPage();
        abort_if($page === null, 404);

        return view('core.legal.accept', [
            'page' => $page,
            'version' => $version,
            'summary' => $version->change_summary ?: 'Please read the Terms of use and confirm you accept them.',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        $version = $this->termsAcceptance->requiredVersion($user);
        abort_if($version === null, 403);

        $this->termsAcceptance->accept($user, $version);

        return redirect()->intended(route('dashboard'))
            ->with('status', 'Thank you. Your acceptance of the Terms of use has been recorded.');
    }

    public function dismissNotice(Request $request, LegalPage $legalPage): RedirectResponse
    {
        $user = $request->user();
        abort_if($user === null, 403);

        abort_unless(in_array($legalPage->slug, ['privacy', 'cookies'], true), 404);

        $this->termsAcceptance->dismissUpdatedNotice($user, $legalPage);

        return back()->with('status', 'Noted. We will not show that update message again.');
    }
}
