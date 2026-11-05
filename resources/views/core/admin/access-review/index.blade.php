<x-layouts.app title="Access review" subtitle="The quarterly check of intranet groups and sensitive access. Fix anything wrong in Cloudron; this page only reports.">
    <div class="space-y-4">
        <section class="sg-card p-5" aria-labelledby="stacked-heading" data-section="stacked">
            <h2 id="stacked-heading" class="font-serif text-lg font-semibold">More than one senior role ({{ $stacked->count() }})</h2>
            <p class="text-sm text-sg-muted">People who hold two or more of admin, HR, legal and management. Check each still needs them.</p>
            @if ($stacked->isEmpty())
                <p class="mt-2">Nobody.</p>
            @else
                <ul class="mt-2 space-y-1">
                    @foreach ($stacked as $person)
                        <li>
                            <strong>{{ $person->name }}</strong>
                            <span class="text-sg-muted">{{ $person->roles->map(fn ($role) => \App\Core\Access\RoleName::tryFrom($role->name)?->label() ?? $role->name)->sort()->implode(', ') }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="sg-card p-5" aria-labelledby="leavers-heading" data-section="leavers">
            <h2 id="leavers-heading" class="font-serif text-lg font-semibold">Possible leavers ({{ $leavers->count() }})</h2>
            <p class="text-sm text-sg-muted">
                People who still hold an intranet role but have not signed in for {{ \App\Core\Access\AccessReview::LEAVER_DAYS }} days.
                If they have left, remove them from their Cloudron groups.
            </p>
            @if ($leavers->isEmpty())
                <p class="mt-2">Nobody.</p>
            @else
                <ul class="mt-2 space-y-1">
                    @foreach ($leavers as $row)
                        <li>
                            <strong>{{ $row['user']->name }}</strong>
                            <span class="text-sg-muted">last signed in {{ $row['lastSignIn']?->timezone('Europe/London')->format('j M Y') ?? 'never' }}</span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="sg-card p-5" aria-labelledby="no-group-heading" data-section="no-group">
            <h2 id="no-group-heading" class="font-serif text-lg font-semibold">In no group ({{ $noGroup->count() }})</h2>
            <p class="text-sm text-sg-muted">Accounts with no intranet role. They see the "No intranet access" page until they are added to an approved group.</p>
            @if ($noGroup->isEmpty())
                <p class="mt-2">Nobody.</p>
            @else
                <ul class="mt-2 space-y-1">
                    @foreach ($noGroup as $person)
                        <li><strong>{{ $person->name }}</strong></li>
                    @endforeach
                </ul>
            @endif
        </section>

        <section class="sg-card p-5" aria-labelledby="sensitive-heading" data-section="sensitive">
            <h2 id="sensitive-heading" class="font-serif text-lg font-semibold">Admin and management views of DBS and health data ({{ $sensitiveViews->count() }})</h2>
            <p class="text-sm text-sg-muted">From the audit log, last {{ \App\Core\Access\AccessReview::SENSITIVE_VIEW_DAYS }} days. Check each had a reason.</p>
            @if ($sensitiveViews->isEmpty())
                <p class="mt-2">None.</p>
            @else
                <ul class="mt-2 space-y-1">
                    @foreach ($sensitiveViews as $entry)
                        <li>
                            {{ $entry->occurred_at->timezone('Europe/London')->format('j M Y, H:i') }}:
                            <strong>{{ $entry->actor?->name ?? 'Unknown' }}</strong>
                            viewed {{ ($entry->context['category'] ?? '') === 'dbs' ? 'DBS' : 'health' }} record #{{ $entry->subject_id }}
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</x-layouts.app>
