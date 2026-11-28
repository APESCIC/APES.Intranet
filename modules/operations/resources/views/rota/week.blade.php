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
        <form method="post" action="{{ route('operations.rota.weeks.copy', $week) }}">
            @csrf
            <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Copy to next week</button>
        </form>
        @if ($week->isPublished())
            <span class="sg-badge sg-badge--ok">Published {{ $week->published_at?->format('j M H:i') }}</span>
        @endif
        @error('copy')
            <p class="text-sg-error" role="alert">
                {{ $message }}
                @if (session('existing_week_id'))
                    <a class="underline" href="{{ route('operations.rota.weeks.show', session('existing_week_id')) }}">Open the existing week</a>
                @endif
            </p>
        @enderror
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
                                    @foreach ($row['cells'][$dayKey] as $shift)
                                        <button
                                            type="button"
                                            class="mb-2 block w-full rounded-sg-field border border-sg-line p-2 text-left last:mb-0 hover:border-sg-teal"
                                            data-shift="{{ $shift->id }}"
                                            data-rota-edit-shift
                                            data-shift-role="{{ $shift->role }}"
                                            data-shift-starts="{{ $shift->starts_at->format('Y-m-d\TH:i') }}"
                                            data-shift-ends="{{ $shift->ends_at->format('Y-m-d\TH:i') }}"
                                            data-shift-user="{{ $shift->user_id }}"
                                            data-shift-update="{{ route('operations.rota.shifts.update', $shift) }}"
                                            data-shift-destroy="{{ route('operations.rota.shifts.destroy', $shift) }}"
                                            data-shift-label="{{ $shift->label() }}"
                                        >
                                            <span class="block font-medium">{{ $shift->role }}</span>
                                            <span class="block text-sm text-sg-muted">
                                                {{ $shift->starts_at->format('H:i') }}–{{ $shift->ends_at->format('H:i') }}
                                                @if (\Modules\Operations\Support\RotaWeekGrid::isOvernight($shift))
                                                    <span class="sg-badge sg-badge--neutral">Overnight</span>
                                                @endif
                                            </span>
                                            @foreach ($shift->checklistRuns as $run)
                                                <span class="block text-sm">{{ $run->template->name }}@if ($run->isComplete()) (done)@endif</span>
                                            @endforeach
                                            @foreach ($clashes->get($shift->id, []) as $clash)
                                                <span class="sg-badge sg-badge--warn" data-clash>{{ $clash }}</span>
                                            @endforeach
                                        </button>
                                    @endforeach
                                    <button
                                        type="button"
                                        class="sg-btn sg-btn-ghost sg-btn-sm"
                                        data-rota-add-cell
                                        data-day="{{ $dayKey }}"
                                        data-user-id="{{ $row['user_id'] ?? '' }}"
                                        aria-label="Add shift on {{ $day->format('l j F') }}@if ($row['label'] !== 'Open shifts') for {{ $row['label'] }}@endif"
                                    >Add</button>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-operations::table-scroll>
    </section>

    <dialog id="rota-add-dialog" class="sg-card max-w-md w-full border border-sg-line p-0 backdrop:bg-black/40" data-rota-add-dialog>
        <form method="post" action="{{ route('operations.rota.weeks.shifts.store', $week) }}" class="p-6 grid gap-3" data-rota-add-form>
            @csrf
            <h2 class="font-serif text-xl font-semibold" id="rota-add-title">Add a shift</h2>
            <input type="hidden" name="day" value="" data-rota-add-day>
            <div>
                <label class="sg-label" for="rota-add-template">From a template</label>
                <select id="rota-add-template" name="shift_template_id" class="sg-input w-full" data-rota-add-template>
                    <option value="">One-off shift</option>
                    @foreach ($templates as $template)
                        <option
                            value="{{ $template->id }}"
                            data-weekday="{{ $template->weekday }}"
                            data-role="{{ $template->role }}"
                            data-starts="{{ $template->starts_at }}"
                            data-ends="{{ $template->ends_at }}"
                        >{{ $template->name }} · {{ $template->weekdayName() }} {{ \Illuminate\Support\Str::of($template->starts_at)->substr(0, 5) }}–{{ \Illuminate\Support\Str::of($template->ends_at)->substr(0, 5) }}</option>
                    @endforeach
                </select>
            </div>
            <div data-rota-add-oneoff>
                <label class="sg-label" for="rota-add-role">Role</label>
                <input id="rota-add-role" name="role" class="sg-input w-full" data-rota-add-role>
            </div>
            <div data-rota-add-oneoff>
                <label class="sg-label" for="rota-add-starts">Starts</label>
                <input id="rota-add-starts" type="datetime-local" name="starts_at" class="sg-input w-full" data-rota-add-starts>
            </div>
            <div data-rota-add-oneoff>
                <label class="sg-label" for="rota-add-ends">Ends</label>
                <input id="rota-add-ends" type="datetime-local" name="ends_at" class="sg-input w-full" data-rota-add-ends>
            </div>
            <div>
                <label class="sg-label" for="rota-add-user">Member</label>
                <select id="rota-add-user" name="user_id" class="sg-input w-full" data-rota-add-user>
                    <option value="">Open shift</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add shift</button>
                <button type="button" class="sg-btn sg-btn-secondary sg-btn-sm" data-rota-add-close>Cancel</button>
            </div>
        </form>
    </dialog>

    <dialog id="rota-edit-dialog" class="sg-card max-w-md w-full border border-sg-line p-0 backdrop:bg-black/40" data-rota-edit-dialog>
        <form method="post" action="" class="p-6 grid gap-3" data-rota-edit-form>
            @csrf
            @method('patch')
            <h2 class="font-serif text-xl font-semibold">Change shift</h2>
            <p class="text-sm text-sg-muted" data-rota-edit-label></p>
            <div>
                <label class="sg-label" for="rota-edit-role">Role</label>
                <input id="rota-edit-role" name="role" class="sg-input w-full" required data-rota-edit-role>
            </div>
            <div>
                <label class="sg-label" for="rota-edit-starts">Starts</label>
                <input id="rota-edit-starts" type="datetime-local" name="starts_at" class="sg-input w-full" required data-rota-edit-starts>
            </div>
            <div>
                <label class="sg-label" for="rota-edit-ends">Ends</label>
                <input id="rota-edit-ends" type="datetime-local" name="ends_at" class="sg-input w-full" required data-rota-edit-ends>
            </div>
            <div>
                <label class="sg-label" for="rota-edit-user">Member</label>
                <select id="rota-edit-user" name="user_id" class="sg-input w-full" data-rota-edit-user>
                    <option value="">Open shift</option>
                    @foreach ($members as $member)
                        <option value="{{ $member->id }}">{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex flex-wrap gap-2">
                <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save</button>
                <button type="button" class="sg-btn sg-btn-secondary sg-btn-sm" data-rota-edit-close>Cancel</button>
            </div>
        </form>
        <form method="post" action="" class="border-t border-sg-line px-6 py-4" data-rota-remove-form>
            @csrf
            @method('delete')
            <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Remove this shift</button>
        </form>
    </dialog>

    <section class="sg-card max-w-3xl p-6" aria-labelledby="add-shift">
        <h2 id="add-shift" class="mb-3 font-serif text-xl font-semibold">Add a one-off shift</h2>
        <p class="mb-3 text-sm text-sg-muted">Fallback if you prefer the form instead of a cell.</p>
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

    <script>
        (() => {
            const addDialog = document.querySelector('[data-rota-add-dialog]');
            const editDialog = document.querySelector('[data-rota-edit-dialog]');
            if (!addDialog || !editDialog) return;

            const addForm = addDialog.querySelector('[data-rota-add-form]');
            const addDay = addDialog.querySelector('[data-rota-add-day]');
            const addUser = addDialog.querySelector('[data-rota-add-user]');
            const addTemplate = addDialog.querySelector('[data-rota-add-template]');
            const addRole = addDialog.querySelector('[data-rota-add-role]');
            const addStarts = addDialog.querySelector('[data-rota-add-starts]');
            const addEnds = addDialog.querySelector('[data-rota-add-ends]');
            const oneOffFields = addDialog.querySelectorAll('[data-rota-add-oneoff]');

            const editForm = editDialog.querySelector('[data-rota-edit-form]');
            const removeForm = editDialog.querySelector('[data-rota-remove-form]');
            const editLabel = editDialog.querySelector('[data-rota-edit-label]');
            const editRole = editDialog.querySelector('[data-rota-edit-role]');
            const editStarts = editDialog.querySelector('[data-rota-edit-starts]');
            const editEnds = editDialog.querySelector('[data-rota-edit-ends]');
            const editUser = editDialog.querySelector('[data-rota-edit-user]');

            const toggleOneOff = () => {
                const fromTemplate = Boolean(addTemplate.value);
                oneOffFields.forEach((el) => { el.hidden = fromTemplate; });
                addRole.required = !fromTemplate;
                addStarts.required = !fromTemplate;
                addEnds.required = !fromTemplate;
                if (fromTemplate) {
                    addRole.value = '';
                    addStarts.value = '';
                    addEnds.value = '';
                }
            };

            addTemplate.addEventListener('change', toggleOneOff);

            document.querySelectorAll('[data-rota-add-cell]').forEach((button) => {
                button.addEventListener('click', () => {
                    const day = button.getAttribute('data-day');
                    addDay.value = day;
                    addUser.value = button.getAttribute('data-user-id') || '';
                    addTemplate.value = '';
                    addRole.value = '';
                    addStarts.value = day + 'T09:00';
                    addEnds.value = day + 'T17:00';
                    toggleOneOff();
                    addDialog.showModal();
                    addTemplate.focus();
                });
            });

            addDialog.querySelector('[data-rota-add-close]').addEventListener('click', () => addDialog.close());

            document.querySelectorAll('[data-rota-edit-shift]').forEach((button) => {
                button.addEventListener('click', () => {
                    editForm.action = button.getAttribute('data-shift-update');
                    removeForm.action = button.getAttribute('data-shift-destroy');
                    editLabel.textContent = button.getAttribute('data-shift-label') || '';
                    editRole.value = button.getAttribute('data-shift-role') || '';
                    editStarts.value = button.getAttribute('data-shift-starts') || '';
                    editEnds.value = button.getAttribute('data-shift-ends') || '';
                    editUser.value = button.getAttribute('data-shift-user') || '';
                    editDialog.showModal();
                    editRole.focus();
                });
            });

            editDialog.querySelector('[data-rota-edit-close]').addEventListener('click', () => editDialog.close());
        })();
    </script>
</x-layouts.app>
