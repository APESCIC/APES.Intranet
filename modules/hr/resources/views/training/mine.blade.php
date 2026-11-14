<x-layouts.app title="My training" subtitle="Required courses and links to the LMS.">
    <p class="mb-4 text-sg-muted">Open a course on <a class="text-sg-brand underline" href="{{ $lmsBase }}" target="_blank" rel="noopener">{{ $lmsBase }}</a>. Completions are recorded by HR.</p>

    <section class="sg-card p-6" aria-labelledby="courses-heading">
        <h2 id="courses-heading" class="mb-3 font-serif text-xl font-semibold">Required for your roles</h2>
        @if ($rows->isEmpty())
            <p class="text-sg-muted">No required courses for your roles.</p>
        @else
            <ul class="space-y-3">
                @foreach ($rows as $row)
                    <li class="border-b border-sg-border/60 pb-3" data-course="{{ $row['course']->code }}">
                        <a class="font-medium text-sg-brand underline" href="{{ $row['course']->lms_url }}" target="_blank" rel="noopener">{{ $row['course']->title }}</a>
                        @if ($row['overdue'])
                            <span class="text-sg-danger"> — overdue or missing</span>
                        @else
                            <span class="text-sg-ok"> — complete
                                @if ($row['completion']?->expires_on)
                                    (expires {{ $row['completion']->expires_on->format('d M Y') }})
                                @endif
                            </span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
</x-layouts.app>
