<x-layouts.app :title="$assignment->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.play.show', $course) }}" class="underline">{{ $course->title }}</a>
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">{{ $assignment->title }}</h1>
        @if ($assignment->instructions)
            <div class="mt-4 prose text-ink">{!! $assignment->instructions !!}</div>
        @endif

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <section class="mt-8">
            <h2 class="font-display text-2xl text-ink">Your versions</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($versions as $version)
                    <li class="py-3 text-sm">
                        <p class="font-semibold text-ink">Version {{ $version->version_number }}
                            <span class="font-normal text-ink-muted">· {{ $version->submitted_at->timezone(config('app.timezone'))->toDayDateTimeString() }}</span>
                        </p>
                        @if ($version->text_body)
                            <div class="mt-2 prose text-ink">{!! $version->text_body !!}</div>
                        @endif
                        @if ($version->hasFile())
                            <p class="mt-2">
                                <a class="underline" href="{{ route('learning.play.assignment.download', [$course, $assignment, $version]) }}">
                                    Download {{ $version->original_filename }}
                                </a>
                            </p>
                        @endif
                    </li>
                @empty
                    <li class="py-3 text-ink-muted">No submissions yet.</li>
                @endforelse
            </ul>
        </section>

        <form method="post" action="{{ route('learning.play.assignment.submit', [$course, $assignment]) }}" enctype="multipart/form-data" class="mt-8 space-y-3 border-t border-border pt-6">
            @csrf
            <h2 class="font-semibold text-ink">Submit a new version</h2>
            @if ($assignment->allow_text)
                <label class="block text-sm">Text
                    <textarea name="text_body" class="sg-input mt-1" rows="5"></textarea>
                </label>
            @endif
            @if ($assignment->allow_file)
                <label class="block text-sm">File
                    <input type="file" name="file" class="sg-input mt-1">
                </label>
                @if ($assignment->allowed_extensions)
                    <p class="text-sm text-ink-muted">Allowed: {{ implode(', ', $assignment->allowed_extensions) }}</p>
                @endif
            @endif
            <button class="sg-btn sg-btn-primary" type="submit">Submit</button>
        </form>

        <section class="mt-10 border-t border-border pt-6">
            <h2 class="font-display text-2xl text-ink">Feedback</h2>
            @if ($feedback)
                <div class="mt-3 prose text-ink">{!! $feedback->body !!}</div>
            @else
                <p class="mt-3 text-ink-muted">No released feedback yet.</p>
            @endif
        </section>
    </div>
</x-layouts.app>
