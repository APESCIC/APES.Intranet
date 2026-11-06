<x-layouts.app title="New handbook chapter">
    <form method="post" action="{{ route('communications.handbook.store') }}" class="grid max-w-3xl gap-4">
        @csrf
        <div>
            <label class="mb-1 block text-sm font-semibold" for="title">Title</label>
            <input id="title" name="title" class="sg-input w-full" required>
        </div>
        <x-editor.tiptap name="body" label="Chapter body" required />
        <button class="sg-btn sg-btn-primary" type="submit">Publish chapter</button>
    </form>
</x-layouts.app>
