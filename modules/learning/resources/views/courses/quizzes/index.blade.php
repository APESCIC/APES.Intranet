<x-layouts.app :title="'Quizzes — '.$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.show', $course) }}" class="underline">{{ $course->title }}</a>
            · Question bank and quizzes
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">Question bank and quizzes</h1>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif
        @foreach ($errors->all() as $error)
            <p class="mt-2 text-sm text-danger" role="alert">{{ $error }}</p>
        @endforeach

        <p class="mt-4">
            <a href="{{ route('learning.courses.quizzes.mark-queue', $course) }}" class="sg-btn">Manual mark queue</a>
        </p>

        <section class="mt-10">
            <h2 class="font-display text-2xl text-ink">Question bank</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($course->questions as $question)
                    <li class="py-3">
                        <p class="font-semibold text-ink">{!! $question->stem !!}</p>
                        <p class="text-sm text-ink-muted">{{ $question->type->label() }} · {{ $question->points }} pts
                            @if ($question->needs_manual_mark) · manual mark @endif
                        </p>
                    </li>
                @empty
                    <li class="py-3 text-ink-muted">No questions yet.</li>
                @endforelse
            </ul>

            <form method="post" action="{{ route('learning.courses.questions.store', $course) }}" class="mt-6 space-y-3 border-t border-border pt-6">
                @csrf
                <h3 class="font-semibold text-ink">Add question</h3>
                <label class="block text-sm">Type
                    <select name="type" class="sg-input mt-1" required>
                        @foreach ($questionTypes as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="block text-sm">Stem
                    <textarea name="stem" class="sg-input mt-1" rows="3" required></textarea>
                </label>
                <label class="block text-sm">Options (MCQ keys a,b,c… — one per line as key=label)
                    <textarea name="options_raw" class="sg-input mt-1" rows="3" placeholder="a=Red&#10;b=Blue"></textarea>
                </label>
                <input type="hidden" name="options[a]" value="" id="opt-placeholder" disabled>
                <label class="block text-sm">Correct answer (MCQ key, true/false, or short text)
                    <input name="correct_answer" class="sg-input mt-1">
                </label>
                <label class="block text-sm">Points
                    <input type="number" name="points" class="sg-input mt-1" min="1" value="1">
                </label>
                <button class="sg-btn sg-btn-primary" type="submit">Add question</button>
            </form>
            <script>
                document.currentScript.previousElementSibling?.closest('form')?.addEventListener('submit', function (e) {
                    const raw = this.querySelector('[name="options_raw"]');
                    if (!raw || !raw.value.trim()) return;
                    raw.value.trim().split(/\n+/).forEach((line) => {
                        const i = line.indexOf('=');
                        if (i < 1) return;
                        const key = line.slice(0, i).trim();
                        const val = line.slice(i + 1).trim();
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'options[' + key + ']';
                        input.value = val;
                        this.appendChild(input);
                    });
                });
            </script>
        </section>

        <section class="mt-12">
            <h2 class="font-display text-2xl text-ink">Quizzes</h2>
            <ul class="mt-3 divide-y divide-border">
                @forelse ($course->quizzes as $quiz)
                    <li class="py-3">
                        <p class="font-semibold text-ink">{{ $quiz->title }}</p>
                        <p class="text-sm text-ink-muted">
                            {{ ucfirst($quiz->status) }} · {{ $quiz->questions->count() }} questions
                            @if ($quiz->attempt_limit) · {{ $quiz->attempt_limit }} attempts @endif
                            @if ($quiz->time_limit_seconds) · {{ $quiz->time_limit_seconds }}s @endif
                            @if ($quiz->random_draw_count) · draw {{ $quiz->random_draw_count }} @endif
                        </p>
                        @if ($quiz->status !== 'published')
                            <form method="post" action="{{ route('learning.courses.quizzes.publish', [$course, $quiz]) }}" class="mt-2">
                                @csrf
                                <button class="sg-btn" type="submit">Publish</button>
                            </form>
                        @endif
                        <form method="post" action="{{ route('learning.courses.quizzes.extra-time', [$course, $quiz]) }}" class="mt-2 flex flex-wrap gap-2">
                            @csrf
                            <input type="number" name="user_id" class="sg-input" placeholder="Learner user id" required>
                            <input type="number" name="extra_seconds" class="sg-input" placeholder="Extra seconds" min="1" required>
                            <button class="sg-btn" type="submit">Grant extra time</button>
                        </form>
                    </li>
                @empty
                    <li class="py-3 text-ink-muted">No quizzes yet.</li>
                @endforelse
            </ul>

            <form method="post" action="{{ route('learning.courses.quizzes.store', $course) }}" class="mt-6 space-y-3 border-t border-border pt-6">
                @csrf
                <h3 class="font-semibold text-ink">Create quiz</h3>
                <label class="block text-sm">Title
                    <input name="title" class="sg-input mt-1" required maxlength="255">
                </label>
                <label class="block text-sm">Instructions
                    <textarea name="instructions" class="sg-input mt-1" rows="2"></textarea>
                </label>
                <label class="block text-sm">Attempt limit (blank = unlimited)
                    <input type="number" name="attempt_limit" class="sg-input mt-1" min="1">
                </label>
                <label class="block text-sm">Time limit seconds (blank = none)
                    <input type="number" name="time_limit_seconds" class="sg-input mt-1" min="1">
                </label>
                <label class="block text-sm">Random draw count (blank = all)
                    <input type="number" name="random_draw_count" class="sg-input mt-1" min="1">
                </label>
                @if ($course->questions->isNotEmpty())
                    <fieldset>
                        <legend class="text-sm font-semibold">Attach questions</legend>
                        @foreach ($course->questions as $question)
                            <label class="mt-1 flex items-start gap-2 text-sm">
                                <input type="checkbox" name="question_ids[]" value="{{ $question->id }}">
                                <span>{!! \Illuminate\Support\Str::limit(strip_tags($question->stem), 80) !!}</span>
                            </label>
                        @endforeach
                    </fieldset>
                @endif
                <button class="sg-btn sg-btn-primary" type="submit">Create quiz</button>
            </form>
        </section>
    </div>
</x-layouts.app>
