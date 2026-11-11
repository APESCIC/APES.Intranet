<x-layouts.app title="Help desk topics" subtitle="Each topic sends tickets to one queue with a usual priority. Retire a topic rather than deleting it.">
    @include('helpdesk::partials.flash')

    <section class="sg-card mb-6 p-6" aria-labelledby="sla-heading">
        <h2 id="sla-heading" class="mb-3 font-serif text-xl font-semibold">Service level targets</h2>
        <p class="mb-3 text-sg-muted">Hours from when a ticket is raised. Set in the help desk config.</p>
        <x-helpdesk::table-scroll label="Service level targets">
            <table class="sg-table">
                <thead><tr><th scope="col">Priority</th><th scope="col">First reply within</th><th scope="col">Resolved within</th></tr></thead>
                <tbody>
                    @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                        <tr>
                            <td>{{ $label }}</td>
                            <td>{{ config('helpdesk.sla_hours.'.$value.'.respond') }} hours</td>
                            <td>{{ config('helpdesk.sla_hours.'.$value.'.resolve') }} hours</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-helpdesk::table-scroll>
    </section>

    <ul class="mb-6 grid gap-4">
        @foreach ($categories as $category)
            <li class="sg-card p-5" data-category="{{ $category->slug }}">
                <form method="post" action="{{ route('admin.helpdesk.categories.update', $category) }}" class="grid gap-3 md:grid-cols-[2fr_1fr_1fr_6rem_auto_auto] md:items-end">
                    @csrf
                    @method('put')
                    <div>
                        <label class="sg-label" for="name-{{ $category->id }}">Topic</label>
                        <input id="name-{{ $category->id }}" name="name" class="sg-input w-full" value="{{ $category->name }}" maxlength="120" required>
                    </div>
                    <div>
                        <label class="sg-label" for="queue-{{ $category->id }}">Queue</label>
                        <select id="queue-{{ $category->id }}" name="queue" class="sg-input w-full">
                            @foreach (\Modules\Helpdesk\Models\Ticket::QUEUES as $value => $label)
                                <option value="{{ $value }}" @selected($category->queue === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sg-label" for="priority-{{ $category->id }}">Usual priority</label>
                        <select id="priority-{{ $category->id }}" name="default_priority" class="sg-input w-full">
                            @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                                <option value="{{ $value }}" @selected($category->default_priority === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sg-label" for="sort-{{ $category->id }}">Order</label>
                        <input id="sort-{{ $category->id }}" type="number" name="sort_order" min="0" max="1000" class="sg-input w-full" value="{{ $category->sort_order }}">
                    </div>
                    <label class="inline-flex items-center gap-2 text-sm"><input type="checkbox" name="active" value="1" @checked($category->active)> In use</label>
                    <button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save</button>
                </form>
                <p class="mt-2 text-sm text-sg-muted">{{ $category->tickets_count }} {{ \Illuminate\Support\Str::plural('ticket', $category->tickets_count) }}</p>
            </li>
        @endforeach
    </ul>

    <form method="post" action="{{ route('admin.helpdesk.categories.store') }}" class="sg-card grid max-w-3xl gap-3 p-6">
        @csrf
        <h2 class="font-serif text-xl font-semibold">Add a topic</h2>
        <div>
            <label class="sg-label" for="new-name">Topic</label>
            <input id="new-name" name="name" class="sg-input w-full" maxlength="120" required>
        </div>
        <div class="grid gap-3 sm:grid-cols-3">
            <div>
                <label class="sg-label" for="new-queue">Queue</label>
                <select id="new-queue" name="queue" class="sg-input w-full">
                    @foreach (\Modules\Helpdesk\Models\Ticket::QUEUES as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sg-label" for="new-priority">Usual priority</label>
                <select id="new-priority" name="default_priority" class="sg-input w-full">
                    @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                        <option value="{{ $value }}" @selected($value === 'normal')>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="sg-label" for="new-sort">Order</label>
                <input id="new-sort" type="number" name="sort_order" min="0" max="1000" value="50" class="sg-input w-full">
            </div>
        </div>
        <input type="hidden" name="active" value="1">
        <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add topic</button></div>
    </form>
</x-layouts.app>
