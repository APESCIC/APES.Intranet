<x-layouts.app title="Checklist templates" subtitle="Routine checks by site and shift. Each published shift gets its own copy.">
    @include('operations::partials.flash')

    <p class="mb-4"><a class="sg-btn sg-btn-ghost sg-btn-sm" href="{{ route('operations.checklists.index') }}">Back to checklists</a></p>

    @if ($sites->isEmpty())
        <p class="sg-card p-6 text-sg-muted">You do not look after any sites yet. An admin links sites to teams in the Admin panel.</p>
    @else
        <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="add-heading">
            <h2 id="add-heading" class="mb-3 font-serif text-xl font-semibold">Add a checklist</h2>
            <form method="post" action="{{ route('operations.checklists.templates.store') }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="sg-label" for="site_id">Site</label>
                    <select id="site_id" name="site_id" class="sg-input w-full" required>
                        @foreach ($sites as $site)
                            <option value="{{ $site->id }}" @selected((int) old('site_id') === $site->id)>{{ $site->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sg-label" for="shift_template_id">Shift</label>
                    <select id="shift_template_id" name="shift_template_id" class="sg-input w-full">
                        <option value="">Every shift at the site</option>
                        @foreach ($shiftTemplates as $shiftTemplate)
                            <option value="{{ $shiftTemplate->id }}">{{ $shiftTemplate->weekdayName() }} {{ substr($shiftTemplate->starts_at, 0, 5) }} · {{ $shiftTemplate->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sg-label" for="name">Name</label>
                    <input id="name" name="name" maxlength="120" class="sg-input w-full" placeholder="Morning reptile checks" value="{{ old('name') }}" required>
                </div>
                <div>
                    <label class="sg-label" for="due_time">Complete by</label>
                    <input id="due_time" type="time" name="due_time" class="sg-input w-full" value="{{ old('due_time', '10:00') }}" required>
                </div>
                <div class="sm:col-span-2">
                    <label class="sg-label" for="items">Checks, one per line</label>
                    <textarea id="items" name="items" rows="6" maxlength="5000" class="sg-input w-full" required>{{ old('items') }}</textarea>
                </div>
                <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Save checklist</button></div>
            </form>
        </section>

        <section class="sg-card p-6" aria-labelledby="templates-heading">
            <h2 id="templates-heading" class="mb-3 font-serif text-xl font-semibold">Checklists</h2>
            <div class="overflow-x-auto">
                <table class="sg-table">
                    <thead>
                        <tr><th scope="col">Name</th><th scope="col">Site</th><th scope="col">Shift</th><th scope="col">By</th><th scope="col">Checks</th><th scope="col"><span class="sr-only">Retire</span></th></tr>
                    </thead>
                    <tbody>
                        @forelse ($templates as $template)
                            <tr>
                                <td>{{ $template->name }} @unless ($template->active)<span class="sg-badge">Retired</span>@endunless</td>
                                <td>{{ $template->site->name }}</td>
                                <td>{{ $template->shiftTemplate ? $template->shiftTemplate->weekdayName().' · '.$template->shiftTemplate->name : 'Every shift' }}</td>
                                <td>{{ substr($template->due_time, 0, 5) }}</td>
                                <td>{{ count($template->items) }}</td>
                                <td>
                                    @if ($template->active)
                                        <form method="post" action="{{ route('operations.checklists.templates.destroy', $template) }}">
                                            @csrf
                                            @method('delete')
                                            <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Retire<span class="sr-only"> {{ $template->name }}</span></button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-sg-muted">No checklists yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</x-layouts.app>
