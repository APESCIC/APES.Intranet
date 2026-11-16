<x-layouts.app :title="'Edit: '.$lesson->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.show', $course) }}" class="underline">{{ $course->title }}</a>
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">Edit lesson</h1>
        <p class="mt-2 text-sm text-ink-muted">Saving updates the draft only. Publish to create a new version.</p>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif

        <form method="post" action="{{ route('learning.courses.lessons.update', [$course, $lesson]) }}" class="mt-6 grid gap-4">
            @csrf
            @method('PUT')
            <div>
                <label class="mb-1 block text-sm font-semibold" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title', $lesson->title) }}" class="sg-input w-full" required maxlength="200">
            </div>
            <x-editor.tiptap name="body" label="Lesson body" :value="old('body', $lesson->draft_body)" />
            @error('body') <p class="text-sm text-danger">{{ $message }}</p> @enderror
            <div class="flex flex-wrap gap-2">
                <button class="sg-btn" type="submit">Save draft</button>
            </div>
        </form>

        <form method="post" action="{{ route('learning.courses.lessons.publish', [$course, $lesson]) }}" class="mt-4">
            @csrf
            <button class="sg-btn sg-btn-primary" type="submit">Publish new version</button>
        </form>

        @if ($lesson->versions->isNotEmpty())
            <h2 class="mt-10 font-display text-xl text-ink">Published versions</h2>
            <ul class="mt-3 text-sm text-ink-muted">
                @foreach ($lesson->versions->sortByDesc('version_number') as $version)
                    <li>v{{ $version->version_number }} · {{ $version->published_at?->timezone(config('app.timezone'))->format('d M Y H:i') }}</li>
                @endforeach
            </ul>
        @endif
    </div>
</x-layouts.app>
