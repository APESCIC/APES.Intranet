<x-layouts.app title="Raise a ticket" subtitle="Choose what it is about and it goes to the right team: IT or Management.">
    @include('helpdesk::partials.flash')

    <form method="post" action="{{ route('helpdesk.tickets.store') }}" class="sg-card grid max-w-3xl gap-4 p-6">
        @csrf
        <div>
            <label class="sg-label" for="category_id">What is it about?</label>
            <select id="category_id" name="category_id" class="sg-input w-full" required>
                <option value="">Choose a topic</option>
                @foreach ($categories as $queue => $group)
                    <optgroup label="{{ \Modules\Helpdesk\Models\Ticket::QUEUES[$queue] ?? $queue }}">
                        @foreach ($group as $category)
                            <option value="{{ $category->id }}" @selected((int) old('category_id', $selected) === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
        </div>
        <div>
            <label class="sg-label" for="subject">Subject</label>
            <input id="subject" name="subject" maxlength="200" class="sg-input w-full" value="{{ old('subject') }}" required>
        </div>
        <x-editor.tiptap name="body" label="Tell us what you need" :value="old('body', '')" required />
        <div>
            <label class="sg-label" for="priority">How urgent is it?</label>
            <select id="priority" name="priority" class="sg-input w-full sm:w-auto">
                <option value="">The usual for this topic</option>
                @foreach (\Modules\Helpdesk\Models\Ticket::PRIORITIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('priority') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <p class="text-sm text-sg-muted">Do not include passwords, codes or someone else's personal details.</p>
        <div>
            <button type="submit" class="sg-btn sg-btn-primary sg-btn-sm">Raise ticket</button>
        </div>
    </form>
</x-layouts.app>
