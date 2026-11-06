<x-layouts.app :title="$chapter->title" :subtitle="'Version '.$version->version_number">
    <div class="mb-4 flex flex-wrap gap-2 text-sm">
        @foreach ($versions as $v)
            <a class="underline {{ $v->id === $version->id ? 'font-bold' : '' }}" href="{{ route('communications.handbook.show', ['chapter' => $chapter, 'version' => $v->version_number]) }}">v{{ $v->version_number }}</a>
        @endforeach
        @can('hr.access')
            <a class="underline" href="{{ route('communications.handbook.acknowledgements', $chapter) }}">Export acknowledgements (CSV)</a>
        @endcan
    </div>
    <article class="sg-card max-w-3xl p-5">
        <div class="prose max-w-none">{!! $version->body !!}</div>
    </article>
    @if ($version->id === $chapter->currentVersion?->id)
        @if ($acknowledged)
            <p class="mt-4 text-sg-muted">You have acknowledged this version.</p>
        @else
            <form method="post" action="{{ route('communications.handbook.acknowledge', $chapter) }}" class="mt-4">
                @csrf
                <button class="sg-btn sg-btn-primary" type="submit">I have read and understand this chapter</button>
            </form>
        @endif
    @endif
    @if ($canPublish)
        <form method="post" action="{{ route('communications.handbook.versions.store', $chapter) }}" class="mt-8 grid max-w-3xl gap-4">
            @csrf
            <h2 class="font-serif text-xl font-semibold">Publish a new version</h2>
            <x-editor.tiptap name="body" label="Updated body" :value="$version->body" required />
            <button class="sg-btn sg-btn-secondary" type="submit">Publish new version</button>
        </form>
    @endif
</x-layouts.app>
