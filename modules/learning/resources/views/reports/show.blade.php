<x-layouts.app :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <p class="text-sm">
            <a href="{{ route('learning.reports.index') }}" class="text-brand underline">All learning reports</a>
        </p>
        <h1 class="mt-2 font-display text-3xl text-ink">{{ $course->title }}</h1>

        <section class="mt-8" aria-labelledby="completion-heading">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 id="completion-heading" class="font-display text-xl text-ink">Completion and overdue</h2>
                <a href="{{ route('learning.reports.completions.export', $course) }}" class="sg-btn sg-btn-primary text-sm">
                    Download CSV
                </a>
            </div>
            <p class="mt-2 text-ink-muted text-sm">
                Enrolled {{ $completion['enrolled'] }},
                completed {{ $completion['completed'] }},
                overdue {{ $completion['overdue'] }}.
            </p>
            <ul class="mt-4 divide-y divide-border" aria-label="Learners">
                @foreach ($completion['rows'] as $row)
                    <li class="py-2 flex justify-between gap-4 text-sm">
                        <span>{{ $row['user']->name }}</span>
                        <span class="{{ $row['overdue'] ? 'text-sg-danger' : 'text-sg-ok' }}">
                            {{ $row['overdue'] ? 'Overdue' : 'Complete' }}
                        </span>
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="mt-10" aria-labelledby="distribution-heading">
            <h2 id="distribution-heading" class="font-display text-xl text-ink">Grade distribution</h2>
            @if ($distribution['suppressed'])
                <p class="mt-3 rounded border border-border bg-panel p-4 text-sm text-ink" role="status">
                    {{ $distribution['reason'] }}
                    ({{ $distribution['total'] }} mark{{ $distribution['total'] === 1 ? '' : 's' }} available; minimum {{ $minGroupSize }}).
                </p>
            @else
                <ul class="mt-4 space-y-2" aria-label="Score buckets">
                    @foreach ($distribution['buckets'] as $bucket)
                        <li class="flex justify-between text-sm border-b border-border py-2">
                            <span>{{ $bucket['label'] }}</span>
                            <span>{{ $bucket['count'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
