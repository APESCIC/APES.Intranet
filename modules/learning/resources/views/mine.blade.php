<x-layouts.app :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">{{ $title }}</h1>
        <p class="mt-3 text-ink-muted">
            Intranet courses you are enrolled on. Compliance courses on Moodle Upskilling stay on
            <a href="{{ route('hr.training.mine') }}" class="text-brand underline">My training</a>
            until a later decision. Both systems run together for now.
        </p>

        @if (session('status'))
            <p class="mt-4 text-sm text-brand" role="status">{{ session('status') }}</p>
        @endif

        <ul class="mt-8 divide-y divide-border" aria-label="Your enrolled courses">
            @forelse ($courses as $row)
                <li class="py-5">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <h2 class="font-display text-xl text-ink">
                                <a href="{{ route('learning.play.show', $row['course']) }}" class="text-brand underline">
                                    {{ $row['course']->title }}
                                </a>
                            </h2>
                            <p class="mt-1 text-sm text-ink-muted">
                                @if ($row['complete'])
                                    Completed
                                @else
                                    {{ $row['summary']['completed'] }} of {{ $row['summary']['total'] }} lessons
                                    ({{ $row['summary']['percent'] }}%)
                                @endif
                            </p>
                            <div
                                class="mt-2 h-2 max-w-xs rounded bg-border"
                                role="progressbar"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                aria-valuenow="{{ $row['summary']['percent'] }}"
                                aria-label="Course progress"
                            >
                                <div class="h-2 rounded bg-brand" style="width: {{ $row['summary']['percent'] }}%"></div>
                            </div>
                        </div>
                        <div class="flex shrink-0 flex-col gap-2 sm:items-end">
                            <a href="{{ route('learning.play.show', $row['course']) }}" class="sg-btn sg-btn-primary">
                                {{ $row['complete'] ? 'Review course' : 'Continue' }}
                            </a>
                            @if ($row['certificate'] ?? null)
                                <a href="{{ route('learning.certificates.download', $row['certificate']) }}" class="text-sm text-brand underline">
                                    Download certificate (PDF)
                                </a>
                            @endif
                        </div>
                    </div>
                </li>
            @empty
                <li class="py-8 text-ink-muted">
                    You are not enrolled on any courses yet. Ask a learning manager if you need access.
                </li>
            @endforelse
        </ul>
    </div>
</x-layouts.app>
