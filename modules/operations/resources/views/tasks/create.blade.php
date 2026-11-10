<x-layouts.app title="Add a task" subtitle="For a person, a team or a shift.">
    @include('operations::partials.flash')

    <form method="post" action="{{ route('operations.tasks.store') }}" class="sg-card grid max-w-3xl gap-4 p-6">
        @csrf
        <div>
            <label class="sg-label" for="title">Title</label>
            <input id="title" name="title" maxlength="200" class="sg-input w-full" value="{{ old('title') }}" required>
        </div>
        <x-editor.tiptap name="description" label="Details (optional)" :value="old('description', '')" />

        <fieldset class="grid gap-3">
            <legend class="sg-label">Who is it for?</legend>
            <div class="grid gap-3 sm:grid-cols-3">
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="assignee_type" value="person" @checked(old('assignee_type', 'person') === 'person')> A person</label>
                    <label class="sr-only" for="user_id">Person</label>
                    <select id="user_id" name="user_id" class="sg-input mt-1 w-full">
                        <option value="">Choose a person</option>
                        @foreach ($members as $member)
                            <option value="{{ $member->id }}" @selected((int) old('user_id') === $member->id)>{{ $member->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="assignee_type" value="team" @checked(old('assignee_type') === 'team')> A team</label>
                    <label class="sr-only" for="team">Team</label>
                    <select id="team" name="team" class="sg-input mt-1 w-full">
                        <option value="">Choose a team</option>
                        @foreach ($teams as $team)
                            <option value="{{ $team }}" @selected(old('team') === $team)>{{ $team }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm font-semibold"><input type="radio" name="assignee_type" value="shift" @checked(old('assignee_type') === 'shift')> A shift</label>
                    <label class="sr-only" for="shift_id">Shift</label>
                    <select id="shift_id" name="shift_id" class="sg-input mt-1 w-full">
                        <option value="">Choose a shift</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected((int) old('shift_id') === $shift->id)>{{ $shift->label() }} · {{ $shift->site->name }}{{ $shift->member ? ' · '.$shift->member->name : ' · open' }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </fieldset>

        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="sg-label" for="due_at">Due (optional)</label>
                <input id="due_at" type="datetime-local" name="due_at" class="sg-input w-full" value="{{ old('due_at') }}">
            </div>
            <div>
                <label class="sg-label" for="recurrence">Repeats</label>
                <select id="recurrence" name="recurrence" class="sg-input w-full">
                    <option value="">Does not repeat</option>
                    @foreach (\Modules\Operations\Models\Task::RECURRENCES as $value => $label)
                        <option value="{{ $value }}" @selected(old('recurrence') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div>
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Add task</button>
        </div>
    </form>
</x-layouts.app>
