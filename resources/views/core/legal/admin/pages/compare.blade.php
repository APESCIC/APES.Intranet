<x-layouts.app :title="$page->title.' — compare'" subtitle="Word-level comparison between two versions.">
    <p class="mb-4 text-sm">
        <a href="{{ route('admin.legal.pages.history', $page) }}" class="underline">Back to history</a>
    </p>

    <p class="mb-4 text-sm text-sg-muted">
        Comparing version {{ $fromVersion->number }} to version {{ $toVersion->number }} (insertions and deletions are marked below).
    </p>

    <article class="sg-card max-w-3xl p-6 text-sm leading-relaxed">
        @if ($diff === [])
            <p class="text-sg-muted">No textual differences.</p>
        @else
            <p>
                @foreach ($diff as $token)
                    @if ($token['type'] === 'equal')
                        {{ $token['text'] }} 
                    @elseif ($token['type'] === 'insert')
                        <ins class="bg-sg-ok-mist no-underline">{{ $token['text'] }}</ins> 
                    @else
                        <del class="bg-red-100">{{ $token['text'] }}</del> 
                    @endif
                @endforeach
            </p>
        @endif
    </article>
</x-layouts.app>