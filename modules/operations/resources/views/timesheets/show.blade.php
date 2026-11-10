<x-layouts.app :title="'Timesheet: week of '.$timesheet->period_start->format('j F Y')" :subtitle="$timesheet->member->name">
    @include('operations::timesheets.nav')
    @include('operations::partials.flash')

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="entries-heading">
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <h2 id="entries-heading" class="font-serif text-xl font-semibold">Total {{ $timesheet->hoursLabel() }}</h2>
            @include('operations::timesheets.status', ['timesheet' => $timesheet])
        </div>
        @if ($timesheet->review_note)
            <p class="mb-3 rounded-sg-field bg-sg-warn-mist p-3 text-sg-warn">Note from {{ $timesheet->reviewer?->name ?? 'your manager' }}: {{ $timesheet->review_note }}</p>
        @endif
        <div class="overflow-x-auto">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Date</th><th scope="col">Work</th><th scope="col">Time</th></tr>
                </thead>
                <tbody>
                    @forelse ($timesheet->entries as $entry)
                        <tr>
                            <td>{{ $entry->worked_on->format('D j M') }}</td>
                            <td>{{ $entry->description ?? '—' }}</td>
                            <td>{{ intdiv($entry->minutes, 60) }}h {{ $entry->minutes % 60 }}m</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-sg-muted">No finished clock records in this week.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($isOwner && $timesheet->isEditable())
            <form method="post" action="{{ route('operations.timesheets.submit', $timesheet) }}" class="mt-4">
                @csrf
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Rebuild and submit for approval</button>
            </form>
        @endif
    </section>

    @if ($canReview && $timesheet->status === 'submitted')
        <section class="sg-card max-w-3xl p-6" aria-labelledby="review-heading">
            <h2 id="review-heading" class="mb-3 font-serif text-xl font-semibold">Approve or return</h2>
            <form method="post" action="{{ route('operations.timesheets.decide', $timesheet) }}" class="grid gap-3">
                @csrf
                <div>
                    <label class="sg-label" for="review_note">Note (required to return)</label>
                    <textarea id="review_note" name="review_note" rows="3" maxlength="1000" class="sg-input w-full">{{ old('review_note') }}</textarea>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="submit" name="decision" value="approve" class="sg-btn sg-btn-primary sg-btn-sm">Approve and lock</button>
                    <button type="submit" name="decision" value="return" class="sg-btn sg-btn-secondary sg-btn-sm">Return with note</button>
                </div>
            </form>
        </section>
    @endif
</x-layouts.app>
