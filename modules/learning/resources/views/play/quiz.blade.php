<x-layouts.app :title="$quiz->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.play.show', $course) }}" class="underline">{{ $course->title }}</a>
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">{{ $quiz->title }}</h1>
        @if ($quiz->instructions)
            <div class="mt-4 prose text-ink">{!! $quiz->instructions !!}</div>
        @endif

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <dl class="mt-6 grid gap-2 text-sm text-ink-muted">
            <div><dt class="inline font-semibold text-ink">Attempts left:</dt> <dd class="inline">{{ $remaining === null ? 'Unlimited' : $remaining }}</dd></div>
            <div><dt class="inline font-semibold text-ink">Time limit:</dt> <dd class="inline">{{ $effectiveSeconds === null ? 'None' : $effectiveSeconds.' seconds' }}</dd></div>
        </dl>

        @if ($openAttempt)
            <p class="mt-6">
                <a href="{{ route('learning.play.quiz.attempt', [$course, $quiz, $openAttempt]) }}" class="sg-btn sg-btn-primary">Continue attempt</a>
            </p>
        @elseif ($remaining === null || $remaining > 0)
            <form method="post" action="{{ route('learning.play.quiz.start', [$course, $quiz]) }}" class="mt-6">
                @csrf
                <button class="sg-btn sg-btn-primary" type="submit">Start attempt</button>
            </form>
        @else
            <p class="mt-6 text-ink-muted">No attempts remaining.</p>
        @endif
    </div>
</x-layouts.app>
