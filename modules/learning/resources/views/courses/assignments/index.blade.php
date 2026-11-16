<x-layouts.app :title="'Assignments — '.$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.show', $course) }}" class="underline">{{ $course->title }}</a>
            · Assignments
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">Assignments</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <ul class="mt-8 divide-y divide-border">
            @forelse ($course->courseAssignments as $assignment)
                <li class="py-3">
                    <p class="font-semibold text-ink">{{ $assignment->title }}</p>
                    <p class="text-sm text-ink-muted">{{ ucfirst($assignment->status) }}
                        @if ($assignment->allow_file) · files @endif
                        @if ($assignment->allow_text) · text @endif
                    </p>
                    @if (! $assignment->isPublished())
                        <form method="post" action="{{ route('learning.courses.assignments.publish', [$course, $assignment]) }}" class="mt-2">
                            @csrf
                            <button class="sg-btn" type="submit">Publish</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('learning.courses.assignments.feedback', [$course, $assignment]) }}" class="mt-3 space-y-2">
                        @csrf
                        <p class="text-sm font-semibold text-ink">Write feedback</p>
                        <input type="number" name="user_id" class="sg-input" placeholder="Learner user id" required>
                        <textarea name="body" class="sg-input" rows="3" required></textarea>
                        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="release" value="1"> Release to learner now</label>
                        <button class="sg-btn" type="submit">Save feedback</button>
                    </form>
                </li>
            @empty
                <li class="py-3 text-ink-muted">No assignments yet.</li>
            @endforelse
        </ul>

        <form method="post" action="{{ route('learning.courses.assignments.store', $course) }}" class="mt-10 space-y-3 border-t border-border pt-6">
            @csrf
            <h2 class="font-display text-2xl text-ink">Create assignment</h2>
            <label class="block text-sm">Title
                <input name="title" class="sg-input mt-1" required maxlength="255">
            </label>
            <label class="block text-sm">Instructions
                <textarea name="instructions" class="sg-input mt-1" rows="3"></textarea>
            </label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_text" value="1" checked> Allow text</label>
            <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="allow_file" value="1" checked> Allow file</label>
            <label class="block text-sm">Allowed extensions (comma-separated)
                <input name="allowed_extensions" class="sg-input mt-1" placeholder="pdf,docx">
            </label>
            <label class="block text-sm">Max file bytes
                <input type="number" name="max_file_bytes" class="sg-input mt-1" min="1" placeholder="1048576">
            </label>
            <button class="sg-btn sg-btn-primary" type="submit">Create</button>
        </form>
    </div>
</x-layouts.app>
