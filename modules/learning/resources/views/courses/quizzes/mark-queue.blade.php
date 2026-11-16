<x-layouts.app :title="'Mark queue — '.$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.quizzes.index', $course) }}" class="underline">Quizzes</a>
            · Manual mark queue
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">Manual mark queue</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <ul class="mt-8 divide-y divide-border">
            @forelse ($queue as $answer)
                <li class="py-4">
                    <p class="text-sm text-ink-muted">
                        {{ $answer->attempt->user->name }} · {{ $answer->attempt->quiz->title }}
                        · attempt {{ $answer->attempt->attempt_number }}
                    </p>
                    <div class="mt-2 prose text-ink">{!! $answer->question->stem !!}</div>
                    <div class="mt-2 rounded border border-border p-3 text-ink">{!! $answer->response !!}</div>
                    <form method="post" action="{{ route('learning.courses.quizzes.mark', [$course, $answer->attempt->quiz, $answer->id]) }}" class="mt-3 flex flex-wrap gap-2">
                        @csrf
                        <input type="number" step="0.01" name="score" class="sg-input" min="0" max="{{ $answer->question->points }}" placeholder="Score / {{ $answer->question->points }}" required>
                        <input name="feedback" class="sg-input" placeholder="Feedback (optional)">
                        <button class="sg-btn sg-btn-primary" type="submit">Mark</button>
                    </form>
                </li>
            @empty
                <li class="py-4 text-ink-muted">Nothing waiting to be marked.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
