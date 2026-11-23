@php
    $selected = $selectedCategory;
@endphp
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

    <div class="sg-topics-layout" data-helpdesk-topics>
        <section class="sg-card p-5" aria-labelledby="topics-list-heading">
            <h2 id="topics-list-heading" class="font-serif text-lg font-semibold">Topics</h2>
            <ul class="sg-topics-list mt-3">
                @foreach ($categories as $category)
                    <li>
                        <a
                            href="{{ route('admin.helpdesk.categories.index', ['topic' => $category->slug]) }}"
                            class="sg-topics-list__link"
                            data-category="{{ $category->slug }}"
                            @if ($selected?->is($category)) aria-current="true" @endif
                        >
                            <span class="sg-topics-list__name">{{ $category->name }}</span>
                            <span class="sg-topics-list__meta">
                                {{ \Modules\Helpdesk\Models\Ticket::QUEUES[$category->queue] ?? $category->queue }}
                                · {{ $category->tickets_count }} {{ \Illuminate\Support\Str::plural('ticket', $category->tickets_count) }}
                                @unless ($category->active)
                                    · Retired
                                @endunless
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>

        <div class="grid gap-4">
            @if ($selected)
                <section class="sg-card p-5" aria-labelledby="topic-edit-heading" data-category="{{ $selected->slug }}">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h2 id="topic-edit-heading" class="font-serif text-lg font-semibold">Edit topic</h2>
                            <p class="mt-1 text-sm text-sg-muted">One form for this topic. Save once when you are done.</p>
                        </div>
                        <p class="sg-topics-dirty" data-topic-dirty hidden role="status" aria-live="polite" aria-hidden="true">
                            You have unsaved changes
                            <button type="button" class="sg-btn sg-btn-secondary sg-btn-sm" data-topic-discard>Discard</button>
                        </p>
                    </div>

                    <form
                        method="post"
                        action="{{ route('admin.helpdesk.categories.update', $selected) }}"
                        class="mt-4 grid gap-3"
                        data-topic-form
                    >
                        @csrf
                        @method('put')

                        <div>
                            <label class="sg-label" for="topic-name">Topic</label>
                            <input id="topic-name" name="name" class="sg-input w-full" value="{{ old('name', $selected->name) }}" maxlength="120" required>
                            @error('name')
                                <p class="mt-1 text-sm text-sg-error" role="alert">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="grid gap-3 sm:grid-cols-3">
                            <div>
                                <label class="sg-label" for="topic-queue">Queue</label>
                                <select id="topic-queue" name="queue" class="sg-input w-full">
                                    @foreach (\Modules\Helpdesk\Models\Ticket::QUEUES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('queue', $selected->queue) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('queue')
                                    <p class="mt-1 text-sm text-sg-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="sg-label" for="topic-priority">Usual priority</label>
                                <select id="topic-priority" name="default_priority" class="sg-input w-full">
                                    @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                                        <option value="{{ $value }}" @selected(old('default_priority', $selected->default_priority) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('default_priority')
                                    <p class="mt-1 text-sm text-sg-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="sg-label" for="topic-sort">Order</label>
                                <input id="topic-sort" type="number" name="sort_order" min="0" max="1000" class="sg-input w-full" value="{{ old('sort_order', $selected->sort_order) }}">
                                @error('sort_order')
                                    <p class="mt-1 text-sm text-sg-error" role="alert">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <label class="sg-toggle">
                            <input type="checkbox" name="active" value="1" @checked(old('active', $selected->active))>
                            <span>In use</span>
                        </label>
                        <label class="sg-toggle">
                            <input type="checkbox" name="requires_identity_check" value="1" @checked(old('requires_identity_check', $selected->requires_identity_check))>
                            <span>Identity check before closing</span>
                        </label>

                        <div>
                            <button type="submit" class="sg-btn sg-btn-primary">Save topic</button>
                        </div>
                    </form>
                </section>
            @else
                <p class="sg-card sg-empty-state p-5">No topics yet. Add one below.</p>
            @endif

            <form method="post" action="{{ route('admin.helpdesk.categories.store') }}" class="sg-card grid gap-3 p-5">
                @csrf
                <h2 class="font-serif text-lg font-semibold">Add a topic</h2>
                <div>
                    <label class="sg-label" for="new-name">Topic</label>
                    <input id="new-name" name="name" class="sg-input w-full" maxlength="120" required value="{{ old('name') }}">
                    @error('name')
                        <p class="mt-1 text-sm text-sg-error" role="alert">{{ $message }}</p>
                    @enderror
                </div>
                <div class="grid gap-3 sm:grid-cols-3">
                    <div>
                        <label class="sg-label" for="new-queue">Queue</label>
                        <select id="new-queue" name="queue" class="sg-input w-full">
                            @foreach (\Modules\Helpdesk\Models\Ticket::QUEUES as $value => $label)
                                <option value="{{ $value }}" @selected(old('queue') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sg-label" for="new-priority">Usual priority</label>
                        <select id="new-priority" name="default_priority" class="sg-input w-full">
                            @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                                <option value="{{ $value }}" @selected(old('default_priority', 'normal') === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="sg-label" for="new-sort">Order</label>
                        <input id="new-sort" type="number" name="sort_order" min="0" max="1000" value="{{ old('sort_order', 50) }}" class="sg-input w-full">
                    </div>
                </div>
                <label class="sg-toggle">
                    <input type="checkbox" name="requires_identity_check" value="1" @checked(old('requires_identity_check'))>
                    <span>Identity check before closing</span>
                </label>
                <input type="hidden" name="active" value="1">
                <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add topic</button></div>
            </form>
        </div>
    </div>
</x-layouts.app>
