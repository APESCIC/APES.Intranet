<x-layouts.app :title="$page->title" subtitle="Edit draft legal page">
    @if (session('status'))
        <p class="mb-4 rounded-sg-field bg-sg-ok-mist p-3 text-sg-ok" role="status">{{ session('status') }}</p>
    @endif

    <p class="mb-4 text-sm">
        <a href="{{ route('admin.legal.pages.index') }}" class="underline">Back to legal pages</a>
    </p>

    <nav class="mb-6 flex flex-wrap gap-2" role="tablist" aria-label="Editor views">
        <span class="sg-btn sg-btn-secondary sg-btn-sm" role="tab" aria-selected="true">Write</span>
        <a
            href="{{ route('admin.legal.pages.preview', $page) }}"
            class="sg-btn sg-btn-ghost sg-btn-sm"
            role="tab"
            aria-selected="false"
        >Preview</a>
    </nav>

    @if ($published)
        <p class="mb-4 text-sm text-sg-muted">
            Live version {{ $published->number }} is published on the site. This draft is version {{ $draft->number }}.
        </p>
    @else
        <p class="mb-4 text-sm text-sg-muted">No published version yet. You are editing draft version {{ $draft->number }}.</p>
    @endif

    <form method="post" action="{{ route('admin.legal.pages.draft', $page) }}" class="grid max-w-3xl gap-5">
        @csrf

        <div>
            <label for="change_summary" class="mb-1 block text-sm font-semibold text-sg-ink">Change summary</label>
            <input
                type="text"
                name="change_summary"
                id="change_summary"
                value="{{ old('change_summary', $draft->change_summary) }}"
                class="sg-input w-full"
                maxlength="500"
            />
            @error('change_summary')
                <p class="mt-1 text-sm text-sg-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="in_effect_at" class="mb-1 block text-sm font-semibold text-sg-ink">In effect from (optional)</label>
            <input
                type="date"
                name="in_effect_at"
                id="in_effect_at"
                value="{{ old('in_effect_at', $draft->in_effect_at?->timezone(config('app.timezone'))->format('Y-m-d')) }}"
                class="sg-input w-full"
            />
            <p class="mt-1 text-xs text-sg-muted">Stored on the draft for when you publish later.</p>
            @error('in_effect_at')
                <p class="mt-1 text-sm text-sg-error">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="visibility" class="mb-1 block text-sm font-semibold text-sg-ink">Who can see this page</label>
            <select name="visibility" id="visibility" class="sg-input w-full">
                <option value="{{ \App\Core\Legal\LegalPageVisibility::Everyone }}" @selected(old('visibility', $page->visibility) === \App\Core\Legal\LegalPageVisibility::Everyone)>Everyone</option>
                <option value="{{ \App\Core\Legal\LegalPageVisibility::SignedIn }}" @selected(old('visibility', $page->visibility) === \App\Core\Legal\LegalPageVisibility::SignedIn)>Signed in</option>
            </select>
            @error('visibility')
                <p class="mt-1 text-sm text-sg-error">{{ $message }}</p>
            @enderror
        </div>

        @if ($page->needs_acceptance)
            <p class="flex items-start gap-2 text-sm">
                <input
                    type="checkbox"
                    name="material_change"
                    id="material_change"
                    value="1"
                    class="mt-1"
                    @checked(old('material_change', $draft->material_change))
                />
                <label for="material_change">
                    <span class="font-semibold text-sg-ink">Material change</span>
                    <span class="block text-sg-muted">People will need to accept the new terms when this version is published.</span>
                </label>
            </p>
            @error('material_change')
                <p class="text-sm text-sg-error">{{ $message }}</p>
            @enderror
        @endif

        <x-editor.tiptap name="body" label="Page body" :value="old('body', $draft->body)" required />

        @error('body')
            <p class="text-sm text-sg-error">{{ $message }}</p>
        @enderror

        <button class="sg-btn sg-btn-primary justify-self-start" type="submit">Save draft</button>
    </form>
</x-layouts.app>