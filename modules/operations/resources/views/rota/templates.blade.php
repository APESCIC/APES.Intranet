<x-layouts.app title="Shift templates" subtitle="Repeating shifts by site and role. New weeks start from these.">
    @include('operations::rota.nav')
    @include('operations::partials.flash')

    @if ($sites->isEmpty())
        <p class="sg-card p-6 text-sg-muted">You do not look after any sites yet. An admin links sites to teams in the Admin panel.</p>
    @else
        <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="add-template">
            <h2 id="add-template" class="mb-3 font-serif text-xl font-semibold">Add a shift template</h2>
            <form method="post" action="{{ route('operations.rota.templates.store') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="sg-label" for="site_id">Site</label>
                    <select id="site_id" name="site_id" class="sg-input w-full" required>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sg-label" for="name">Name</label>
                    <input id="name" name="name" class="sg-input w-full" placeholder="Morning feed" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="sg-label" for="role">Role</label>
                    <input id="role" name="role" class="sg-input w-full" placeholder="Animal care" value="{{ old('role') }}" required>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="sg-label" for="starts_at">Starts</label>
                        <input id="starts_at" type="time" name="starts_at" class="sg-input w-full" value="{{ old('starts_at', '09:00') }}" required>
                    </div>
                    <div>
                        <label class="sg-label" for="ends_at">Ends</label>
                        <input id="ends_at" type="time" name="ends_at" class="sg-input w-full" value="{{ old('ends_at', '17:00') }}" required>
                    </div>
                </div>
                <fieldset class="sm:col-span-2">
                    <legend class="sg-label">Days</legend>
                    <div class="flex flex-wrap gap-3">
                        @foreach (\Modules\Operations\Models\ShiftTemplate::WEEKDAYS as $number => $day)
                            <label class="flex items-center gap-1 text-sm"><input type="checkbox" name="weekdays[]" value="{{ $number }}" @checked(in_array($number, old('weekdays', [1, 2, 3, 4, 5])))> {{ $day }}</label>
                        @endforeach
                    </div>
                </fieldset>
                <div class="sm:col-span-2">
                    <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save template</button>
                </div>
            </form>
        </section>

        <section class="sg-card p-6" aria-labelledby="templates-heading">
            <h2 id="templates-heading" class="mb-3 font-serif text-xl font-semibold">Templates</h2>
            <x-operations::table-scroll label="Shift templates">
                <table class="sg-table">
                    <thead>
                        <tr><th scope="col">Site</th><th scope="col">Day</th><th scope="col">Name</th><th scope="col">Role</th><th scope="col">Time</th><th scope="col">Status</th><th scope="col"><span class="sr-only">Retire</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td>{{ $template->site->name }}</td>
                                <td>{{ $template->weekdayName() }}</td>
                                <td>{{ $template->name }}</td>
                                <td>{{ $template->role }}</td>
                                <td>{{ substr($template->starts_at, 0, 5) }}–{{ substr($template->ends_at, 0, 5) }}</td>
                                <td><span @class(['sg-badge', 'sg-badge--ok' => $template->active])>{{ $template->active ? 'Active' : 'Retired' }}</span></td>
                                <td>
                                    @if ($template->active)
                                        <form method="post" action="{{ route('operations.rota.templates.destroy', $template) }}">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Retire<span class="sr-only"> {{ $template->name }} on {{ $template->weekdayName() }}</span></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="text-sg-muted">No templates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </x-operations::table-scroll>
        </section>
    @endif
</x-layouts.app>
