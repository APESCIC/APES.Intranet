<x-layouts.app :title="$course->title">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <p class="text-sm text-ink-muted">
            <a href="{{ route('learning.courses.index') }}" class="underline">Courses</a>
            · {{ ucfirst($course->status) }}
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">{{ $course->title }}</h1>
        @if ($course->summary)
            <p class="mt-3 text-ink-muted">{{ $course->summary }}</p>
        @endif

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif

        @can('enrol', $course)
            <p class="mt-4">
                <a href="{{ route('learning.courses.enrolments.index', $course) }}" class="sg-btn">Manage enrolments</a>
            </p>
        @endcan

        @foreach ($course->sections as $section)
            <section class="mt-10">
                <h2 class="font-display text-2xl text-ink">{{ $section->title }}</h2>
                <ul class="mt-3 divide-y divide-border">
                    @foreach ($section->lessons as $lesson)
                        <li class="flex flex-wrap items-center justify-between gap-2 py-3">
                            <div>
                                <p class="font-semibold text-ink">{{ $lesson->title }}</p>
                                <p class="text-sm text-ink-muted">
                                    @if ($lesson->currentVersion)
                                        Published v{{ $lesson->currentVersion->version_number }}
                                    @else
                                        Draft only
                                    @endif
                                </p>
                            </div>
                            @if ($canAuthor)
                                <a href="{{ route('learning.courses.lessons.edit', [$course, $lesson]) }}" class="sg-btn">Edit lesson</a>
                            @endif
                        </li>
                    @endforeach
                </ul>

                @if ($canAuthor)
                    <form method="post" action="{{ route('learning.courses.sections.lessons.store', [$course, $section]) }}" class="mt-4 flex flex-wrap gap-2">
                        @csrf
                        <input name="title" class="sg-input" placeholder="New lesson title" required maxlength="200">
                        <button class="sg-btn" type="submit">Add lesson</button>
                    </form>
                @endif
            </section>
        @endforeach

        @if ($canAuthor)
            <form method="post" action="{{ route('learning.courses.sections.store', $course) }}" class="mt-10 flex flex-wrap gap-2 border-t border-border pt-6">
                @csrf
                <input name="title" class="sg-input" placeholder="New section title" required maxlength="200">
                <button class="sg-btn sg-btn-primary" type="submit">Add section</button>
            </form>
        @endif
    </div>
</x-layouts.app>
