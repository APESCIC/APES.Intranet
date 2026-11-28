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

    <section class="sg-card mb-6 p-6" aria-labelledby="week-grid-heading">
        <h2 id="week-grid-heading" class="mb-3 font-serif text-xl font-semibold">Week grid</h2>
        <x-operations::table-scroll label="Person by day rota for this week">
            <table class="sg-table" data-rota-grid>
                <thead>
                    <tr>
                        <th scope="col" class="sticky left-0 z-10 min-w-[10rem] bg-sg-mist">Person</th>
                        @foreach ($grid['days'] as $day)
                            <th scope="col" class="min-w-[11rem]">
                                <span class="block">{{ $day->format('D') }}</span>
                                <span class="font-normal text-sg-muted">{{ $day->format('j M') }}</span>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grid['rows'] as $row)
                        <tr data-rota-row="{{ $row['key'] }}">
                            <th scope="row" class="sticky left-0 z-10 bg-sg-mist align-top">
                                <span class="block font-semibold">{{ $row['label'] }}</span>
                                @if ($row['subtitle'])
                                    <span class="block text-sm font-normal text-sg-muted">{{ $row['subtitle'] }}</span>
                                @endif
                            </th>
                            @foreach ($grid['days'] as $day)
                                @php($dayKey = $day->toDateString())
                                <td class="align-top" data-rota-cell="{{ $row['key'] }}:{{ $dayKey }}">
                                    @forelse ($row['cells'][$dayKey] as $shift)
                                        <div class="mb-2 rounded-sg-field border border-sg-line p-2 last:mb-0" data-shift="{{ $shift->id }}">
                                            <p class="font-medium">{{ $shift->role }}</p>
                                            <p class="text-sm text-sg-muted">
                                                {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                                @if (\Modules\Operations\Support\RotaWeekGrid::isOvernight($shift))
                                                    <span class="sg-badge sg-badge--neutral">Overnight</span>
                                                @endif
                                            </p>
                                            @foreach ($shift->checklistRuns as $run)
                                                <p class="text-sm">
                                                    <a href="{{ route('operations.checklists.runs.show', $run) }}" data-shift-checklist="{{ $run->id }}">{{ $run->template->name }}</a>
                                                    @if ($run->isComplete())
                                                        <span class="sg-badge sg-badge--ok">Done</span>
                                                    @endif
                                                </p>
                                            @endforeach
                                            @foreach ($clashes->get($shift->id, []) as $clash)
                                                <span class="sg-badge sg-badge--warn" data-clash>{{ $clash }}</span>
                                            @endforeach
                                            <form method="post" action="{{ route('operations.rota.shifts.update', $shift) }}" class="mt-2 flex flex-wrap gap-2">
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
                                            <form method="post" action="{{ route('operations.rota.shifts.destroy', $shift) }}" class="mt-1">
                                                @csrf
                                                @method('delete')
                                                <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Remove<span class="sr-only"> {{ $shift->label() }}</span></button>
                                            </form>
                                        </div>
                                    @empty
                                        <span class="text-sm text-sg-muted">—</span>
                                    @endforelse
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
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
