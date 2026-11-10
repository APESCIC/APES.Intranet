<x-layouts.app :title="'Rota: '.$week->site->name" :subtitle="'Week of '.$week->week_start->format('j F Y').' · '.ucfirst($week->status)">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    @if ($clashes->isNotEmpty())
        <p class="mb-4 rounded-sg-field bg-sg-warn-mist p-3 text-sg-warn" role="alert" data-clash-count="{{ $clashes->count() }}">
            {{ $clashes->count() === 1 ? '1 shift clashes' : $clashes->count().' shifts clash' }} with unavailability or another shift. Check the flagged shifts below.
        </p>
    @endif

    <div class="mb-6 flex flex-wrap items-center gap-3">
        <form method="post" action="{{ route('operations.rota.weeks.publish', $week) }}">
            @csrf
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">{{ $week->isPublished() ? 'Publish changes and email members' : 'Publish and email members' }}</button>
        </form>
        @if ($week->isPublished())
            <span class="sg-badge sg-badge--ok">Published {{ $week->published_at?->format('j M H:i') }}</span>
        @endif
    </div>

    <section class="sg-card mb-6 p-6" aria-labelledby="shifts-heading">
        <h2 id="shifts-heading" class="mb-3 font-serif text-xl font-semibold">Shifts</h2>
        <x-operations::table-scroll label="Shifts this week">
            <table class="sg-table">
                <thead>
                    <tr><th scope="col">When</th><th scope="col">Role</th><th scope="col">Member</th><th scope="col">Clashes</th><th scope="col"><span class="sr-only">Remove</span></th></tr>
                </thead>
                <tbody>
                    @forelse ($week->shifts as $shift)
                        <tr data-shift="{{ $shift->id }}">
                            <td>{{ $shift->starts_at->format('D j M H:i') }}–{{ $shift->ends_at->format('H:i') }}</td>
                            <td>{{ $shift->role }}</td>
                            <td>
                                <form method="post" action="{{ route('operations.rota.shifts.update', $shift) }}" class="flex flex-wrap gap-2">
                                    @csrf
                                    @method('patch')
                                    <label class="sr-only" for="member-{{ $shift->id }}">Member for {{ $shift->label() }}</label>
                                    <select id="member-{{ $shift->id }}" name="user_id" class="sg-input">
                                        <option value="">Open shift</option>
                                        @foreach ($members as $member)
                                            <option value="{{ $member->id }}" @selected($shift->user_id === $member->id)>{{ $member->name }}@if ($member->team) ({{ $member->team }})@endif</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save</button>
                                </form>
                            </td>
                            <td>
                                @foreach ($clashes->get($shift->id, []) as $clash)
                                    <span class="sg-badge sg-badge--warn" data-clash>{{ $clash }}</span>
                                @endforeach
                            </td>
                            <td>
                                <form method="post" action="{{ route('operations.rota.shifts.destroy', $shift) }}">
                                    @csrf
                                    @method('delete')
                                    <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Remove<span class="sr-only"> {{ $shift->label() }}</span></button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-sg-muted">No shifts yet. Add one below, or add shift templates for this site.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-operations::table-scroll>
    </section>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="add-shift">
        <h2 id="add-shift" class="mb-3 font-serif text-xl font-semibold">Add a one-off shift</h2>
        <form method="post" action="{{ route('operations.rota.weeks.shifts.store', $week) }}" class="grid gap-3 sm:grid-cols-2">
            @csrf
            <div>
                <label class="sg-label" for="role">Role</label>
                <input id="role" name="role" class="sg-input w-full" value="{{ old('role') }}" required>
            </div>
            <div>
                <label class="sg-label" for="user_id">Member</label>
                <select id="user_id" name="user_id" class="sg-input w-full">
                    <option value="">Open shift</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sg-label" for="starts_at">Starts</label>
                <input id="starts_at" type="datetime-local" name="starts_at" class="sg-input w-full" value="{{ old('starts_at', $week->week_start->setTime(9, 0)->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div>
                <label class="sg-label" for="ends_at">Ends</label>
                <input id="ends_at" type="datetime-local" name="ends_at" class="sg-input w-full" value="{{ old('ends_at', $week->week_start->setTime(17, 0)->format('Y-m-d\TH:i')) }}" required>
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add shift</button>
            </div>
        </form>
    </section>
</x-layouts.app>
