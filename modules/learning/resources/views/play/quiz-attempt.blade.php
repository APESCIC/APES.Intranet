<x-layouts.app :title="$quiz->title.' — attempt'">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">{{ $quiz->title }}</h1>
        <p class="mt-2 text-sm text-ink-muted">Attempt {{ $attempt->attempt_number }}
            @if ($attempt->due_at)
                · due {{ $attempt->due_at->timezone(config('app.timezone'))->format('H:i:s') }}
            @endif
        </p>

        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <form method="post" action="{{ route('learning.play.quiz.submit', [$course, $quiz, $attempt]) }}" class="mt-8 space-y-8">
            @csrf
            @foreach ($questions as $index => $question)
                <fieldset class="border-t border-border pt-6">
                    <legend class="font-semibold text-ink">Question {{ $index + 1 }}</legend>
                    <div class="mt-2 prose text-ink">{!! $question->stem !!}</div>
                    @if ($question->type->value === 'mcq' && is_array($question->options))
                        <div class="mt-3 space-y-2">
                            @foreach ($question->options as $key => $label)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="radio" name="answers[{{ $question->id }}]" value="{{ $key }}" required>
                                    <span>{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($question->type->value === 'true_false')
                        <div class="mt-3 space-y-2">
                            <label class="flex items-center gap-2 text-sm"><input type="radio" name="answers[{{ $question->id }}]" value="1" required> True</label>
                            <label class="flex items-center gap-2 text-sm"><input type="radio" name="answers[{{ $question->id }}]" value="0" required> False</label>
                        </div>
                    @else
                        <textarea name="answers[{{ $question->id }}]" class="sg-input mt-3" rows="4" @if($question->type->value !== 'essay') required @endif></textarea>
                    @endif
                </fieldset>
            @endforeach
            <button class="sg-btn sg-btn-primary" type="submit">Submit attempt</button>
        </form>
    </div>
</x-layouts.app>
