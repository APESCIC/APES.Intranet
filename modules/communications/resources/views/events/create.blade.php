<x-layouts.app title="Add an event">
    <form method="post" action="{{ route('communications.events.store') }}" class="grid max-w-3xl gap-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-semibold" for="title">Title</label>
            <input id="title" name="title" class="sg-input w-full" required>
        </div>
        <x-editor.tiptap name="body" label="Details" />
        <div>
            <label class="mb-1 block text-sm font-semibold" for="starts_at">Starts</label>
            <input id="starts_at" type="datetime-local" name="starts_at" class="sg-input" required>
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="ends_at">Ends</label>
            <input id="ends_at" type="datetime-local" name="ends_at" class="sg-input">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="location">Location</label>
            <input id="location" name="location" class="sg-input w-full">
        </div>
        <div>
            <label class="mb-1 block text-sm font-semibold" for="reminder_at">Reminder at</label>
            <input id="reminder_at" type="datetime-local" name="reminder_at" class="sg-input">
        </div>
        <button class="sg-btn sg-btn-primary" type="submit">Publish event</button>
    </form>
</x-layouts.app>
