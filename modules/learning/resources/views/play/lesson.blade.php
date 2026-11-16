<x-layouts.app :title="$lesson->title.' — '.$course->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <nav class="text-sm text-ink-muted" aria-label="Breadcrumb">
            <ol class="flex flex-wrap gap-x-2 gap-y-1">
                <li><a href="{{ route('learning.mine') }}" class="underline">My learning</a></li>
                <li aria-hidden="true">/</li>
                <li><a href="{{ route('learning.play.show', $course) }}" class="underline">{{ $course->title }}</a></li>
                <li aria-hidden="true">/</li>
                <li aria-current="page">{{ $lesson->title }}</li>
            </ol>
        </nav>

        <h1 class="mt-4 font-display text-3xl text-ink">{{ $lesson->title }}</h1>
        <p class="mt-2 text-sm text-ink-muted">
            Version {{ $version->version_number }}
            · Course progress {{ $summary['percent'] }}%
        </p>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @if ($errors->any())
            <ul class="mt-4 text-sm text-danger" role="alert">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif

        <article class="prose mt-8 max-w-none text-ink" aria-label="Lesson content">
            {!! $version->body !!}
        </article>

        <div class="mt-10 flex flex-col gap-3 border-t border-border pt-6 sm:flex-row sm:flex-wrap">
            <a href="{{ route('learning.play.show', $course) }}" class="sg-btn">Back to course</a>
            @if (! $progress?->isCompleted())
                <form method="post" action="{{ route('learning.play.complete', [$course, $lesson]) }}">
                    @csrf
                    <button type="submit" class="sg-btn sg-btn-primary">Mark lesson complete</button>
                </form>
            @else
                <p class="text-sm text-ink-muted self-center" role="status">This lesson is complete.</p>
            @endif
        </div>
    </div>
</x-layouts.app>
