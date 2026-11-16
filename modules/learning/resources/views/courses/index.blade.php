<x-layouts.app title="Courses">
    <div class="mx-auto max-w-4xl px-4 py-8">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="font-display text-3xl text-ink">Courses</h1>
                <p class="mt-2 text-ink-muted">Author intranet courses. Separate from HR My training.</p>
            </div>
            @can('create', Modules\Learning\Models\Course::class)
                <a href="{{ route('learning.courses.create') }}" class="sg-btn sg-btn-primary">New course</a>
            @endcan
        </div>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif

        <ul class="mt-8 divide-y divide-border">
            @forelse ($courses as $course)
                <li class="py-4">
                    <a href="{{ route('learning.courses.show', $course) }}" class="font-semibold text-ink hover:underline">
                        {{ $course->title }}
                    </a>
                    <p class="text-sm text-ink-muted">
                        {{ ucfirst($course->status) }} · {{ $course->sections_count }} sections
                    </p>
                </li>
            @empty
                <li class="py-4 text-ink-muted">No courses yet.</li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
