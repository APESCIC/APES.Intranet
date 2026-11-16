<x-layouts.app :title="$title">
    <div class="mx-auto max-w-3xl px-4 py-8">
        <h1 class="font-display text-3xl text-ink">{{ $title }}</h1>
        <p class="mt-3 text-ink-muted">
            Your intranet courses will appear here. This is separate from
            <a href="{{ route('hr.training.mine') }}" class="text-brand underline">My training</a>
            in HR, which tracks compliance links to the external LMS.
        </p>
    </div>
</x-layouts.app>
