<x-layouts.app title="DBS and right to work" subtitle="Special category metadata only. Every open is audited. No scans stored.">
    <p class="mb-4 text-sm text-sg-muted">Document evidence stays as a SharePoint link. Certificate or passport images are never uploaded here.</p>

    <section class="sg-card mb-6 p-6" aria-labelledby="expiring-heading">
        <h2 id="expiring-heading" class="mb-3 font-serif text-xl font-semibold">Expiring within 30 days ({{ $expiring->count() }})</h2>
        @include('hr::checks.partials-table', ['rows' => $expiring, 'empty' => 'None due in the next 30 days.'])
    </section>

    <section class="sg-card mb-6 p-6" aria-labelledby="expired-heading">
        <h2 id="expired-heading" class="mb-3 font-serif text-xl font-semibold">Expired ({{ $expired->count() }})</h2>
        @include('hr::checks.partials-table', ['rows' => $expired, 'empty' => 'No expired checks.'])
    </section>

    <section class="sg-card mb-6 p-6" aria-labelledby="missing-heading">
        <h2 id="missing-heading" class="mb-3 font-serif text-xl font-semibold">Missing a check type ({{ count($missing) }})</h2>
        @if (count($missing) === 0)
            <p class="text-sg-muted">Everyone has both check types recorded.</p>
        @else
            <ul class="list-disc space-y-1 pl-5">
                @foreach ($missing as $row)
                    <li>
                        <a class="underline" href="{{ route('hr.records.show', $row['user']) }}">{{ $row['user']->name }}</a>
                        — {{ $row['check_type'] === 'dbs' ? 'DBS' : 'Right to work' }}
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="sg-card p-6" aria-labelledby="all-heading">
        <h2 id="all-heading" class="mb-3 font-serif text-xl font-semibold">All recorded checks</h2>
        @include('hr::checks.partials-table', ['rows' => $checks, 'empty' => 'No checks recorded yet.'])
    </section>
</x-layouts.app>
