<?php

namespace App\Http\Controllers;

use App\Core\Legal\LegalPages;
use App\Core\Legal\LegalPageVisibility;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LegalPageController
{
    public function __construct(private readonly LegalPages $legalPages) {}

    public function show(Request $request, string $slug): View|RedirectResponse
    {
        $page = $this->legalPages->findBySlug($slug);
        abort_if($page === null, 404);

        if ($page->visibility === LegalPageVisibility::SignedIn && $request->user() === null) {
            return redirect()->guest(route('login'));
        }

        $version = $this->legalPages->currentVersion($page);
        abort_if($version === null, 404);

        $body = $this->legalPages->bodyWithHeadingIds($version->body);

        return view('core.legal.show', [
            'page' => $page,
            'version' => $version,
            'body' => $body,
            'contents' => $this->legalPages->contentsNavigation($body),
        ]);
    }

    public function versions(Request $request, string $slug): View|RedirectResponse
    {
        $page = $this->legalPages->findBySlug($slug);
        abort_if($page === null, 404);

        if ($page->visibility === LegalPageVisibility::SignedIn && $request->user() === null) {
            return redirect()->guest(route('login'));
        }

        return view('core.legal.versions', [
            'page' => $page,
            'versions' => $this->legalPages->publishedVersions($page),
        ]);
    }
}
