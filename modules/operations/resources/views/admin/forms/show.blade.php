<x-layouts.app :title="$form->title" :subtitle="ucfirst($form->status).' · '.$form->submissions_count.' submissions'">
    @include('operations::partials.flash')

    <div class="mb-6 flex flex-wrap gap-2">
        <form method="post" action="{{ route('admin.operations.forms.publish', $form) }}">
            @csrf
            <input type="hidden" name="publish" value="{{ $form->isPublished() ? 0 : 1 }}">
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">{{ $form->isPublished() ? 'Unpublish' : 'Publish' }}</button>
        </form>
        @if ($form->submissions_count > 0)
            <a class="sg-btn sg-btn-secondary sg-btn-sm" href="{{ route('admin.operations.forms.export', $form) }}">Export submissions (CSV)</a>
        @endif
        <a class="sg-btn sg-btn-ghost sg-btn-sm" href="{{ route('admin.operations.forms.index') }}">All forms</a>
    </div>

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="settings-heading">
        <h2 id="settings-heading" class="mb-3 font-serif text-xl font-semibold">Settings and routing</h2>
        <form method="post" action="{{ route('admin.operations.forms.update', $form) }}" class="grid gap-3">
            @csrf
            @method('put')
            <div>
                <label class="sg-label" for="title">Title</label>
                <input id="title" name="title" maxlength="200" class="sg-input w-full" value="{{ old('title', $form->title) }}" required>
            </div>
            <div>
                <label class="sg-label" for="description">Description for members</label>
                <textarea id="description" name="description" rows="2" maxlength="2000" class="sg-input w-full">{{ old('description', $form->description) }}</textarea>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="sg-label" for="route_to_user_id">Send submissions to a person</label>
                    <select id="route_to_user_id" name="route_to_user_id" class="sg-input w-full">
                        <option value="">Nobody</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected($form->route_to_user_id === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="sg-label" for="route_to_role">and/or to everyone with a role</label>
                    <select id="route_to_role" name="route_to_role" class="sg-input w-full">
                        <option value="">No role</option>
                        @foreach ($roles as $role)
                            <option value="{{ $role->value }}" @selected($form->route_to_role === $role->value)>{{ $role->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div><button type="submit" class="sg-btn sg-btn-secondary sg-btn-sm">Save settings</button></div>
        </form>
    </section>

    <section class="sg-card mb-6 max-w-3xl p-6" aria-labelledby="fields-heading">
        <h2 id="fields-heading" class="mb-3 font-serif text-xl font-semibold">Fields</h2>
        <ol class="mb-4 grid gap-2">
            @forelse ($form->fields as $field)
                <li class="flex flex-wrap items-center justify-between gap-2 rounded-sg-field border border-sg-info-mist p-3" data-field="{{ $field->id }}">
                    <span>
                        <span class="font-semibold">{{ $field->label }}</span>
                        · {{ \Modules\Operations\Models\FormField::TYPES[$field->type] }}
                        @if ($field->required)<span class="sg-badge">Required</span>@endif
                        @if ($field->options) · {{ implode(', ', $field->options) }}@endif
                    </span>
                    @unless ($form->isPublished() || $form->submissions_count > 0)
                        <form method="post" action="{{ route('admin.operations.forms.fields.destroy', [$form, $field]) }}">
                            @csrf
                            @method('delete')
                            <button type="submit" class="sg-btn sg-btn-ghost sg-btn-sm">Remove<span class="sr-only"> {{ $field->label }}</span></button>
                        </form>
                    @endunless
                </li>
            @empty
                <li class="text-sg-muted">No fields yet.</li>
            @endforelse
        </ol>

        @if ($form->isPublished())
            <p class="text-sm text-sg-muted">Unpublish the form to add fields.</p>
        @else
            <form method="post" action="{{ route('admin.operations.forms.fields.store', $form) }}" class="grid gap-3 sm:grid-cols-2">
                @csrf
                <div>
                    <label class="sg-label" for="label">Question</label>
                    <input id="label" name="label" maxlength="200" class="sg-input w-full" required>
                </div>
                <div>
                    <label class="sg-label" for="type">Answer type</label>
                    <select id="type" name="type" class="sg-input w-full">
                        @foreach (\Modules\Operations\Models\FormField::TYPES as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="sm:col-span-2">
                    <label class="sg-label" for="options">Choices, separated by commas (for "Choice from a list")</label>
                    <input id="options" name="options" maxlength="2000" class="sg-input w-full">
                </div>
                <label class="flex items-center gap-2 text-sm font-semibold"><input type="checkbox" name="required" value="1"> Required</label>
                <div><button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add field</button></div>
            </form>
        @endif
    </section>
</x-layouts.app>
