<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Support\HtmlSanitizer;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Models\HandbookAcknowledgement;
use Modules\Communications\Models\HandbookChapter;
use Modules\Communications\Models\HandbookVersion;

class HandbookController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $q = trim((string) $request->query('q', ''));
        $chapters = HandbookChapter::query()
            ->where('published', true)
            ->with('currentVersion')
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get();

        if ($q !== '') {
            $chapters = $chapters->filter(function (HandbookChapter $chapter) use ($q) {
                $hay = strtolower($chapter->title.' '.($chapter->currentVersion->body ?? ''));

                return str_contains($hay, strtolower($q));
            })->values();
        }

        return view('communications::handbook.index', [
            'chapters' => $chapters,
            'q' => $q,
            'canPublish' => $request->user()->can(CommunicationsPermissions::PUBLISH),
        ]);
    }

    public function show(Request $request, HandbookChapter $chapter): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);
        abort_unless($chapter->published, 404);

        $versionNumber = $request->integer('version') ?: null;
        $version = $versionNumber
            ? $chapter->versions()->where('version_number', $versionNumber)->firstOrFail()
            : $chapter->currentVersion;

        abort_if($version === null, 404);

        $acked = HandbookAcknowledgement::query()
            ->where('handbook_version_id', $version->id)
            ->where('user_id', $request->user()->id)
            ->exists();

        return view('communications::handbook.show', [
            'chapter' => $chapter,
            'version' => $version,
            'versions' => $chapter->versions()->orderByDesc('version_number')->get(),
            'acknowledged' => $acked,
            'canPublish' => $request->user()->can(CommunicationsPermissions::PUBLISH),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        return view('communications::handbook.create');
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'slug' => ['nullable', 'string', 'max:200'],
        ]);

        $slug = $data['slug'] ?? Str::slug($data['title']);

        $chapter = HandbookChapter::query()->create([
            'slug' => $slug,
            'title' => $data['title'],
            'sort_order' => (int) HandbookChapter::query()->max('sort_order') + 1,
            'published' => true,
        ]);

        HandbookVersion::query()->create([
            'handbook_chapter_id' => $chapter->id,
            'version_number' => 1,
            'body' => $sanitizer->clean($data['body']),
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        return redirect()->route('communications.handbook.show', $chapter)
            ->with('status', 'Chapter published.');
    }

    public function publishVersion(Request $request, HandbookChapter $chapter, HtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        $data = $request->validate(['body' => ['required', 'string']]);
        $next = (int) $chapter->versions()->max('version_number') + 1;

        HandbookVersion::query()->create([
            'handbook_chapter_id' => $chapter->id,
            'version_number' => $next,
            'body' => $sanitizer->clean($data['body']),
            'published_by' => $request->user()->id,
            'published_at' => now(),
        ]);

        return redirect()->route('communications.handbook.show', $chapter)
            ->with('status', 'New version published. Members need to acknowledge again.');
    }

    public function acknowledge(Request $request, HandbookChapter $chapter): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);
        $version = $chapter->currentVersion;
        abort_if($version === null, 404);

        HandbookAcknowledgement::query()->firstOrCreate(
            ['handbook_version_id' => $version->id, 'user_id' => $request->user()->id],
            ['acknowledged_at' => now()],
        );

        return back()->with('status', 'Acknowledgement recorded.');
    }

    public function exportAcknowledgements(Request $request, HandbookChapter $chapter): Response
    {
        abort_unless(
            $request->user()?->can(AccessMatrix::HR_ACCESS)
            || $request->user()?->can(CommunicationsPermissions::PUBLISH),
            403
        );

        $version = $chapter->currentVersion;
        abort_if($version === null, 404);

        $ackedIds = HandbookAcknowledgement::query()
            ->where('handbook_version_id', $version->id)
            ->pluck('user_id');

        $users = User::query()->orderBy('name')->get(['id', 'name', 'email']);

        $lines = ['name,email,acknowledged,acknowledged_at'];
        foreach ($users as $user) {
            $ack = HandbookAcknowledgement::query()
                ->where('handbook_version_id', $version->id)
                ->where('user_id', $user->id)
                ->first();
            $lines[] = sprintf(
                '"%s","%s","%s","%s"',
                str_replace('"', '""', $user->name),
                $user->email,
                $ack ? 'yes' : 'no',
                $ack?->acknowledged_at?->toIso8601String() ?? '',
            );
        }

        return response(implode("\n", $lines)."\n", 200, [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="handbook-'.$chapter->slug.'-acks.csv"',
        ]);
    }
}
