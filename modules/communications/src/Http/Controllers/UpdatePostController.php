<?php

namespace Modules\Communications\Http\Controllers;

use App\Core\Access\AccessMatrix;
use App\Core\Support\HtmlSanitizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Modules\Communications\CommunicationsPermissions;
use Modules\Communications\Models\UpdateComment;
use Modules\Communications\Models\UpdatePost;
use Modules\Communications\Models\UpdateRead;

class UpdatePostController
{
    public function index(Request $request): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);

        $posts = UpdatePost::query()
            ->with('author')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderByDesc('pinned')
            ->orderByDesc('published_at')
            ->get()
            ->filter(fn (UpdatePost $post) => $post->isVisibleTo($request->user()))
            ->values();

        return view('communications::updates.index', [
            'posts' => $posts,
            'canPublish' => $request->user()->can(CommunicationsPermissions::PUBLISH),
        ]);
    }

    public function create(Request $request): View
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        return view('communications::updates.create');
    }

    public function store(Request $request, HtmlSanitizer $sanitizer): RedirectResponse
    {
        abort_unless($request->user()?->can(CommunicationsPermissions::PUBLISH), 403);

        $data = $request->validate([
            'title' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string'],
            'audiences' => ['nullable', 'array'],
            'audiences.*' => ['string'],
            'pinned' => ['sometimes', 'boolean'],
            'comments_enabled' => ['sometimes', 'boolean'],
            'email_alert' => ['sometimes', 'boolean'],
        ]);

        $post = UpdatePost::query()->create([
            'author_id' => $request->user()->id,
            'title' => $data['title'],
            'body' => $sanitizer->clean($data['body']),
            'audiences' => $data['audiences'] ?? [],
            'pinned' => (bool) ($data['pinned'] ?? false),
            'comments_enabled' => (bool) ($data['comments_enabled'] ?? true),
            'email_alert' => (bool) ($data['email_alert'] ?? false),
            'published_at' => now(),
        ]);

        return redirect()->route('communications.updates.show', $post)
            ->with('status', 'Update published.');
    }

    public function show(Request $request, UpdatePost $update): View
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);
        abort_unless($update->isVisibleTo($request->user()), 403);

        UpdateRead::query()->firstOrCreate(
            ['update_post_id' => $update->id, 'user_id' => $request->user()->id],
            ['read_at' => now()],
        );

        $update->load(['author', 'comments.user']);

        return view('communications::updates.show', [
            'post' => $update,
            'readCount' => $update->reads()->count(),
        ]);
    }

    public function comment(Request $request, UpdatePost $update): RedirectResponse
    {
        abort_unless($request->user()?->can(AccessMatrix::INTRANET_ACCESS), 403);
        abort_unless($update->isVisibleTo($request->user()), 403);
        abort_unless($update->comments_enabled, 403);

        $data = $request->validate(['body' => ['required', 'string', 'max:2000']]);

        UpdateComment::query()->create([
            'update_post_id' => $update->id,
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        return back()->with('status', 'Comment added.');
    }
}
