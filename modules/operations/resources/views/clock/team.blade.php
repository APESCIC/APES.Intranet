<x-layouts.app title="Team clock" subtitle="Who is clocked in, missed clock-outs and the last 7 days.">
    @include('operations::partials.flash')

    <section class="sg-card p-6" aria-labelledby="team-clock">
        <h2 id="team-clock" class="sr-only">Clock records</h2>
        <x-operations::table-scroll label="Team clock records">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">Member</th><th scope="col">In</th><th scope="col">Out</th><th scope="col">Worked</th><th scope="col">Where</th><th scope="col">Location check</th></tr>
                </thead>
                <tbody>
                    @forelse ($records as $record)
                        <tr data-clock-record="{{ $record->id }}">
                            <td>{{ $record->member->name }}</td>
                            <td>{{ $record->clock_in_at->format('D j M H:i') }}</td>
                            <td>
                                @if ($record->isOpen())
                                    @if ($record->missed_clock_out_flagged_at)
                                        <span class="sg-badge sg-badge--warn">Missed clock-out</span>
                                    @else
                                        <span class="sg-badge">Clocked in</span>
                                    @endif
                                    <form method="post" action="{{ route('operations.clock.correct', $record) }}" class="mt-2 flex flex-wrap gap-2">
                                        @csrf
                                        <label class="sr-only" for="out-{{ $record->id }}">Clock-out time for {{ $record->member->name }}</label>
                                        <input id="out-{{ $record->id }}" type="datetime-local" name="clock_out_at" class="sg-input" value="{{ ($record->shift?->ends_at ?? now())->format('Y-m-d\TH:i') }}" required>
                                        <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Record clock-out</button>
                                    </form>
                                @else
                                    {{ $record->clock_out_at->format('H:i') }}
                                    @if ($record->corrected_by)<span class="sg-badge">Recorded by a manager</span>@endif
                                @endif
                            </td>
                            <td>{{ intdiv($record->workedMinutes(), 60) }}h {{ $record->workedMinutes() % 60 }}m</td>
                            <td>{{ $record->shift?->label() ?? ($record->site?->name ?? '—') }}</td>
                            <td>
                                @if ($record->geofence_result === 'pass')
                                    <span class="sg-badge sg-badge--ok">Near the site</span>
                                @elseif ($record->geofence_result === 'fail')
                                    <span class="sg-badge sg-badge--warn">Not confirmed</span>
                                @else
                                    —
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-sg-muted">Nobody in your teams has clocked in this week.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-operations::table-scroll>
        <p class="mt-3 text-sm text-sg-muted">"Not confirmed" can mean poor signal or that the member said no to sharing their location. Treat it as a prompt to ask, not as proof.</p>
    </section>
</x-layouts.app>
