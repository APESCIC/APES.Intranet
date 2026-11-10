<x-layouts.app title="Clock in and out" subtitle="Tap to start and finish your shift.">
    @include('operations::partials.flash')

    <section class="sg-card mb-6 max-w-xl p-6" aria-labelledby="clock-status">
        @if ($open)
            <h2 id="clock-status" class="font-serif text-2xl font-semibold">Clocked in since {{ $open->clock_in_at->format('H:i') }}</h2>
            <p class="mt-1 text-sg-muted">
                @if ($open->shift){{ $open->shift->label() }} · @endif
                {{ $open->site?->name ?? 'No site' }} · {{ intdiv($open->workedMinutes(), 60) }}h {{ $open->workedMinutes() % 60 }}m worked
            </p>
            @if ($open->openBreak())
                <p class="mt-2"><span class="sg-badge sg-badge--warn">On a break since {{ $open->openBreak()->started_at->format('H:i') }}</span></p>
            @endif
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                @if ($open->openBreak())
                    <form method="post" action="{{ route('operations.clock.break.end') }}">
                        @csrf
                        <button type="submit" class="sg-btn sg-btn-secondary w-full justify-center py-4 text-lg">End break</button>
                    </form>
                @else
                    <form method="post" action="{{ route('operations.clock.break.start') }}">
                        @csrf
                        <button type="submit" class="sg-btn sg-btn-secondary w-full justify-center py-4 text-lg">Start break</button>
                    </form>
                @endif
                <form method="post" action="{{ route('operations.clock.out') }}">
                    @csrf
                    <button type="submit" class="sg-btn sg-btn-primary w-full justify-center py-4 text-lg">Clock out</button>
                </form>
            </div>
        @else
            <h2 id="clock-status" class="font-serif text-2xl font-semibold">You are not clocked in</h2>
            <form method="post" action="{{ route('operations.clock.in') }}" class="mt-4 grid gap-4" data-clock-form>
                @csrf
                <div>
                    <label class="sg-label" for="target">What are you clocking in to?</label>
                    <select id="target" name="target" class="sg-input w-full">
                        @if ($shifts->isNotEmpty())
                            <optgroup label="Your shifts">
                                @foreach ($shifts as $shift)
                                    <option value="shift:{{ $shift->id }}" @if ($geofenceShiftIds->contains($shift->id)) data-geofence @endif>{{ $shift->label() }} · {{ $shift->site->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Work at a site, not on a shift">
                            @foreach ($sites as $site)
                                <option value="site:{{ $site->id }}" @if ($geofenceSiteIds->contains($site->id)) data-geofence @endif>{{ $site->name }}</option>
                            @endforeach
                            <option value="">No site</option>
                        </optgroup>
                    </select>
                </div>
                <input type="hidden" name="latitude" data-geofence-latitude>
                <input type="hidden" name="longitude" data-geofence-longitude>
                <button type="submit" class="sg-btn sg-btn-primary w-full justify-center py-4 text-lg">Clock in</button>
                @if ($geofenceOn)
                    <p class="text-sm text-sg-muted" data-geofence-notice>
                        For some sites your device is asked for its location once, when you tap Clock in. Only "near the site" or "not near the site" is saved,
                        never where you are. You can say no, and you will still be clocked in.
                    </p>
                @endif
            </form>
        @endif
    </section>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="recent-heading">
        <h2 id="recent-heading" class="mb-3 font-serif text-xl font-semibold">Last 14 days</h2>
        <div class="overflow-x-auto">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">In</th><th scope="col">Out</th><th scope="col">Breaks</th><th scope="col">Worked</th><th scope="col">Where</th></tr>
                </thead>
                <tbody>
                    @forelse ($recent as $record)
                        <tr data-clock-record="{{ $record->id }}">
                            <td>{{ $record->clock_in_at->format('D j M H:i') }}</td>
                            <td>
                                {{ $record->clock_out_at?->format('H:i') ?? 'Still clocked in' }}
                                @if ($record->missed_clock_out_flagged_at)<span class="sg-badge sg-badge--warn">Missed clock-out</span>@endif
                            </td>
                            <td>{{ $record->breaks->count() }}</td>
                            <td>{{ intdiv($record->workedMinutes(), 60) }}h {{ $record->workedMinutes() % 60 }}m</td>
                            <td>{{ $record->site?->name ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-sg-muted">No clock records yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($shifts->isEmpty() && ! $open)
            <p class="mt-3 text-sm text-sg-muted">No shift of yours starts within two hours, so choose the site you are working at.</p>
        @endif
    </section>
</x-layouts.app>
