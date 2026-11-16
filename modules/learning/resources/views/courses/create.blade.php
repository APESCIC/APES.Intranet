<x-layouts.app title="New course">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">New course</h1>
        <form method="post" action="{{ route('learning.courses.store') }}" class="mt-6 grid gap-4">
            @csrf
            <div>
                <label class="mb-1 block text-sm font-semibold" for="title">Title</label>
                <input id="title" name="title" value="{{ old('title') }}" class="sg-input w-full" required maxlength="200">
                @error('title') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="mb-1 block text-sm font-semibold" for="summary">Summary</label>
                <textarea id="summary" name="summary" rows="3" class="sg-input w-full" maxlength="2000">{{ old('summary') }}</textarea>
            </div>
            <button class="sg-btn sg-btn-primary" type="submit">Create draft</button>
        </form>
    </div>
</x-layouts.app>
