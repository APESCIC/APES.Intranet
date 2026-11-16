<x-layouts.app :title="$course->title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.mine') }}" class="underline">My learning</a>
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">{{ $course->title }}</h1>
        @if ($course->summary)
            <p class="mt-3 text-ink-muted">{{ $course->summary }}</p>
        @endif

        <p class="mt-4 text-sm text-ink-muted" aria-live="polite">
            Progress: {{ $summary['completed'] }} of {{ $summary['total'] }} lessons
            ({{ $summary['percent'] }}%)
            @if ($complete)
                · Course completed
            @endif
        </p>
        <div
            class="mt-2 h-2 max-w-md rounded bg-border"
            role="progressbar"
            aria-valuemin="0"
            aria-valuemax="100"
            aria-valuenow="{{ $summary['percent'] }}"
            aria-label="Course progress"
        >
            <div class="h-2 rounded bg-brand" style="width: {{ $summary['percent'] }}%"></div>
        </div>

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

        @foreach ($course->sections as $section)
            <section class="mt-10" aria-labelledby="section-{{ $section->id }}">
                <h2 id="section-{{ $section->id }}" class="font-display text-2xl text-ink">{{ $section->title }}</h2>
                <ol class="mt-3 divide-y divide-border">
                    @foreach ($section->lessons as $lesson)
                        @php
                            $state = $lessonStates[$lesson->id] ?? ['started' => false, 'completed' => false];
                            $published = $lesson->versions->isNotEmpty();
                        @endphp
                        <li class="flex flex-col gap-2 py-4 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <p class="font-semibold text-ink">{{ $lesson->title }}</p>
                                <p class="text-sm text-ink-muted">
                                    @if (! $published)
                                        Not published yet
                                    @elseif ($state['completed'])
                                        Completed
                                    @elseif ($state['started'])
                                        In progress
                                    @else
                                        Not started
                                    @endif
                                </p>
                            </div>
                            @if ($published)
                                <a
                                    href="{{ route('learning.play.lesson', [$course, $lesson]) }}"
                                    class="sg-btn shrink-0"
                                >
                                    {{ $state['completed'] ? 'Review' : ($state['started'] ? 'Continue' : 'Start') }}
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>
        @endforeach
    </div>
</x-layouts.app>
