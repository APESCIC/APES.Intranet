<x-layouts.app :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">{{ $title }}</h1>
        <p class="mt-3 text-ink-muted">
            Completion, overdue and grade distribution reports for courses you can view. Small groups are not shown as identifiable grade distributions.
        </p>

        <ul class="mt-8 divide-y divide-border" aria-label="Courses with reports">
            @forelse ($courses as $course)
                <li class="py-4">
                    <a href="{{ route('learning.reports.show', $course) }}" class="text-brand underline font-display text-xl">
                        {{ $course->title }}
                    </a>
                </li>
            @empty
                <li class="py-8 text-ink-muted">
                    You do not have a scoped learning role that can view reports yet.
                </li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
